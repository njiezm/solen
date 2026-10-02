<?php

namespace Tests\Feature;

use App\Models\Theme;
use Tests\TestCase;

/**
 * Le thème était bien enregistré mais la feuille de style l'ignorait : le
 * site restait vert quel que soit le choix du couple. On vérifie donc que
 * les jetons arrivent réellement dans la page.
 */
class ThemeTest extends TestCase
{
    public function test_les_jetons_du_theme_arrivent_dans_la_page(): void
    {
        $theme = Theme::where('cle', 'terracotta')->first();
        $event = $this->mariagePublie(['theme_id' => $theme->id]);

        $reponse = $this->get($this->url($event));

        $reponse->assertOk()
            ->assertSee('--theme-ink:' . $theme->ink, false)
            ->assertSee('--theme-accent:' . $theme->accent, false)
            ->assertSee('--theme-r-carte:', false);
    }

    public function test_deux_themes_produisent_des_rendus_differents(): void
    {
        $tropical = Theme::where('cle', 'tropical')->first();   // très rond, festif
        $ivoire   = Theme::where('cle', 'ivoire')->first();     // doux, épuré

        $a = $this->mariagePublie(['nom' => 'Alice & Bob',   'theme_id' => $tropical->id]);
        $b = $this->mariagePublie(['nom' => 'Chloé & David', 'theme_id' => $ivoire->id]);

        $this->get($this->url($a))
            ->assertSee('--theme-capitales:uppercase', false)
            ->assertSee('ornement-double', false);

        $this->get($this->url($b))
            ->assertSee('--theme-capitales:none', false)
            ->assertSee('ornement-aucun', false);
    }

    public function test_les_jetons_couvrent_couleurs_formes_et_caractere(): void
    {
        $jetons = Theme::where('cle', 'nuit')->first()->jetons();

        foreach (['--theme-ink', '--theme-surface', '--theme-accent', '--theme-secondaire',
                  '--theme-display', '--theme-body', '--theme-r-carte', '--theme-r-bouton',
                  '--theme-capitales', '--theme-densite'] as $jeton) {
            $this->assertArrayHasKey($jeton, $jetons, "Le jeton {$jeton} manque.");
        }
    }

    public function test_un_mariage_garde_son_theme_quand_un_autre_change(): void
    {
        $a = $this->mariagePublie(['nom' => 'Alice & Bob',   'theme_id' => Theme::where('cle', 'sapin')->value('id')]);
        $b = $this->mariagePublie(['nom' => 'Chloé & David', 'theme_id' => Theme::where('cle', 'blush')->value('id')]);

        $b->update(['theme_id' => Theme::where('cle', 'tropical')->value('id')]);

        $this->get($this->url($a))->assertSee('--theme-accent:#B55239', false);
    }

    public function test_le_traitement_photographique_arrive_dans_la_page(): void
    {
        $nb = Theme::where('cle', 'nuit')->first();          // noir et blanc
        $event = $this->mariagePublie(['theme_id' => $nb->id]);

        $this->get($this->url($event))->assertSee('--theme-filtre:grayscale', false);
    }

    public function test_un_theme_sombre_declare_son_propre_fond(): void
    {
        $minuit = Theme::where('cle', 'minuit')->first();

        $this->assertTrue($minuit->estSombre());
        $this->assertSame('#08080D', $minuit->fondDePage());

        // Un thème clair, lui, déduit son fond de l'encre.
        $this->assertStringContainsString('color-mix',
            Theme::where('cle', 'ivoire')->first()->fondDePage());
    }

    public function test_la_couverture_se_replie_sur_un_degrade_sans_photo(): void
    {
        $event = $this->mariagePublie();

        $this->get($this->url($event))
            ->assertSee('couverture-fond', false)
            ->assertSee('couverture-voile', false);
    }

    public function test_une_seule_barre_de_navigation_en_tete(): void
    {
        $event = $this->mariagePublie();

        $rendu = $this->get($this->url($event, '/accueil'))->getContent();

        $this->assertSame(1, substr_count($rendu, '<header class="entete'));
        $this->assertStringNotContainsString('class="navbar', $rendu);
    }
}
