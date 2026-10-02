<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ancienne table de la chasse photo, antérieure à `chasse_photos`.
 * Conservée le temps de la reprise de données, puis à supprimer.
 *
 * @deprecated Utiliser ChassePhoto.
 */
class JeuChassePhoto extends Model
{
    use BelongsToEvent;

    protected $fillable = [
        'event_id', 'participant_id', 'indice', 'photo_path',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
