<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChassePhoto extends Model
{
    use BelongsToEvent;

    protected $fillable = [
        'participant_id',
        'session_jeu_id',
        'indice',
        'photo_path',
        'valide'
    ];

    /**
     * Obtenir le participant qui a soumis la photo.
     */
    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * Obtenir la session de jeu associée.
     */
    public function sessionJeu()
    {
        return $this->belongsTo(SessionJeu::class, 'session_jeu_id');
    }

    /**
     * Obtenir l'URL complète de la photo.
     */
    public function getPhotoUrlAttribute()
    {
        return Storage::url($this->photo_path);
    }
}