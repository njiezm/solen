<?php

namespace Tests\Unit;

use App\Models\ContentBlock;
use App\Solen\AvancementMariage;
use Tests\TestCase;

class AvancementTest extends TestCase
{
    public function test_un_mariage_neuf_nest_pas_complet(): void
    {
        $etat = app(AvancementMariage::class)->pour($this->creerMariage());

        $this->assertLessThan(100, $etat['pourcentage']);
        $this->assertGreaterThan(0, $etat['total']);
    }

    public function test_remplir_une_etape_fait_progresser(): void
    {
        $event   = $this->creerMariage();
        $service = app(AvancementMariage::class);

        $avant = $service->pour($event)['pourcentage'];

        $this->dansLeMariage($event, fn () => ContentBlock::create([
            'page' => 'histoire', 'type' => 'texte',
            'donnees' => ['contenu' => 'Notre rencontre'], 'ordre' => 1, 'actif' => true,
        ]));

        $this->assertGreaterThan($avant, $service->pour($event->fresh())['pourcentage']);
    }

    public function test_les_etapes_hors_formule_ne_sont_pas_comptees(): void
    {
        $service = app(AvancementMariage::class);

        $sansCagnotte = $service->pour($this->creerMariage(['plan' => 'decouverte']));
        $avecCagnotte = $service->pour($this->creerMariage(['nom' => 'A & B', 'plan' => 'celebration']));

        $this->assertLessThan($avecCagnotte['total'], $sansCagnotte['total']);
    }

    public function test_publier_termine_la_derniere_etape(): void
    {
        $event   = $this->creerMariage();
        $service = app(AvancementMariage::class);

        $this->assertFalse(collect($service->pour($event)['etapes'])->firstWhere('cle', 'publier')['fait']);

        $event->update(['statut' => 'publie']);

        $this->assertTrue(collect($service->pour($event->fresh())['etapes'])->firstWhere('cle', 'publier')['fait']);
    }
}
