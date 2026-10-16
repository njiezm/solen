<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use BelongsToEvent;

    protected $fillable = [
        'participant_id',
        'path',
        'publie',
    ];

    protected $casts = ['publie' => 'boolean'];

    public function scopePublies($query)
    {
        return $query->where('publie', true);
    }

    /** Les clichés pris au photobooth (rangés à part sur le disque). */
    public function scopeDuPhotobooth($query)
    {
        return $query->where('path', 'like', '%/photobooth/%');
    }

    /**
     * Les photos telles que la visionneuse les fait défiler : adresse et
     * prénom de l'auteur, dans l'ordre de la grille.
     *
     * @param  iterable<Photo>  $photos
     * @return list<array{0: string, 1: ?string}>
     */
    public static function diaporama(iterable $photos): array
    {
        return collect($photos)
            ->map(fn (self $p) => [\Illuminate\Support\Facades\Storage::url($p->path), $p->participant?->prenom])
            ->values()
            ->all();
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }
}
