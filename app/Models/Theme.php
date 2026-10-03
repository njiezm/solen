<?php

namespace App\Models;

use App\Solen\Contraste;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un univers visuel complet : couleurs, typographie, formes, caractère.
 *
 * Volontairement plus qu'une palette : deux mariages aux mêmes couleurs mais
 * l'un anguleux et sobre, l'autre arrondi et festif, ne se ressemblent pas.
 */
class Theme extends Model
{
    /** Arrondi des cartes, boutons et images. */
    public const FORMES = [
        'droit'  => ['nom' => 'Anguleux',  'carte' => '2px',  'bouton' => '4px',   'image' => '2px'],
        'doux'   => ['nom' => 'Doux',      'carte' => '14px', 'bouton' => '10px',  'image' => '12px'],
        'rond'   => ['nom' => 'Arrondi',   'carte' => '26px', 'bouton' => '999px', 'image' => '22px'],
        'pilule' => ['nom' => 'Très rond', 'carte' => '34px', 'bouton' => '999px', 'image' => '999px'],
    ];

    /** Caractère typographique : ce qui distingue le sobre du festif. */
    public const CARACTERES = [
        'epure'     => ['nom' => 'Épuré',     'interlettrage' => '0',      'capitales' => 'none',      'graisse' => '500', 'ornement' => 'aucun'],
        'classique' => ['nom' => 'Classique', 'interlettrage' => '.02em',  'capitales' => 'none',      'graisse' => '600', 'ornement' => 'filet'],
        'festif'    => ['nom' => 'Festif',    'interlettrage' => '.12em',  'capitales' => 'uppercase', 'graisse' => '700', 'ornement' => 'double'],
    ];

    /**
     * Polices de titre proposées en retouche. Toutes sont chargées par la
     * feuille du site : aucune ne peut tomber en police système.
     */
    public const POLICES_TITRE = [
        'fraunces'  => ['nom' => 'Fraunces — moderne et chaleureuse', 'pile' => "'Fraunces', Georgia, serif"],
        'cormorant' => ['nom' => 'Cormorant — fine et élégante',      'pile' => "'Cormorant Garamond', Georgia, serif"],
        'playfair'  => ['nom' => 'Playfair — classique et contrastée', 'pile' => "'Playfair Display', Georgia, serif"],
        'lora'      => ['nom' => 'Lora — douce et lisible',            'pile' => "'Lora', Georgia, serif"],
        'vibes'     => ['nom' => 'Great Vibes — calligraphiée',        'pile' => "'Great Vibes', cursive"],
        'inter'     => ['nom' => 'Inter — sans empattement, sobre',    'pile' => "'Inter', sans-serif"],
        'montserrat'=> ['nom' => 'Montserrat — géométrique',           'pile' => "'Montserrat', sans-serif"],
    ];

    /** Densité : l'air entre les éléments. */
    public const DENSITES = [
        'compacte'    => ['nom' => 'Compacte',    'echelle' => '.85'],
        'confortable' => ['nom' => 'Confortable', 'echelle' => '1'],
        'aeree'       => ['nom' => 'Aérée',       'echelle' => '1.25'],
    ];

    /**
     * Traitement appliqué à toutes les photos du site.
     *
     * Les mêmes clichés en argentique ou en noir et blanc racontent deux
     * mariages différents : c'est le levier de différenciation le plus fort,
     * devant les couleurs et les formes.
     */
    public const TRAITEMENTS = [
        'naturel'    => ['nom' => 'Naturel',        'filtre' => 'none'],
        'argentique' => ['nom' => 'Argentique',     'filtre' => 'sepia(.18) saturate(.88) contrast(1.06)'],
        'noir_blanc' => ['nom' => 'Noir et blanc',  'filtre' => 'grayscale(1) contrast(1.08)'],
        'chaud'      => ['nom' => 'Chaleureux',     'filtre' => 'saturate(1.18) sepia(.08) brightness(1.03)'],
        'doux'       => ['nom' => 'Doux',           'filtre' => 'saturate(.82) brightness(1.06) contrast(.94)'],
    ];

    /**
     * Emplacements des décors, en marge des pages. Le miroir permet de
     * reprendre la même fleur dans le coin opposé.
     */
    public const EMPLACEMENTS_DECOR = ['haut-gauche', 'haut-droite', 'bas-gauche', 'bas-droite'];

    protected $fillable = [
        'cle', 'nom', 'ink', 'surface', 'fond', 'accent', 'secondaire',
        'forme', 'caractere', 'densite', 'traitement', 'decors',
        'font_display', 'font_body', 'actif', 'ordre', 'event_id',
    ];

    /** Les thèmes du catalogue, visibles de tous : vitrine, commande. */
    public function scopePublics($query)
    {
        return $query->where('actif', true)->whereNull('event_id')->orderBy('ordre');
    }

    /** Ce qu'un couple peut choisir : le catalogue, plus son thème sur mesure. */
    public function scopeDisponiblesPour($query, ?Event $event)
    {
        return $query->where('actif', true)
            ->where(fn ($q) => $q->whereNull('event_id')->when($event, fn ($q) => $q->orWhere('event_id', $event->id)))
            ->orderByRaw('event_id is null')   // le thème sur mesure en tête
            ->orderBy('ordre');
    }

    public function estSurMesure(): bool
    {
        return (bool) $this->event_id;
    }

    protected function casts(): array
    {
        return [
            'actif'  => 'boolean',
            'ordre'  => 'integer',
            'decors' => 'array',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    // --- Jetons ---------------------------------------------------------

    /** @return array<string, string> */
    private function forme(): array
    {
        return self::FORMES[$this->forme] ?? self::FORMES['doux'];
    }

    /** @return array<string, string> */
    private function caractere(): array
    {
        return self::CARACTERES[$this->caractere] ?? self::CARACTERES['classique'];
    }

    /**
     * Les jetons de design, injectés en variables CSS sur le site invité.
     *
     * @param  array<string, string>  $surcharges  Couleurs propres à l'événement.
     * @return array<string, string>
     */
    public function jetons(array $surcharges = []): array
    {
        $forme     = $this->forme();
        $caractere = $this->caractere();
        $densite   = self::DENSITES[$this->densite] ?? self::DENSITES['confortable'];

        $jetons = array_filter(array_merge([
            // Couleurs
            '--theme-ink'         => $this->ink,
            '--theme-surface'     => $this->surface,
            '--theme-accent'      => $this->accent,
            '--theme-secondaire'  => $this->secondaire ?: $this->accent,
            '--theme-fond'        => $this->fondDePage(),

            // Typographie
            '--theme-display'     => $this->font_display,
            '--theme-body'        => $this->font_body,
            '--theme-lettrage'    => $caractere['interlettrage'],
            '--theme-capitales'   => $caractere['capitales'],
            '--theme-graisse'     => $caractere['graisse'],

            // Formes
            '--theme-r-carte'     => $forme['carte'],
            '--theme-r-bouton'    => $forme['bouton'],
            '--theme-r-image'     => $forme['image'],

            // Respiration
            '--theme-densite'     => $densite['echelle'],

            // Photographie
            '--theme-filtre'      => $this->traitementPhoto()['filtre'],
        ], $surcharges), fn ($v) => $v !== null && $v !== '');
        // Filtre explicite : un interlettrage « 0 » est une valeur, pas un vide.

        return $jetons + $this->paires(
            $jetons['--theme-ink'],
            $jetons['--theme-surface'],
            $jetons['--theme-accent'],
            $jetons['--theme-secondaire'],
            $jetons['--theme-fond'],
        );
    }

    /**
     * Chaque fond reçoit sa couleur de texte, calculée et non devinée.
     *
     * Le site invité ne lit que ces paires (--c-*) : un texte y est toujours
     * posé avec la couleur prévue pour son fond, si bien qu'aucun réglage de
     * thème ne peut produire un titre sombre sur fond sombre.
     *
     * @return array<string, string>
     */
    public function paires(string $ink, string $surface, string $accent, string $secondaire, string $fond): array
    {
        $ink        = Contraste::estHex($ink) ? $ink : '#1B1B2F';
        $surface    = Contraste::estHex($surface) ? $surface : '#FCFAF7';
        $accent     = Contraste::estHex($accent) ? $accent : '#C99B63';
        $secondaire = Contraste::estHex($secondaire) ? $secondaire : $accent;

        // Le fond de page : celui du thème s'il en déclare un, sinon la
        // surface elle-même. Une page claire, des cartes à peine plus claires.
        $page = Contraste::estHex($fond) ? $fond : $surface;
        $sombre = Contraste::estSombre($page);

        $carte = $sombre
            ? Contraste::melanger($page, '#FFFFFF', .07)
            : (Contraste::luminance($page) > .9 ? '#FFFFFF' : Contraste::melanger($page, '#FFFFFF', .7));

        // Le bloc « fort » : bandeaux, couverture sans photo, barre basse.
        // L'encre pour un thème clair, une teinte plus profonde que la page
        // pour un thème sombre.
        $fort = $sombre ? Contraste::melanger($page, $accent, .18) : $ink;

        $texte     = Contraste::texteSur($page, $ink);
        $texteDoux = Contraste::ajuster(Contraste::melanger($texte, $page, .32), $page);

        return [
            '--c-page'            => $page,
            '--c-sur-page'        => $texte,
            '--c-sur-page-doux'   => $texteDoux,
            '--c-carte'           => $carte,
            '--c-sur-carte'       => Contraste::texteSur($carte, $ink),
            '--c-sur-carte-doux'  => Contraste::ajuster(Contraste::melanger(Contraste::texteSur($carte, $ink), $carte, .32), $carte),
            '--c-bord'            => Contraste::melanger($page, $texte, .12),
            '--c-voile'           => Contraste::melanger($page, $texte, .05),
            '--c-fort'            => $fort,
            '--c-sur-fort'        => Contraste::texteSur($fort, $surface),
            '--c-sur-fort-doux'   => Contraste::ajuster(Contraste::melanger(Contraste::texteSur($fort, $surface), $fort, .25), $fort),
            '--c-accent'          => $accent,
            '--c-sur-accent'      => Contraste::texteSur($accent, $surface),
            // L'accent employé comme couleur de texte ou d'icône.
            '--c-accent-texte'    => Contraste::ajuster($accent, $page),
            '--c-accent-carte'    => Contraste::ajuster($accent, $carte),
            '--c-accent-fort'     => Contraste::ajuster($accent, $fort, Contraste::GRAPHIQUE),
            '--c-accent-pale'     => Contraste::melanger($carte, $accent, .14),
            '--c-secondaire'      => $secondaire,
            '--c-sur-secondaire'  => Contraste::texteSur($secondaire, $surface),
            '--c-schema'          => $sombre ? 'dark' : 'light',
        ];
    }

    /** @return array<string, string> */
    public function traitementPhoto(): array
    {
        return self::TRAITEMENTS[$this->traitement] ?? self::TRAITEMENTS['naturel'];
    }

    /**
     * Le fond de page. Déclaré par le thème quand il en a un ; sinon la
     * surface elle-même : une page claire, sur laquelle le texte se lit.
     */
    public function fondDePage(): string
    {
        return $this->fond ?: $this->surface;
    }

    /** Un thème sombre a besoin d'ajustements que le CSS seul ne devine pas. */
    public function estSombre(): bool
    {
        return $this->luminosite($this->surface) < 0.5;
    }

    /** Luminosité perçue d'une couleur hexadécimale, entre 0 et 1. */
    private function luminosite(string $hex): float
    {
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%2x%2x%2x');

        // Coefficients de la recommandation UIT-R BT.601.
        return (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    }

    /**
     * Les décors valides du thème : une image publique, un emplacement
     * connu, une largeur raisonnable. Le reste est ignoré sans bruit.
     *
     * @return list<array{image: string, emplacement: string, largeur: int, miroir: bool, opacite: float, debord: int}>
     */
    public function decorsValides(): array
    {
        return collect((array) $this->decors)
            ->filter(fn ($d) => ! empty($d['image']) && in_array($d['emplacement'] ?? null, self::EMPLACEMENTS_DECOR, true))
            ->map(fn ($d) => [
                'image'       => $d['image'],
                'emplacement' => $d['emplacement'],
                'largeur'     => max(40, min(480, (int) ($d['largeur'] ?? 180))),
                'miroir'      => (bool) ($d['miroir'] ?? false),
                'opacite'     => max(.1, min(1, (float) ($d['opacite'] ?? 1))),
                // Part de l'image qui sort de l'écran : une fleur coupée
                // par le bord paraît posée là, pas collée dans un coin.
                'debord'      => max(0, min(60, (int) ($d['debord'] ?? 0))),
            ])
            ->values()
            ->all();
    }

    /** Le nom de l'ornement, utilisé comme classe sur le body. */
    public function ornement(): string
    {
        return $this->caractere()['ornement'];
    }

    /** Résumé lisible, pour les listes de la console. */
    public function resume(): string
    {
        return implode(' · ', [
            self::FORMES[$this->forme]['nom']          ?? '—',
            self::CARACTERES[$this->caractere]['nom']  ?? '—',
            self::TRAITEMENTS[$this->traitement]['nom'] ?? '—',
        ]);
    }
}
