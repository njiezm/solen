<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;

class JeuQuiDeux extends Model
{
    use BelongsToEvent;

    protected $table = 'jeu_qui_deux';

    protected $fillable = [
        'participant_id',
        'question',
        'reponse'
    ];

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }
}
