<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Photo;
use App\Solen\CurrentEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Le mur photo : les invités y déposent leurs clichés, plusieurs à la fois.
 *
 * Les réglages du module sont appliqués ici : consigne, poids maximal,
 * modération, téléchargement — et le plafond de photos de la formule.
 */
class GalerieController extends Controller
{
    public function __construct(private readonly CurrentEvent $courant)
    {
    }

    public function index(): View
    {
        $event = $this->courant->get();

        return view('pages.galerie', [
            'photos'         => Photo::publies()->with('participant')->latest()->get(),
            'consigne'       => $event->reglage('mur', 'texte_intro', 'Partagez vos plus belles photos de la journée.'),
            'poidsMax'       => (int) $event->reglage('mur', 'taille_max_mo', 10),
            'telechargement' => (bool) $event->reglage('mur', 'telechargement', true),
            'pleine'         => $event->photosRestantes() === 0,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $event = $this->courant->get();
        $poids = (int) $event->reglage('mur', 'taille_max_mo', 10) * 1024;

        $request->validate([
            'prenom'   => ['required', 'string', 'max:100'],
            // Le prénom suffit à savoir qui a posté : le nom est facultatif.
            'nom'      => ['nullable', 'string', 'max:100'],
            'photos'   => ['required', 'array', 'min:1', 'max:20'],
            'photos.*' => ['image', "max:{$poids}"],
        ], [
            'photos.required' => 'Choisissez au moins une photo.',
            'photos.*.max'    => 'Une photo dépasse la taille autorisée.',
            'photos.*.image'  => 'Seules les images sont acceptées.',
        ]);

        $restantes = $event->photosRestantes();

        if ($restantes === 0) {
            return back()->with('erreur', 'La galerie de ce mariage est pleine.');
        }

        $participant = Participant::firstOrCreate([
            'nom'    => trim((string) $request->input('nom')),
            'prenom' => $request->prenom,
        ]);

        $aValider = $event->reglage('mur', 'moderation', 'aucune') === 'apres';
        $fichiers = collect($request->file('photos'));

        if ($restantes !== null) {
            $fichiers = $fichiers->take($restantes);
        }

        foreach ($fichiers as $fichier) {
            Photo::create([
                'participant_id' => $participant->id,
                'path'           => $fichier->store("galerie/{$event->id}", 'public'),
                'publie'         => ! $aValider,
            ]);
        }

        $n = $fichiers->count();

        return back()->with('success', $aValider
            ? ($n > 1 ? "Merci ! Vos {$n} photos seront visibles dès que les mariés les auront validées." : 'Merci ! Votre photo sera visible dès que les mariés l’auront validée.')
            : ($n > 1 ? "Merci ! Vos {$n} photos ont été ajoutées." : 'Merci ! Votre photo a été ajoutée.'));
    }
}
