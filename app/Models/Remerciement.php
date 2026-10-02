<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;

class Remerciement extends Model
{
    use BelongsToEvent;

    protected $fillable = [
        'event_id', 'titre', 'contenu', 'signatures',
    ];
}
