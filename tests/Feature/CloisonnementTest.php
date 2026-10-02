<?php

namespace Tests\Feature;

use App\Models\ContentBlock;
use App\Models\LivreOr;
use App\Models\Participant;
use App\Models\Photo;
use Tests\TestCase;

/**
 * L'invariant le plus important de la plateforme : deux mariages ne doivent
 * jamais se voir. Une fuite ici exposerait les messages privés et les photos
 * d'un couple à un autre.
 */
class CloisonnementTest extends TestCase
{
    public function test_chaque_mariage_ne_voit_que_ses_donnees(): void
    {
        $a = $this->creerMariage(['nom' => 'Alice & Bob',   'parties' => ['ceremonie']]);
        $b = $this->creerMariage(['nom' => 'Chloé & David', 'parties' => ['ceremonie']]);

        $this->dansLeMariage($a, fn () => Participant::create(['prenom' => 'Léa', 'nom' => 'Martin']));
        $this->dansLeMariage($b, function () {
            Participant::create(['prenom' => 'Hugo', 'nom' => 'Durand']);
            Participant::create(['prenom' => 'Inès', 'nom' => 'Petit']);
        });

        $this->assertSame(1, $this->dansLeMariage($a, fn () => Participant::count()));
        $this->assertSame(2, $this->dansLeMariage($b, fn () => Participant::count()));

        $this->assertSame('Léa', $this->dansLeMariage($a, fn () => Participant::first()->prenom));
    }

    public function test_event_id_est_rempli_automatiquement(): void
    {
        $event = $this->creerMariage();

        $bloc = $this->dansLeMariage($event, fn () => ContentBlock::create([
            'page' => 'histoire', 'type' => 'texte', 'donnees' => ['contenu' => 'Notre rencontre'],
        ]));

        $this->assertSame($event->id, $bloc->event_id);
    }

    public function test_la_portee_transverse_voit_tout(): void
    {
        $a = $this->creerMariage(['nom' => 'Alice & Bob']);
        $b = $this->creerMariage(['nom' => 'Chloé & David']);

        $this->dansLeMariage($a, fn () => Participant::create(['prenom' => 'Un', 'nom' => 'X']));
        $this->dansLeMariage($b, fn () => Participant::create(['prenom' => 'Deux', 'nom' => 'Y']));

        $this->assertSame(2, Participant::tousEvenements()->count());
        $this->assertSame(1, Participant::pourEvenement($a)->count());
    }

    public function test_le_site_dun_mariage_naffiche_pas_le_contenu_dun_autre(): void
    {
        $a = $this->mariagePublie(['nom' => 'Alice & Bob']);
        $b = $this->mariagePublie(['nom' => 'Chloé & David']);

        $this->dansLeMariage($a, fn () => ContentBlock::create([
            'page' => 'hommage', 'type' => 'defunt',
            'donnees' => ['nom' => 'Grand-Mère Yvonne'], 'ordre' => 1, 'actif' => true,
        ]));

        $this->get($this->url($a, '/hommage'))->assertOk()->assertSee('Yvonne');
        $this->get($this->url($b, '/hommage'))->assertOk()->assertDontSee('Yvonne');
    }

    public function test_les_messages_prives_ne_fuient_pas_entre_mariages(): void
    {
        $a = $this->mariagePublie(['nom' => 'Alice & Bob']);
        $b = $this->mariagePublie(['nom' => 'Chloé & David']);

        $this->dansLeMariage($a, function () {
            $p = Participant::create(['prenom' => 'Léa', 'nom' => 'M']);
            LivreOr::create(['participant_id' => $p->id, 'message' => 'Un secret bien gardé']);
        });

        // Le propriétaire de B, connecté, ne doit rien voir de A.
        $this->actingAs($this->proprietaire($b))
            ->get($this->url($b, '/maries/livre-or'))
            ->assertOk()
            ->assertDontSee('secret bien gardé');
    }

    public function test_un_mariage_inexistant_renvoie_404(): void
    {
        $this->get('/mariage/nexiste-pas')->assertNotFound();
    }
}
