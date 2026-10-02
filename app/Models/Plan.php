<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Une formule commerciale : un prix, des limites, un panier de modules.
 */
class Plan extends Model
{
    protected $fillable = [
        'cle', 'nom', 'accroche', 'description', 'prix',
        'limites', 'populaire', 'actif', 'ordre',
    ];

    protected function casts(): array
    {
        return [
            'limites'   => 'array',
            'populaire' => 'boolean',
            'actif'     => 'boolean',
            'prix'      => 'integer',
            'ordre'     => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'cle';
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class);
    }

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('actif', true)->orderBy('ordre');
    }

    public function estGratuit(): bool
    {
        return $this->prix === 0;
    }

    /**
     * Une limite de la formule : nombre de photos, d'invités, mois d'archive.
     * `null` signifie « sans limite ».
     */
    public function limite(string $cle): ?int
    {
        return $this->limites[$cle] ?? null;
    }
}
