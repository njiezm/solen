<?php

namespace App\Models\Concerns;

use App\Models\Event;
use App\Models\Scopes\EventScope;
use App\Solen\CurrentEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * À appliquer à tout modèle métier rattaché à un mariage.
 *
 * Fait deux choses : filtre les lectures sur le locataire courant, et
 * renseigne event_id à la création pour qu'aucun appel existant n'ait
 * besoin d'être modifié.
 */
trait BelongsToEvent
{
    public static function bootBelongsToEvent(): void
    {
        static::addGlobalScope(new EventScope);

        static::creating(function (Model $model) {
            if (empty($model->event_id)) {
                $model->event_id = app(CurrentEvent::class)->id();
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** Sortir du cloisonnement : administration transverse, seeders, statistiques. */
    public function scopeTousEvenements(Builder $query): Builder
    {
        return $query->withoutGlobalScope(EventScope::class);
    }

    /** Cibler explicitement un autre mariage que celui de la requête. */
    public function scopePourEvenement(Builder $query, Event|int $event): Builder
    {
        return $query
            ->withoutGlobalScope(EventScope::class)
            ->where($this->qualifyColumn('event_id'), $event instanceof Event ? $event->id : $event);
    }
}
