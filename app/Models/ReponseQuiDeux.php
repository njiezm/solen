<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;

class ReponseQuiDeux extends Model
{
    use BelongsToEvent;

    protected $table = 'reponses_qui_deux';

    protected $fillable = [
        'participant_id',
        'question_id',
        'reponse',
        'correct',
        'session_jeu_id'
    ];

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function question()
    {
        return $this->belongsTo(QuestionQuiDeux::class, 'question_id');
    }

    public function sessionJeu()
    {
        return $this->belongsTo(SessionJeu::class, 'session_jeu_id');
    }
}