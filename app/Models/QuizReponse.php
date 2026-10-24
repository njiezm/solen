<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;

/** Une réponse au quiz en direct. */
class QuizReponse extends Model
{
    use BelongsToEvent;

    protected $table = 'quiz_reponses';

    protected $fillable = ['participant_id', 'question', 'choix', 'juste'];

    protected $casts = ['juste' => 'boolean', 'question' => 'integer', 'choix' => 'integer'];

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }
}
