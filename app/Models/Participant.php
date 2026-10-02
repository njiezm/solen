<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    use BelongsToEvent;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'equipe'
    ];

    public function reponses()
    {
        return $this->hasMany(ReponseQuiDeux::class);
    }
}