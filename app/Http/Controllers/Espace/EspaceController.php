<?php

namespace App\Http\Controllers\Espace;

use App\Http\Controllers\Controller;
use App\Models\ChassePhoto;
use App\Models\LivreOr;
use App\Models\Participant;
use App\Models\Photo;
use App\Models\Theme;
use App\Solen\AvancementMariage;
use App\Solen\CurrentEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tableau de bord et informations générales du mariage.
 */
class EspaceController extends Controller
{
    public function index(CurrentEvent $courant, AvancementMariage $avancement): View
    {
        $event = $courant->get();

        return view('espace.index', [
            'chiffres' => [
                ['nombre' => Participant::count(),          'quoi' => 'invités identifiés'],
                ['nombre' => Photo::count() + ChassePhoto::count(), 'quoi' => 'photos partagées'],
                ['nombre' => LivreOr::count(),              'quoi' => 'messages reçus'],
                ['nombre' => $event->modulesActifs()->count(), 'quoi' => 'modules actifs'],
            ],
            'avancement' => $avancement->pour($event),
        ]);
    }

    public function informations(CurrentEvent $courant): View
    {
        return view('espace.informations', [
            'themes'   => Theme::disponiblesPour($courant->get())->get(),
            'fuseaux'  => $this->fuseaux(),
        ]);
    }

    public function enregistrerInformations(Request $request, CurrentEvent $courant): RedirectResponse
    {
        $event = $courant->get();

        $donnees = $request->validate([
            'nom'             => ['required', 'string', 'max:120'],
            'partenaire_1'    => ['nullable', 'string', 'max:60'],
            'partenaire_2'    => ['nullable', 'string', 'max:60'],
            'hashtag'         => ['nullable', 'string', 'max:60'],
            'date_principale' => ['nullable', 'date'],
            'timezone'        => ['required', 'string', 'in:' . implode(',', array_keys($this->fuseaux()))],
            'lieu_ville'      => ['nullable', 'string', 'max:120'],
            'lieu_pays'       => ['nullable', 'string', 'max:80'],
            // Obligatoire : sans thème, le site perd toutes ses couleurs.
            'theme_id'        => ['required', 'exists:themes,id'],
            'statut'          => ['required', 'in:brouillon,publie,archive'],
            'perso.accent'    => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'perso.police'    => ['nullable', \Illuminate\Validation\Rule::in(array_keys(\App\Models\Theme::POLICES_TITRE))],
            'perso.forme'     => ['nullable', \Illuminate\Validation\Rule::in(array_keys(\App\Models\Theme::FORMES))],
        ], [], [
            'nom'             => 'nom du mariage',
            'date_principale' => 'date',
            'timezone'        => 'fuseau horaire',
        ]);

        // Les retouches du thème : seules les valeurs choisies sont gardées.
        $perso = (array) $request->input('perso', []);
        $donnees['couleurs'] = array_filter([
            'accent' => $request->boolean('perso.accent_actif') ? ($perso['accent'] ?? null) : null,
            'police' => $perso['police'] ?? null,
            'forme'  => $perso['forme'] ?? null,
        ]) ?: null;
        unset($donnees['perso']);

        if ($donnees['statut'] === 'publie' && ! $event->publie_at) {
            $donnees['publie_at'] = now();
        }

        $event->update($donnees);

        return back()->with('ok', 'Vos informations ont été enregistrées.');
    }

    /** @return array<string, string> */
    private function fuseaux(): array
    {
        return [
            'Europe/Paris'       => 'France métropolitaine (Paris)',
            'America/Martinique' => 'Martinique',
            'America/Guadeloupe' => 'Guadeloupe',
            'America/Cayenne'    => 'Guyane',
            'Indian/Reunion'     => 'La Réunion',
            'Europe/Brussels'    => 'Belgique',
            'Europe/Zurich'      => 'Suisse',
            'America/Toronto'    => 'Canada (Est)',
        ];
    }
}
