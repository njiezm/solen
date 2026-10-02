<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Éditeur de thèmes.
 *
 * C'est ce qui rend la prestation « thème sur mesure » rentable : trois
 * couleurs, deux polices, cinq minutes — au lieu d'une feuille de style
 * écrite à la main pour chaque client.
 */
class ThemeController extends Controller
{
    /** Polices proposées, toutes disponibles sur Google Fonts. */
    private const POLICES = [
        "'Fraunces', Georgia, serif"          => 'Fraunces — serif contemporaine',
        "'Playfair Display', Georgia, serif"  => 'Playfair Display — serif classique',
        "'Cormorant Garamond', Georgia, serif" => 'Cormorant Garamond — serif fine',
        "'Great Vibes', cursive"              => 'Great Vibes — manuscrite',
        "'Inter', sans-serif"                 => 'Inter — sans-serif neutre',
        "'Montserrat', sans-serif"            => 'Montserrat — sans-serif géométrique',
        "'Lora', Georgia, serif"              => 'Lora — serif lisible',
    ];

    public function index(): View
    {
        return view('console.themes', [
            'themes' => Theme::orderBy('ordre')->withCount('events')->get(),
        ]);
    }

    public function creer(): View
    {
        return view('console.theme', $this->referentiels(new Theme([
            'ink'        => '#1B1B2F',
            'surface'    => '#FCFAF7',
            'accent'     => '#C99B63',
            'secondaire' => '#8A7A63',
            'forme'      => 'doux',
            'caractere'  => 'classique',
            'densite'    => 'confortable',
            'actif'      => true,
        ])));
    }

    public function editer(Theme $theme): View
    {
        return view('console.theme', $this->referentiels($theme));
    }

    /** @return array<string, mixed> */
    private function referentiels(Theme $theme): array
    {
        return [
            'theme'      => $theme,
            'polices'    => self::POLICES,
            'formes'      => collect(Theme::FORMES)->map(fn ($f) => $f['nom'])->all(),
            'caracteres'  => collect(Theme::CARACTERES)->map(fn ($c) => $c['nom'])->all(),
            'densites'    => collect(Theme::DENSITES)->map(fn ($d) => $d['nom'])->all(),
            'traitements' => collect(Theme::TRAITEMENTS)->map(fn ($t) => $t['nom'])->all(),
        ];
    }

    public function enregistrer(Request $request, ?Theme $theme = null): RedirectResponse
    {
        $donnees = $request->validate([
            'nom'          => ['required', 'string', 'max:60'],
            'ink'          => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'surface'      => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondaire'   => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'fond'         => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'forme'        => ['required', Rule::in(array_keys(Theme::FORMES))],
            'caractere'    => ['required', Rule::in(array_keys(Theme::CARACTERES))],
            'densite'      => ['required', Rule::in(array_keys(Theme::DENSITES))],
            'traitement'   => ['required', Rule::in(array_keys(Theme::TRAITEMENTS))],
            'font_display' => ['required', Rule::in(array_keys(self::POLICES))],
            'font_body'    => ['required', Rule::in(array_keys(self::POLICES))],
            'actif'        => ['nullable', 'boolean'],
            'event_id'     => ['nullable', 'exists:events,id'],
        ], [], [
            'nom'          => 'nom du thème',
            'ink'          => 'couleur d’encre',
            'surface'      => 'couleur de fond',
            'accent'       => 'couleur d’accent',
            'secondaire'   => 'couleur secondaire',
            'fond'         => 'fond de page',
            'forme'        => 'forme des éléments',
            'caractere'    => 'caractère',
            'densite'      => 'densité',
            'traitement'   => 'traitement photographique',
            'font_display' => 'police des titres',
            'font_body'    => 'police du texte',
        ]);

        $donnees['actif'] = $request->boolean('actif');

        if ($theme?->exists) {
            $theme->update($donnees);
            $message = "Le thème « {$theme->nom} » a été mis à jour.";
        } else {
            $donnees['cle']   = $this->cleLibre($donnees['nom']);
            $donnees['ordre'] = (Theme::max('ordre') ?? 0) + 1;
            $theme = Theme::create($donnees);
            $message = "Le thème « {$theme->nom} » a été créé.";
        }

        return redirect()->route('console.themes')->with('ok', $message);
    }

    public function supprimer(Theme $theme): RedirectResponse
    {
        if ($theme->events()->exists()) {
            return back()->with('erreur',
                "« {$theme->nom} » est utilisé par un mariage : désactivez-le plutôt que de le supprimer.");
        }

        $theme->delete();

        return redirect()->route('console.themes')->with('ok', 'Le thème a été supprimé.');
    }

    private function cleLibre(string $nom): string
    {
        $base  = Str::slug($nom) ?: 'theme';
        $essai = $base;
        $n     = 1;

        while (Theme::where('cle', $essai)->exists()) {
            $essai = $base . '-' . (++$n);
        }

        return $essai;
    }
}
