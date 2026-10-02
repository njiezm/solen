<?php

namespace App\Http\Controllers;

use App\Mail\PhotoPhotobooth;
use App\Models\Participant;
use App\Models\Photo;
use App\Solen\CurrentEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Photobooth.
 *
 * Deux modes, imposés par la formule et non réglables par le client :
 *  · simple — chacun le lance depuis son téléphone après avoir scanné un QR ;
 *  · borne  — plein écran verrouillé sur une tablette posée dans la salle,
 *             avec enchaînement automatique d'un invité à l'autre.
 *
 * La capture, le cadre et le filigrane sont faits dans le navigateur : rien
 * n'est envoyé au serveur avant que l'invité ait validé son cliché.
 */
class PhotoboothController extends Controller
{
    public function index(CurrentEvent $courant): View
    {
        $event = $courant->get();

        abort_unless($event->aModule('photobooth'), 404, 'Le photobooth n’est pas activé pour ce mariage.');

        $cadre = $event->reglage('photobooth', 'cadre');

        return view('pages.photobooth', [
            'borne'      => $event->photoboothEnBorne(),
            'consigne'   => $event->reglage('photobooth', 'consigne', 'Souriez, c’est pour les mariés !'),
            'poses'      => (int) $event->reglage('photobooth', 'nb_poses', 1),
            'rebours'    => (int) $event->reglage('photobooth', 'compte_a_rebours', 3),
            'cadre'      => $cadre ? Storage::url($cadre) : null,
            'filigrane'  => (bool) $event->reglage('photobooth', 'filigrane', true),
            'apres'      => $event->reglage('photobooth', 'message_apres', 'Merci ! Votre photo rejoint le mur.'),
            'signature'  => trim($event->nom . ' · ' . ($event->dateLocale()?->format('d.m.Y') ?? '')),
        ]);
    }

    /**
     * Reçoit le cliché déjà composé (cadre et filigrane appliqués côté
     * navigateur), en dataURL. Répond en JSON : la page ne se recharge pas,
     * ce qui compte sur une borne.
     */
    public function stocker(Request $request, CurrentEvent $courant): JsonResponse
    {
        $event = $courant->get();

        abort_unless($event->aModule('photobooth'), 404);

        $donnees = $request->validate([
            'image'  => ['required', 'string'],
            'prenom' => ['nullable', 'string', 'max:60'],
            'nom'    => ['nullable', 'string', 'max:60'],
            'email'  => ['nullable', 'email', 'max:255'],
        ], [], ['image' => 'photo', 'email' => 'adresse e-mail']);

        $binaire = $this->decoder($donnees['image']);

        if ($binaire === null) {
            return response()->json(['erreur' => 'Image illisible.'], 422);
        }

        if ($plafond = $this->plafondAtteint($event)) {
            return response()->json(['erreur' => $plafond], 422);
        }

        $chemin = 'evenements/' . $event->id . '/photobooth/' . Str::uuid() . '.jpg';
        Storage::disk('public')->put($chemin, $binaire);

        // Sur une borne, personne ne saisit son nom : la photo est anonyme.
        $participant = filled($donnees['prenom'] ?? null)
            ? Participant::firstOrCreate([
                'prenom' => $donnees['prenom'],
                'nom'    => $donnees['nom'] ?: '—',
            ])
            : null;

        // Même règle que la galerie : si les mariés valident avant
        // publication, la photo du photobooth attend aussi leur accord.
        $photo = Photo::create([
            'participant_id' => $participant?->id,
            'path'           => $chemin,
            'publie'         => $event->reglage('mur', 'moderation', 'aucune') !== 'apres',
        ]);

        $envoye = $this->envoyerParEmail($event, $chemin, $donnees);

        return response()->json([
            'ok'      => true,
            'url'     => Storage::url($chemin),
            'id'      => $photo->id,
            'envoye'  => $envoye,
            'message' => $envoye
                ? 'C’est envoyé ! Regardez vos e-mails.'
                : $event->reglage('photobooth', 'message_apres'),
        ]);
    }

    /**
     * Envoie le cliché à l'invité s'il a laissé son adresse.
     *
     * Mis en file d'attente : la photo pèse plusieurs centaines de kilo-octets,
     * et personne ne doit patienter devant une borne pendant que le serveur
     * de messagerie répond.
     */
    private function envoyerParEmail($event, string $chemin, array $donnees): bool
    {
        if (empty($donnees['email'])) {
            return false;
        }

        try {
            Mail::to($donnees['email'])->queue(
                new PhotoPhotobooth($event, $chemin, $donnees['prenom'] ?? null)
            );

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /** Décode une dataURL en binaire, en refusant tout ce qui n'est pas une image. */
    private function decoder(string $dataUrl): ?string
    {
        if (! preg_match('#^data:image/(jpeg|png|webp);base64,#', $dataUrl, $correspondance)) {
            return null;
        }

        $binaire = base64_decode(substr($dataUrl, strlen($correspondance[0])), true);

        if ($binaire === false || strlen($binaire) > 8 * 1024 * 1024) {
            return null;
        }

        return $binaire;
    }

    /** Le nombre de photos est plafonné par la formule. */
    private function plafondAtteint($event): ?string
    {
        return $event->photosRestantes() === 0
            ? 'La galerie de ce mariage est pleine.'
            : null;
    }
}
