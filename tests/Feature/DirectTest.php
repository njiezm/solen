<?php

namespace Tests\Feature;

use App\Models\EtapeCeremonie;
use Tests\TestCase;

/**
 * Le déroulé en direct, cœur de la promesse commerciale.
 */
class DirectTest extends TestCase
{
    public function test_le_point_de_sondage_decrit_letat_du_deroule(): void
    {
        $event = $this->mariagePublie();

        $this->get($this->url($event, '/direct'))
            ->assertOk()
            ->assertJsonStructure(['actif', 'empreinte', 'enCours', 'etapes', 'avancement']);
    }

    public function test_marquer_une_etape_change_ce_que_voient_les_invites(): void
    {
        $event = $this->mariagePublie();

        $etape = $this->dansLeMariage($event,
            fn () => EtapeCeremonie::ordonnees()->skip(2)->first());

        $avant = $this->get($this->url($event, '/direct'))->json();
        $this->assertNull($avant['enCours']);

        $this->dansLeMariage($event, fn () => $etape->demarrer());

        $apres = $this->get($this->url($event, '/direct'))->json();

        $this->assertSame($etape->titre, $apres['enCours']['titre']);
        $this->assertNotSame($avant['empreinte'], $apres['empreinte']);
        $this->assertGreaterThan(0, $apres['avancement']);
    }

    public function test_une_seule_etape_est_en_cours_a_la_fois(): void
    {
        $event = $this->mariagePublie();

        $this->dansLeMariage($event, function () {
            $etapes = EtapeCeremonie::ordonnees()->get();
            $etapes[1]->demarrer();
            $etapes[4]->demarrer();

            $this->assertSame(1, EtapeCeremonie::where('en_cours', true)->count());
            $this->assertTrue($etapes[4]->fresh()->en_cours);
        });
    }

    public function test_le_direct_est_muet_si_le_module_est_inactif(): void
    {
        $event  = $this->mariagePublie();
        $module = $event->modules()->where('cle', 'deroule')->first();
        $event->modules()->updateExistingPivot($module->id, ['actif' => false]);

        $this->get($this->url($event, '/direct'))->assertOk()->assertJson(['actif' => false]);
    }

    public function test_le_direct_dun_mariage_ignore_les_etapes_dun_autre(): void
    {
        $a = $this->mariagePublie(['nom' => 'Alice & Bob']);
        $b = $this->mariagePublie(['nom' => 'Chloé & David']);

        $this->dansLeMariage($b, fn () => EtapeCeremonie::ordonnees()->first()->demarrer());

        $this->assertNull($this->get($this->url($a, '/direct'))->json('enCours'));
        $this->assertNotNull($this->get($this->url($b, '/direct'))->json('enCours'));
    }
}
