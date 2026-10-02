<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lecture extends Model
{
    use BelongsToEvent;

    protected $fillable = [
        'event_id', 'etape_ceremonie_id', 'titre', 'reference', 'contenu', 'auteur', 'ordre',
    ];

    protected function casts(): array
    {
        return ['ordre' => 'integer'];
    }

    public function etape(): BelongsTo
    {
        return $this->belongsTo(EtapeCeremonie::class, 'etape_ceremonie_id');
    }
}
