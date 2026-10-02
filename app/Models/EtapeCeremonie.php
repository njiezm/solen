<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une étape du déroulé, rattachée à une partie du mariage
 * (mairie, cérémonie, vin d'honneur, soirée…).
 *
 * C'est le module vedette de Solen : ce que les invités regardent pour
 * savoir où on en est.
 */
class EtapeCeremonie extends Model
{
    use BelongsToEvent, HasFactory;

    protected $fillable = [
        'event_id', 'event_part_id',
        'titre', 'description', 'icone', 'ordre', 'en_cours', 'termine',
    ];

    protected function casts(): array
    {
        return [
            'en_cours' => 'boolean',
            'termine'  => 'boolean',
            'ordre'    => 'integer',
        ];
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(EventPart::class, 'event_part_id');
    }

    public function lectures(): HasMany
    {
        return $this->hasMany(Lecture::class)->orderBy('ordre');
    }

    public function chants(): HasMany
    {
        return $this->hasMany(Chant::class)->orderBy('ordre');
    }

    public function prieres(): HasMany
    {
        return $this->hasMany(Priere::class)->orderBy('ordre');
    }

    public function scopeOrdonnees(Builder $query): Builder
    {
        return $query->orderBy('ordre');
    }

    /**
     * Marque cette étape comme celle en cours, et clôt les précédentes.
     * Une seule étape peut être en cours à la fois pour un mariage donné.
     */
    public function demarrer(): void
    {
        static::query()
            ->where('id', '!=', $this->id)
            ->update(['en_cours' => false]);

        static::query()
            ->where('ordre', '<', $this->ordre)
            ->update(['termine' => true]);

        $this->update(['en_cours' => true, 'termine' => false]);
    }

    public function terminer(): void
    {
        $this->update(['en_cours' => false, 'termine' => true]);
    }

    public function statut(): string
    {
        return match (true) {
            $this->en_cours => 'en_cours',
            $this->termine  => 'termine',
            default         => 'a_venir',
        };
    }
}
