<?php

namespace App\Models\Scopes;

use App\Solen\CurrentEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Cloisonne chaque requête au mariage en cours.
 *
 * Sans événement résolu (console, seeders, tâches de fond), le scope ne
 * filtre rien : c'est le middleware ResolveEvent qui garantit qu'aucune
 * requête web n'arrive jusqu'ici sans locataire.
 */
class EventScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $eventId = app(CurrentEvent::class)->id();

        if ($eventId !== null) {
            $builder->where($model->qualifyColumn('event_id'), $eventId);
        }
    }
}
