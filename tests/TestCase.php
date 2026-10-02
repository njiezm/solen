<?php

namespace Tests;

use App\Models\Event;
use App\Models\Theme;
use App\Models\User;
use App\Solen\CreationMariage;
use App\Solen\CurrentEvent;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\ThemeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Socle commun des tests.
 *
 * Le catalogue (thèmes, modules, formules) est chargé pour chaque test :
 * sans lui aucun mariage ne peut exister, et les tests ne diraient rien de
 * ce qui tourne réellement en production.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ThemeSeeder::class);
        $this->seed(CatalogueSeeder::class);
    }

    /**
     * Crée un mariage par le même chemin que la production : l'assistant.
     * Un helper qui insérerait directement en base ne testerait pas ce que
     * vivent les clients.
     *
     * @param  array<string, mixed>  $reponses
     */
    protected function creerMariage(array $reponses = []): Event
    {
        $resultat = app(CreationMariage::class)->executer(array_merge([
            'nom'             => 'Clara & Maël',
            'partenaire_1'    => 'Clara',
            'partenaire_2'    => 'Maël',
            'date_principale' => now()->addMonths(6)->format('Y-m-d'),
            'timezone'        => 'Europe/Paris',
            'lieu_ville'      => 'Arcachon',
            'parties'         => ['mairie', 'ceremonie', 'diner'],
            'type_ceremonie'  => 'laique',
            'plan'            => 'celebration',
            'theme_id'        => Theme::where('cle', 'ivoire')->value('id'),
        ], $reponses));

        return $resultat['event'];
    }

    /** Un mariage publié, prêt à recevoir des invités. */
    protected function mariagePublie(array $reponses = []): Event
    {
        $event = $this->creerMariage($reponses);
        $event->update(['statut' => Event::STATUT_PUBLIE, 'publie_at' => now()]);

        return $event->fresh();
    }

    /** Le compte propriétaire d'un mariage. */
    protected function proprietaire(Event $event): User
    {
        $user = User::factory()->create(['role' => User::ROLE_ORGANISATEUR]);
        $user->events()->attach($event->id, ['role' => 'proprietaire']);

        return $user;
    }

    protected function equipeSolen(): User
    {
        return User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    }

    /**
     * Exécute un traitement dans le contexte d'un mariage, comme le fait le
     * middleware pour une requête web.
     */
    protected function dansLeMariage(Event $event, callable $callback): mixed
    {
        return app(CurrentEvent::class)->pretend($event, $callback);
    }

    /** Adresse d'une page du site invité. */
    protected function url(Event $event, string $chemin = ''): string
    {
        return '/mariage/' . $event->slug . $chemin;
    }
}
