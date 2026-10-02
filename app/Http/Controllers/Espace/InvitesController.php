<?php

namespace App\Http\Controllers\Espace;

use App\Http\Controllers\Controller;
use App\Models\EventPart;
use App\Models\Invite;
use App\Solen\CurrentEvent;
use App\Solen\Documents;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * La liste d'invités, côté mariés : qui vient, combien, à quels moments,
 * avec quelles allergies. Chaque foyer reçoit son lien de réponse par
 * WhatsApp en un geste.
 *
 * Recherches explicites (findOrFail) : modèle cloisonné par mariage.
 */
class InvitesController extends Controller
{
    public function __construct(private readonly CurrentEvent $courant)
    {
    }

    public function index(Request $request): View
    {
        abort_unless($this->courant->get()->aModule('rsvp'), 404);

        $filtre = $request->query('filtre');
        $tous = Invite::orderBy('groupe')->orderBy('nom')->get();
        $moments = EventPart::actives()->orderBy('ordre')->get();

        $oui = $tous->where('reponse', 'oui');

        return view('espace.invites', [
            'invites'  => $tous->when($filtre, fn ($c) => $c->where('reponse', $filtre)),
            'filtre'   => $filtre,
            'moments'  => $moments,
            'groupes'  => $tous->pluck('groupe')->filter()->unique()->sort()->values(),
            'chiffres' => [
                'foyers'     => $tous->count(),
                'places'     => $tous->sum('places'),
                'presents'   => $oui->sum('presents'),
                'absents'    => $tous->where('reponse', 'non')->count(),
                'attente'    => $tous->where('reponse', 'attente')->count(),
                'par_moment' => $moments->mapWithKeys(fn ($m) => [$m->nom => $oui->filter(fn ($i) => in_array($m->cle, $i->moments ?? [], true))->sum('presents')]),
            ],
            'regimes'  => $oui->filter(fn ($i) => filled($i->regimes))->values(),
        ]);
    }

    public function ajouter(Request $request): RedirectResponse
    {
        Invite::create($this->valider($request));

        return back()->with('ok', 'Invité ajouté.')->withFragment('liste');
    }

    public function maj(Request $request, int $id): RedirectResponse
    {
        Invite::findOrFail($id)->update($this->valider($request));

        return back()->with('ok', 'Invité mis à jour.')->withFragment('invite-' . $id);
    }

    public function supprimer(int $id): RedirectResponse
    {
        Invite::findOrFail($id)->delete();

        return back()->with('ok', 'Invité retiré de la liste.')->withFragment('liste');
    }

    /**
     * Import d'une liste collée depuis un tableur ou un carnet d'adresses.
     * Une ligne par foyer : « Nom ; places ; téléphone ; e-mail ; groupe ».
     * Seul le nom est obligatoire ; séparateurs acceptés : ; , ou tabulation.
     */
    public function importer(Request $request): RedirectResponse
    {
        $request->validate(['liste' => ['required', 'string', 'max:50000']]);

        $existants = Invite::pluck('nom')->map(fn ($n) => mb_strtolower(trim($n)))->all();
        $ajoutes = 0;
        $ignores = 0;

        foreach (preg_split('/\R/', $request->input('liste')) as $ligne) {
            $cols = array_map('trim', preg_split('/\t|;|,(?=\s*\d|\s*\+|\s*0|\s*[^@\s]+@)/', $ligne));
            $nom = $cols[0] ?? '';

            if ($nom === '' || in_array(mb_strtolower($nom), $existants, true) || mb_strtolower($nom) === 'nom') {
                $ignores += $nom !== '' ? 1 : 0;
                continue;
            }

            $places = 1; $tel = null; $email = null; $groupe = null;
            foreach (array_slice($cols, 1) as $valeur) {
                match (true) {
                    $valeur === ''                                  => null,
                    (bool) filter_var($valeur, FILTER_VALIDATE_EMAIL) => $email = $valeur,
                    (bool) preg_match('/^\d{1,2}$/', $valeur)       => $places = max(1, min(20, (int) $valeur)),
                    (bool) preg_match('/^[\d\s+().-]{8,}$/', $valeur) => $tel = $valeur,
                    default                                         => $groupe ??= mb_substr($valeur, 0, 60),
                };
            }

            Invite::create(['nom' => mb_substr($nom, 0, 255), 'places' => $places, 'telephone' => $tel, 'email' => $email, 'groupe' => $groupe]);
            $existants[] = mb_strtolower($nom);
            $ajoutes++;
        }

        return back()->with('ok', "{$ajoutes} foyer(s) importé(s)" . ($ignores ? ", {$ignores} déjà présent(s) ignoré(s)" : '') . '.')->withFragment('liste');
    }

    /** Le lien personnel du foyer, envoyé par WhatsApp. */
    public function whatsapp(int $id): RedirectResponse
    {
        $invite = Invite::findOrFail($id);
        $event = $this->courant->get();

        abort_unless($invite->telephone, 422);

        $limite = $event->reglage('rsvp', 'date_limite');
        $texte = "Bonjour {$invite->nom} !\n\n"
            . "Nous serions si heureux de vous compter parmi nous pour notre mariage"
            . ($event->dateLocale() ? ' le ' . $event->dateLocale()->translatedFormat('j F Y') : '') . ".\n\n"
            . 'Pouvez-vous nous répondre' . ($limite ? ' avant le ' . \Illuminate\Support\Carbon::parse($limite)->translatedFormat('j F') : '') . " ? C’est ici :\n"
            . $invite->lien() . "\n\n" . $event->nom;

        $invite->update(['relance_le' => now()]);

        return redirect()->away(app(Documents::class)->whatsapp($invite->telephone, $texte));
    }

    /** Export pour le traiteur, le plan de table ou un tableur. */
    public function exporter(): StreamedResponse
    {
        $moments = EventPart::actives()->orderBy('ordre')->get();
        $nom = 'invites-' . \Illuminate\Support\Str::slug($this->courant->get()->nom) . '.csv';

        return response()->streamDownload(function () use ($moments) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF"); // Excel lit l'UTF-8 avec ce marqueur
            fputcsv($f, array_merge(['Foyer', 'Groupe', 'Places', 'Réponse', 'Présents', 'Prénoms'], $moments->pluck('nom')->all(), ['Allergies et régimes', 'Message', 'Téléphone', 'E-mail', 'Lien de réponse']), ';');

            foreach (Invite::orderBy('groupe')->orderBy('nom')->get() as $i) {
                fputcsv($f, array_merge(
                    [$i->nom, $i->groupe, $i->places, Invite::REPONSES[$i->reponse], $i->presents, implode(', ', $i->noms_presents ?? [])],
                    $moments->map(fn ($m) => $i->reponse === 'oui' && in_array($m->cle, $i->moments ?? [], true) ? 'oui' : '')->all(),
                    [$i->regimes, $i->message, $i->telephone, $i->email, $i->lien()]
                ), ';');
            }

            fclose($f);
        }, $nom, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function valider(Request $request): array
    {
        $donnees = $request->validate([
            'nom'       => ['required', 'string', 'max:255'],
            'places'    => ['required', 'integer', 'min:1', 'max:20'],
            'telephone' => ['nullable', 'string', 'max:40'],
            'email'     => ['nullable', 'email', 'max:255'],
            'groupe'    => ['nullable', 'string', 'max:60'],
            'reponse'   => ['nullable', Rule::in(array_keys(Invite::REPONSES))],
        ], [], ['nom' => 'nom du foyer']);

        // Une réponse saisie à la main par les mariés (un invité qui a
        // répondu de vive voix) ; absente, on ne touche pas à l'existante.
        if (empty($donnees['reponse'])) {
            unset($donnees['reponse']);
        } elseif ($donnees['reponse'] === 'oui') {
            $donnees['presents'] = $donnees['places'];
            $donnees['repondu_le'] = now();
        } else {
            $donnees['presents'] = 0;
            $donnees['repondu_le'] = $donnees['reponse'] === 'non' ? now() : null;
        }

        return $donnees;
    }
}
