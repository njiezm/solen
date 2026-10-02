<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Plan;
use Tests\TestCase;

/**
 * Le moteur déclaratif : les formules débloquent des modules, les modules
 * portent des réglages, et le site invité s'y conforme.
 */
class ModulesTest extends TestCase
{
    public function test_les_formules_sont_cumulatives(): void
    {
        $comptes = Plan::actifs()->get()->map(fn ($p) => $p->modules()->count())->all();

        $this->assertSame($comptes, array_values(array_unique($comptes)), 'Deux formules ont le même contenu.');

        for ($i = 1; $i < count($comptes); $i++) {
            $this->assertGreaterThan($comptes[$i - 1], $comptes[$i],
                'Une formule supérieure doit contenir plus que la précédente.');
        }
    }

    public function test_seuls_les_modules_livres_sont_actifs(): void
    {
        $event = $this->creerMariage(['plan' => 'signature']);

        $actifs = $event->modulesActifs();

        $this->assertTrue($actifs->every(fn ($m) => $m->statut === 'live'));
        $this->assertTrue($event->aModule('photobooth'));
    }

    public function test_monter_de_formule_debloque_sans_rien_ecraser(): void
    {
        $event = $this->creerMariage(['plan' => 'decouverte']);
        $avant = $event->modulesActifs()->count();

        $event->update(['plan' => 'celebration']);
        $ajoutes = $event->appliquerFormule();

        $this->assertGreaterThan(0, $ajoutes);
        $this->assertGreaterThan($avant, $event->fresh()->modulesActifs()->count());
    }

    public function test_un_reglage_retombe_sur_la_valeur_par_defaut(): void
    {
        $event = $this->creerMariage();

        $defaut = Module::where('cle', 'deroule')->first()->valeursParDefaut()['preavis_minutes'];

        $this->assertSame($defaut, $event->reglage('deroule', 'preavis_minutes'));
        $this->assertSame('repli', $event->reglage('deroule', 'champ-inconnu', 'repli'));
    }

    public function test_un_reglage_modifie_est_relu(): void
    {
        $event  = $this->creerMariage();
        $module = $event->modules()->where('cle', 'site')->first();

        $event->modules()->updateExistingPivot($module->id, [
            'config' => json_encode(['dress_code' => 'Tenue de plage']),
        ]);

        $this->assertSame('Tenue de plage', $event->fresh()->reglage('site', 'dress_code'));
    }

    public function test_desactiver_un_module_le_retire_du_site(): void
    {
        $event  = $this->mariagePublie();
        $module = $event->modules()->where('cle', 'livredor')->first();

        $this->get($this->url($event, '/accueil'))->assertSee('Livre d’or', false);

        $event->modules()->updateExistingPivot($module->id, ['actif' => false]);

        $this->get($this->url($event, '/accueil'))->assertDontSee('Livre d’or', false);
    }

    public function test_le_photobooth_passe_en_borne_sur_la_formule_haute(): void
    {
        $this->assertFalse($this->creerMariage(['plan' => 'celebration'])->photoboothEnBorne());
        $this->assertTrue($this->creerMariage(['nom' => 'A & B', 'plan' => 'signature'])->photoboothEnBorne());
    }

    public function test_chaque_module_reglable_declare_des_champs_valides(): void
    {
        foreach (Module::whereNotNull('champs')->get() as $module) {
            foreach ($module->champs as $champ) {
                $this->assertArrayHasKey('cle', $champ, "Champ sans clé dans {$module->cle}.");
                $this->assertArrayHasKey('type', $champ, "Champ {$champ['cle']} sans type dans {$module->cle}.");
            }
        }
    }
}
