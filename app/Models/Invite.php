<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Un foyer invité, et sa réponse. */
class Invite extends Model
{
    use BelongsToEvent;

    public const REPONSES = [
        'attente' => 'En attente',
        'oui'     => 'Présent',
        'non'     => 'Absent',
    ];

    protected $fillable = [
        'nom', 'email', 'telephone', 'groupe', 'places',
        'reponse', 'presents', 'noms_presents', 'moments', 'regimes', 'message',
        'repondu_le', 'relance_le', 'source',
    ];

    protected function casts(): array
    {
        return [
            'places'        => 'integer',
            'presents'      => 'integer',
            'noms_presents' => 'array',
            'moments'       => 'array',
            'repondu_le'    => 'datetime',
            'relance_le'    => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $invite) {
            // Un code court, sans caractère ambigu : il peut être recopié à la main.
            do {
                $code = Str::upper(Str::random(8));
                $code = strtr($code, ['0' => 'X', 'O' => 'Y', '1' => 'Z', 'I' => 'W', 'L' => 'K']);
            } while (static::tousEvenements()->where('code', $code)->exists());

            $invite->code ??= $code;
        });
    }

    public function aRepondu(): bool
    {
        return $this->reponse !== 'attente';
    }

    /** Le lien de réponse personnel du foyer. */
    public function lien(): string
    {
        return route('rsvp.personnel', $this->code);
    }
}
