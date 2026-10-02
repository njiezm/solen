<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Solen\CurrentEvent;
use Illuminate\Database\Seeder;

/**
 * Pas de WithoutModelEvents ici : le trait BelongsToEvent renseigne event_id
 * dans l'événement `creating`. Le désactiver ferait échouer tous les seeders
 * de contenu sur la contrainte NOT NULL.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Socle de la plateforme, hors locataire.
        $this->call([
            ThemeSeeder::class,
            CatalogueSeeder::class,
            EvenementInitialSeeder::class,
            ContenuInitialSeeder::class,
        ]);

        // Le contenu appartient à un mariage : on lui donne son contexte,
        // faute de quoi event_id resterait vide en ligne de commande.
        $event = Event::where('slug', 'maeva-gilles')->first() ?? Event::orderBy('id')->first();

        if (! $event) {
            $this->command?->warn('Aucun événement en base — seeders de contenu ignorés.');

            return;
        }

        app(CurrentEvent::class)->pretend($event, function () {
            $this->call([
                QuestionQuiDeuxSeeder::class,
                CeremonieSeeder::class,
                MotsCroisesSeeder::class,
            ]);
        });
    }
}
