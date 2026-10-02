<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une partie du mariage : mairie, cérémonie, vin d'honneur, dîner, soirée,
 * brunch du lendemain. Chacune a son lieu, son horaire et son déroulé.
 */
class EventPart extends Model
{
    use BelongsToEvent;

    /** Parties proposées par défaut à la création d'un mariage. */
    public const CLES = [
        'mairie'       => 'Mairie',
        'ceremonie'    => 'Cérémonie',
        'vin-honneur'  => 'Vin d’honneur',
        'diner'        => 'Dîner',
        'soiree'       => 'Soirée',
        'brunch'       => 'Brunch du lendemain',
        'autre'        => 'Autre',
    ];

    /** Gabarits de livret disponibles. */
    public const TYPES_CEREMONIE = [
        'civil'        => 'Civile',
        'laique'       => 'Laïque',
        'catholique'   => 'Catholique',
        'evangelique'  => 'Évangélique',
        'autre'        => 'Autre',
    ];

    protected $fillable = [
        'event_id', 'cle', 'nom', 'description', 'icone', 'type_ceremonie',
        'lieu_nom', 'lieu_adresse', 'lieu_lat', 'lieu_lng',
        'accueil_at', 'debut_at', 'fin_at',
        'ordre', 'actif',
    ];

    protected function casts(): array
    {
        return [
            'accueil_at' => 'datetime',
            'debut_at'   => 'datetime',
            'fin_at'     => 'datetime',
            'actif'      => 'boolean',
            'ordre'      => 'integer',
            'lieu_lat'   => 'decimal:7',
            'lieu_lng'   => 'decimal:7',
        ];
    }

    public function etapes(): HasMany
    {
        return $this->hasMany(EtapeCeremonie::class)->orderBy('ordre');
    }

    public function scopeActives(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    /** L'étape en cours de cette partie, s'il y en a une. */
    public function etapeEnCours(): ?EtapeCeremonie
    {
        return $this->etapes()->where('en_cours', true)->first();
    }

    public function enCours(): bool
    {
        return $this->debut_at
            && $this->debut_at->isPast()
            && (! $this->fin_at || $this->fin_at->isFuture());
    }

    /**
     * L'itinéraire vers le lieu. Un lien Google Maps universel : il ouvre
     * l'application de cartes sur téléphone et le site sur ordinateur, là
     * où un lien « geo: » ne faisait rien.
     */
    public function lienCarte(): ?string
    {
        if ($this->lieu_lat && $this->lieu_lng) {
            return "https://www.google.com/maps/dir/?api=1&destination={$this->lieu_lat},{$this->lieu_lng}";
        }

        $adresse = trim(collect([$this->lieu_nom, $this->lieu_adresse])->filter()->implode(', '));

        return $this->lieu_adresse
            ? 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($adresse)
            : null;
    }
}
