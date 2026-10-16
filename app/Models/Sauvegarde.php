<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ce qu'une remise à zéro a effacé, gardé de côté pour être restauré.
 * Hors cloisonnement : c'est un outil de la console.
 */
class Sauvegarde extends Model
{
    /** Jours pendant lesquels une sauvegarde reste restaurable. */
    public const DUREE_JOURS = 30;

    protected $fillable = ['event_id', 'quoi', 'libelle', 'lignes', 'fichiers', 'nombre', 'restauree_le'];

    protected function casts(): array
    {
        return [
            'lignes'       => 'array',
            'fichiers'     => 'array',
            'restauree_le' => 'datetime',
        ];
    }

    /** Le dossier privé où dorment ses fichiers. */
    public function dossier(): string
    {
        return "corbeille/{$this->id}";
    }

    public function expireLe(): \Illuminate\Support\Carbon
    {
        return $this->created_at->copy()->addDays(self::DUREE_JOURS);
    }

    public function restaurable(): bool
    {
        return ! $this->restauree_le && $this->expireLe()->isFuture();
    }
}
