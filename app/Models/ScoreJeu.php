<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Le meilleur score d'un invité à un jeu. Alimente le classement général. */
class ScoreJeu extends Model
{
    use BelongsToEvent;

    protected $table = 'scores_jeu';

    protected $fillable = ['participant_id', 'type_jeu', 'points', 'detail'];

    protected $casts = [
        'points' => 'integer',
        'detail' => 'array',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
