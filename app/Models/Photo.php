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

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }
}
