<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;

class MotsCroises extends Model
{
    use BelongsToEvent;

    protected $fillable = [
        'titre',
        'description',
        'taille',
        'actif'
    ];

    public function mots()
    {
        return $this->hasMany(MotCroise::class, 'mots_croise_id');  // Spécifiez explicitement la clé étrangère
    }
}