<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chant extends Model
{
    use BelongsToEvent;

    protected $fillable = [
        'event_id', 'etape_ceremonie_id', 'titre', 'paroles', 'auteur', 'ordre',
    ];

    protected function casts(): array
    {
        return ['ordre' => 'integer'];
    }

    public function etape(): BelongsTo
    {
        return $this->belongsTo(EtapeCeremonie::class, 'etape_ceremonie_id');
    }

    /** Les couplets, pour l'affichage en mode défilement pendant la cérémonie. */
    public function couplets(): array
    {
        return preg_split('/\R{2,}/', trim((string) $this->paroles)) ?: [];
    }
}
