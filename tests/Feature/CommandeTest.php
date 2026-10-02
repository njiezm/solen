<?php

namespace Tests\Feature;

use App\Mail\AlerteVente;
use App\Mail\CommandeConfirmee;
use App\Models\Commande;
use App\Models\Event;
use App\Models\Theme;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Le tunnel d'achat. La formule gratuite a planté en production faute d'un
 * type de retour correct : ce test l'empêche de se reproduire.
 */
class CommandeTest extends TestCase
{
    private function donnees(array $extra = []): array
    {
        return array_merge([
            'plan'            => 'decouverte',
            'nom'             => 'Clara & Maël',
            'partenaire_1'    => 'Clara',
            'partenaire_2'    => 'Maël',
            'email'           => 'clara@exemple.fr',
            'date_principale' => now()->addYear()->format('Y-m-d'),
            'timezone'        => 'Europe/Paris',
            'lieu_ville'      => 'Arcachon',
            'type_ceremonie'  => 'laique',
            'parties'         => ['ceremonie', 'diner'],
            'theme_id'        => Theme::where('cle', 'terracotta')->value('id'),
            'cgv'             => '1',
        ], $extra);
    }

    public function test_le_formulaire_saffiche_pour_chaque_formule(): void
    {
        foreach (['decouverte', 'essentiel', 'celebration', 'signature'] as $plan) {
            $this->get("/commander/{$plan}")->assertOk();
        }

        $this->get('/commander/inexistante')->assertNotFound();
    }

    public function test_la_formule_gratuite_cree_le_mariage_sans_stripe(): void
    {
        Mail::fake();

        $this->post('/commander', $this->donnees())
            ->assertOk()
            ->assertSee('Votre mariage est en ligne', false);

        $event = Event::where('slug', 'clara-mael')->first();

        $this->assertNotNull($event);
        $this->assertSame('honoree', Commande::first()->statut);
        $this->assertSame($event->id, Commande::first()->event_id);
    }

    public function test_le_theme_choisi_est_bien_applique(): void
    {
        Mail::fake();

        $terracotta = Theme::where('cle', 'terracotta')->first();

        $this->post('/commander', $this->donnees(['theme_id' => $terracotta->id]));

        $this->assertSame($terracotta->id, Event::where('slug', 'clara-mael')->value('theme_id'));
    }

    public function test_le_deroule_correspond_au_culte_choisi(): void
    {
        Mail::fake();

        $this->post('/commander', $this->donnees([
            'type_ceremonie' => 'catholique',
            'parties'        => ['ceremonie'],
        ]));

        $event = Event::where('slug', 'clara-mael')->first();

        $etapes = $this->dansLeMariage($event, fn () => \App\Models\EtapeCeremonie::count());

        $this->assertSame(count(config('solen_schema.deroules.catholique')), $etapes);
    }

    public function test_les_courriels_partent_a_lachat(): void
    {
        Mail::fake();

        $this->post('/commander', $this->donnees());

        Mail::assertSent(CommandeConfirmee::class, fn ($m) => $m->hasTo('clara@exemple.fr'));
        Mail::assertSent(AlerteVente::class);
    }

    public function test_un_formulaire_incomplet_est_refuse_sans_rien_creer(): void
    {
        Mail::fake();

        $this->post('/commander', $this->donnees(['nom' => '', 'cgv' => null]))
            ->assertSessionHasErrors(['nom', 'cgv']);

        $this->assertSame(0, Commande::count());
        $this->assertNull(Event::where('slug', 'clara-mael')->first());
    }

    public function test_une_date_passee_est_refusee(): void
    {
        $this->post('/commander', $this->donnees(['date_principale' => now()->subDay()->format('Y-m-d')]))
            ->assertSessionHasErrors('date_principale');
    }

    public function test_deux_mariages_homonymes_obtiennent_des_adresses_distinctes(): void
    {
        Mail::fake();

        $this->post('/commander', $this->donnees());
        $this->post('/commander', $this->donnees(['email' => 'autre@exemple.fr']));

        $this->assertSame(2, Event::where('slug', 'like', 'clara-mael%')->count());
        $this->assertNotNull(Event::where('slug', 'clara-mael-2')->first());
    }
}
