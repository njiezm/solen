<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;

class SessionJeu extends Model
{
    use BelongsToEvent;

    protected $table = 'sessions_jeu';

    protected $fillable = [
        'nom',
        'description',
        'type_jeu',
        'actif',
        'debut',
        'fin'
    ];

    protected $casts = [
        'debut' => 'datetime',
        'fin' => 'datetime',
    ];

    public function reponses()
    {
        return $this->hasMany(ReponseQuiDeux::class, 'session_jeu_id');
    }

    public function chassePhotos()
{
    return $this->hasMany(ChassePhoto::class, 'session_jeu_id');
}
}

