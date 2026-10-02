<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Une brique fonctionnelle du catalogue.
 *
 * La colonne `champs` porte la description de ses réglages : c'est elle qui
 * permet à l'administration de générer le formulaire sans qu'aucune vue ne
 * soit écrite pour ce module en particulier.
 */
class Module extends Model
{
    protected $fillable = [
        'cle', 'nom', 'description', 'phase', 'icone',
        'statut', 'vedette', 'disponible', 'ordre', 'champs',
    ];

    protected function casts(): array
    {
        return [
            'champs'     => 'array',
            'vedette'    => 'boolean',
            'disponible' => 'boolean',
            'ordre'      => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'cle';
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->withPivot(['actif', 'config', 'ordre'])
            ->withTimestamps();
    }

    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->where('disponible', true);
    }

    /** Les modules réellement livrés, par opposition à ceux encore annoncés. */
    public function scopeOperationnels(Builder $query): Builder
    {
        return $query->where('statut', 'live');
    }

    public function scopePhase(Builder $query, string $phase): Builder
    {
        return $query->where('phase', $phase);
    }

    /** A-t-il des réglages à proposer ? */
    public function reglable(): bool
    {
        return ! empty($this->champs);
    }

    /**
     * Les valeurs par défaut de ses réglages, telles que déclarées dans
     * config/solen_schema.php.
     *
     * @return array<string, mixed>
     */
    public function valeursParDefaut(): array
    {
        return collect($this->champs ?? [])
            ->filter(fn ($champ) => array_key_exists('defaut', $champ))
            ->mapWithKeys(fn ($champ) => [$champ['cle'] => $champ['defaut']])
            ->all();
    }
}
