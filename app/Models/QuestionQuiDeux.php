<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;

class QuestionQuiDeux extends Model
{
    use BelongsToEvent;

    protected $table = 'questions_qui_deux';

    protected $fillable = [
        'question',
        'bonne_reponse',
        'active',
        'ordre',
    ];

    protected $casts = ['active' => 'boolean'];

    /** Posée aux invités : active, et les mariés ont désigné la réponse. */
    public function scopePretes($query)
    {
        return $query->where('active', true)->whereNotNull('bonne_reponse')->orderBy('ordre')->orderBy('id');
    }

    public function reponses()
    {
        return $this->hasMany(ReponseQuiDeux::class, 'question_id');
    }
}