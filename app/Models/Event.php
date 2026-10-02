<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Un mariage. Le locataire de la plateforme.
 *
 * Ce modèle n'utilise volontairement PAS le trait BelongsToEvent : c'est lui,
 * la racine.
 */
class Event extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUT_BROUILLON = 'brouillon';
    public const STATUT_PUBLIE    = 'publie';
    public const STATUT_ARCHIVE   = 'archive';

    protected $fillable = [
        'uuid', 'slug', 'domaine',
        'nom', 'partenaire_1', 'partenaire_2', 'hashtag',
        'date_principale', 'timezone', 'lieu_ville', 'lieu_pays',
        'theme_id', 'couleurs',
        'plan', 'statut', 'est_demo', 'code_acces',
        'publie_at', 'archive_at',
        'accompagnement', 'notes_internes', 'logo',
    ];

    /**
     * Ce que l'équipe Solen peut prendre en charge pour le couple. Les
     * étapes correspondantes s'affichent « Solen s'en occupe » côté mariés,
     * et deviennent la liste de tâches de l'équipe dans la console.
     */
    public const ACCOMPAGNEMENTS = [
        'programme'   => 'Programme, lieux et horaires',
        'contenu'     => 'Textes du site : histoire, infos pratiques, menu',
        'apparence'   => 'Thème, couverture et photos',
        'livret'      => 'Mise en page du livret de cérémonie',
        'jeux'        => 'Préparation des jeux',
        'impressions' => 'QR codes et supports imprimés',
        'moderation'  => 'Modération des photos et des messages',
    ];

    protected function casts(): array
    {
        return [
            'date_principale' => 'date',
            'couleurs'        => 'array',
            'est_demo'        => 'boolean',
            'accompagnement'  => 'array',
            'publie_at'       => 'datetime',
            'archive_at'      => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $event) {
            $event->uuid ??= (string) Str::uuid();
            $event->slug ??= Str::slug($event->nom);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // --- Relations ------------------------------------------------------

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(EventPart::class)->orderBy('ordre');
    }

    public function organisateurs(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /** Les comptes qui gèrent ce mariage : les mariés et leurs aides. */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    /**
     * Les modules du mariage. Un module retiré du catalogue (disponible à
     * faux) disparaît partout d'un coup, sans perdre ses réglages.
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class)
            ->where('modules.disponible', true)
            ->withPivot(['actif', 'config', 'ordre'])
            ->withTimestamps();
    }

    public function blocs(): HasMany
    {
        return $this->hasMany(ContentBlock::class)->orderBy('ordre');
    }

    public function formule(): ?Plan
    {
        return Plan::where('cle', $this->plan)->first();
    }

    // --- Modules --------------------------------------------------------

    /**
     * Les modules actifs, chargés une seule fois par requête : les vues
     * interrogent ce cache en boucle pour savoir quoi afficher.
     *
     * @var \Illuminate\Support\Collection<string, Module>|null
     */
    protected ?\Illuminate\Support\Collection $modulesActifs = null;

    /** @return \Illuminate\Support\Collection<string, Module> */
    public function modulesActifs(): \Illuminate\Support\Collection
    {
        return $this->modulesActifs ??= $this->modules()
            ->wherePivot('actif', true)
            ->get()
            ->keyBy('cle');
    }

    /**
     * Photos encore autorisées par la formule (galerie et photobooth
     * confondus). null : sans limite.
     */
    public function photosRestantes(): ?int
    {
        $plafond = $this->formule()?->limite('photos');

        if ($plafond === null) {
            return null;
        }

        return max(0, $plafond - Photo::where('event_id', $this->id)->count());
    }

    /** L'équipe Solen s'occupe-t-elle de cette partie ? */
    public function accompagne(string $cle): bool
    {
        return in_array($cle, (array) $this->accompagnement, true);
    }

    public function aModule(string $cle): bool
    {
        return $this->modulesActifs()->has($cle);
    }

    /**
     * Le photobooth passe en borne fixe (plein écran verrouillé, enchaînement
     * automatique) sur la formule haute uniquement. Ailleurs il reste en mode
     * simple : chacun le lance depuis son propre téléphone.
     */
    public function photoboothEnBorne(): bool
    {
        return $this->plan === 'signature';
    }

    /**
     * Active les modules de la formule sans rien écraser : les modules déjà
     * présents gardent leurs réglages et leur état, seuls les nouveaux sont
     * ajoutés. Permet de faire monter un client de formule sans perte.
     *
     * @return int Nombre de modules ajoutés.
     */
    public function appliquerFormule(): int
    {
        $formule = $this->formule();

        if (! $formule) {
            return 0;
        }

        $dejaLa  = \Illuminate\Support\Facades\DB::table('event_module')
            ->where('event_id', $this->id)
            ->pluck('module_id')
            ->all();
        $aAjouter = $formule->modules()
            ->where('modules.disponible', true)
            ->whereNotIn('modules.id', $dejaLa)
            ->get();

        foreach ($aAjouter as $module) {
            $this->modules()->attach($module->id, [
                'actif'  => $module->statut === 'live',
                'config' => json_encode($module->valeursParDefaut()),
                'ordre'  => $module->ordre,
            ]);
        }

        $this->modulesActifs = null;

        return $aAjouter->count();
    }

    /**
     * Un réglage de module, avec repli sur la valeur par défaut déclarée
     * dans le schéma puis sur celle fournie par l'appelant.
     */
    /**
     * Enregistre des réglages d'un module sans toucher aux autres clés.
     *
     * @param  array<string, mixed>  $valeurs
     */
    public function ecrireReglages(string $module, array $valeurs): void
    {
        $actif = $this->modules()->where('cle', $module)->first();

        if (! $actif) {
            return;
        }

        $config = $actif->pivot->config ?? [];
        $config = is_string($config) ? (json_decode($config, true) ?: []) : (array) $config;

        $this->modules()->updateExistingPivot($actif->id, [
            'config' => json_encode(array_merge($config, $valeurs)),
        ]);

        $this->modulesActifs = null;
    }

    public function reglage(string $module, string $cle, mixed $defaut = null): mixed
    {
        $actif = $this->modulesActifs()->get($module);

        if (! $actif) {
            return $defaut;
        }

        $config = $actif->pivot->config ?? [];

        if (is_string($config)) {
            $config = json_decode($config, true) ?: [];
        }

        if (array_key_exists($cle, $config)) {
            return $config[$cle];
        }

        return $actif->valeursParDefaut()[$cle] ?? $defaut;
    }

    // --- Portées --------------------------------------------------------

    public function scopePublies(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_PUBLIE);
    }

    public function scopeDemos(Builder $query): Builder
    {
        return $query->where('est_demo', true);
    }

    // --- Comportement ---------------------------------------------------

    public function estPublie(): bool
    {
        return $this->statut === self::STATUT_PUBLIE;
    }

    /**
     * La date du mariage. Volontairement sans conversion de fuseau : la
     * valeur est castée à minuit, et la convertir ferait reculer l'affichage
     * d'un jour pour la Martinique (UTC−4). Le fuseau ne concerne que les
     * horaires, portés par les EventPart.
     */
    public function dateLocale(): ?Carbon
    {
        return $this->date_principale;
    }

    /** Un instant du mariage, exprimé dans le fuseau du lieu. */
    public function enHeureLocale(?Carbon $instant): ?Carbon
    {
        return $instant?->copy()->setTimezone($this->timezone);
    }

    /** Le prochain moment à annoncer : accueil, début ou fin de la partie en cours. */
    public function prochainJalon(): ?EventPart
    {
        return $this->parts()
            ->where('actif', true)
            ->whereNotNull('debut_at')
            ->where('debut_at', '>=', now())
            ->orderBy('debut_at')
            ->first();
    }

    /**
     * Les variables CSS du site invité : thème choisi, puis surcharges
     * propres à l'événement.
     */
    /**
     * Les retouches du couple sur son thème : accent, police des titres,
     * forme des angles. Stockées en clair dans `couleurs`, traduites ici en
     * jetons. Le contraste reste garanti : les paires de texte sont
     * recalculées à partir de l'accent retouché.
     *
     * @return array<string, string>
     */
    public function surchargesTheme(): array
    {
        $perso = (array) ($this->couleurs ?? []);
        $jetons = [];

        if (! empty($perso['accent']) && \App\Solen\Contraste::estHex($perso['accent'])) {
            $jetons['--theme-accent'] = $perso['accent'];
        }

        if (! empty($perso['police']) && isset(Theme::POLICES_TITRE[$perso['police']])) {
            $jetons['--theme-display'] = Theme::POLICES_TITRE[$perso['police']]['pile'];
        }

        if (! empty($perso['forme']) && isset(Theme::FORMES[$perso['forme']])) {
            $forme = Theme::FORMES[$perso['forme']];
            $jetons += ['--theme-r-carte' => $forme['carte'], '--theme-r-bouton' => $forme['bouton'], '--theme-r-image' => $forme['image']];
        }

        return $jetons;
    }

    /** Tous les jetons du mariage : le thème, retouché par le couple. */
    public function jetons(): array
    {
        return $this->theme?->jetons($this->surchargesTheme()) ?? [];
    }

    public function styleInline(): string
    {
        return collect($this->jetons())
            ->map(fn ($valeur, $cle) => "{$cle}:{$valeur}")
            ->implode(';');
    }

    /** La police des titres réellement utilisée, retouche comprise. */
    public function policeTitre(): ?string
    {
        return $this->jetons()['--theme-display'] ?? null;
    }

    /** L'URL publique du mariage : domaine personnalisé, sinon sous-domaine. */
    public function url(): string
    {
        if ($this->domaine) {
            return 'https://' . $this->domaine;
        }

        $racine = config('solen.domaine_racine', parse_url(config('app.url'), PHP_URL_HOST));

        return "https://{$this->slug}.{$racine}";
    }
}
