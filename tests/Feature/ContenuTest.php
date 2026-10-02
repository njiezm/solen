<?php

namespace Tests\Feature;

use App\Models\ContentBlock;
use Tests\TestCase;

/**
 * Les pages sont pilotées par les blocs. Un mariage neuf ne doit afficher
 * aucun contenu d'un autre : le contenu était autrefois figé dans les vues.
 */
class ContenuTest extends TestCase
{
    public function test_un_mariage_neuf_na_aucun_contenu(): void
    {
        $event = $this->mariagePublie();

        $this->assertSame(0, $this->dansLeMariage($event, fn () => ContentBlock::count()));

        $this->get($this->url($event, '/notre-histoire'))->assertOk()->assertSee('bientôt', false);
        $this->get($this->url($event, '/menu'))->assertOk();
        $this->get($this->url($event, '/infos-pratiques'))->assertOk();
    }

    public function test_aucune_trace_du_mariage_dorigine(): void
    {
        $event = $this->mariagePublie(['plan' => 'signature']);

        foreach (['', '/accueil', '/notre-histoire', '/menu', '/infos-pratiques', '/ceremonie'] as $page) {
            $this->get($this->url($event, $page))
                ->assertDontSee('Saint-Laurent')
                ->assertDontSee('Apaloosa')
                ->assertDontSee('Lamentin');
        }
    }

    public function test_un_bloc_ajoute_apparait_sur_le_site(): void
    {
        $event = $this->mariagePublie();

        $this->dansLeMariage($event, fn () => ContentBlock::create([
            'page' => 'pratique', 'type' => 'hebergement',
            'donnees' => ['nom' => 'Hôtel des Pins', 'code_promo' => 'SOLEN10'],
            'ordre' => 1, 'actif' => true,
        ]));

        $this->get($this->url($event, '/infos-pratiques'))
            ->assertSee('Hôtel des Pins')
            ->assertSee('SOLEN10');
    }

    public function test_un_bloc_masque_disparait(): void
    {
        $event = $this->mariagePublie();

        $bloc = $this->dansLeMariage($event, fn () => ContentBlock::create([
            'page' => 'hommage', 'type' => 'defunt',
            'donnees' => ['nom' => 'Tante Jeanne'], 'ordre' => 1, 'actif' => true,
        ]));

        $this->get($this->url($event, '/hommage'))->assertSee('Tante Jeanne');

        $bloc->update(['actif' => false]);

        $this->get($this->url($event, '/hommage'))->assertDontSee('Tante Jeanne');
    }

    public function test_le_client_ajoute_un_bloc_depuis_son_espace(): void
    {
        $event = $this->mariagePublie();

        $this->actingAs($this->proprietaire($event))
            ->post($this->url($event, '/espace/pages/pratique/ajouter/hebergement'), [
                'donnees' => ['nom' => 'Villa Marine', 'telephone' => '0556000001'],
            ])
            ->assertRedirect($this->url($event, '/espace/pages/pratique'));

        $this->get($this->url($event, '/infos-pratiques'))->assertSee('Villa Marine');
    }

    public function test_un_champ_obligatoire_vide_est_refuse(): void
    {
        $event = $this->mariagePublie();

        $this->actingAs($this->proprietaire($event))
            ->post($this->url($event, '/espace/pages/pratique/ajouter/hebergement'), [
                'donnees' => ['nom' => ''],
            ])
            ->assertSessionHasErrors('donnees.nom');
    }

    public function test_chaque_type_de_bloc_declare_des_champs_valides(): void
    {
        foreach (config('solen_schema.blocs') as $type => $definition) {
            $this->assertArrayHasKey('champs', $definition, "Le bloc {$type} n'a pas de champs.");
            $this->assertArrayHasKey('nom', $definition, "Le bloc {$type} n'a pas de nom.");

            foreach ($definition['champs'] as $champ) {
                $this->assertArrayHasKey('cle', $champ, "Champ sans clé dans {$type}.");
                $this->assertArrayHasKey('type', $champ, "Champ sans type dans {$type}.");
            }
        }
    }
}
