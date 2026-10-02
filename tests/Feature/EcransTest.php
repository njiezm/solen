<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Toutes les pages doivent répondre. Trois écrans étaient cassés sans que
 * personne ne s'en aperçoive : une vue manquante, une méthode inexistante,
 * un alias de façade absent.
 */
class EcransTest extends TestCase
{
    public static function pagesInvite(): array
    {
        return [
            'entrée'          => [''],
            'accueil'         => ['/accueil'],
            'notre histoire'  => ['/notre-histoire'],
            'menu'            => ['/menu'],
            'infos pratiques' => ['/infos-pratiques'],
            'hommage'         => ['/hommage'],
            'cérémonie'       => ['/ceremonie'],
            'mairie'          => ['/mairie'],
            'galerie'         => ['/galerie'],
            'livre d’or'      => ['/livre-or'],
            'urne'            => ['/urne'],
            'photobooth'      => ['/photobooth'],
            'qui de nous 2'   => ['/jeux/qui-de-nous-2'],
            'chasse photo'    => ['/jeux/chasse-photo'],
            'mots croisés'    => ['/jeux/mots-croises'],
            'memory'          => ['/jeux/memory'],
        ];
    }

    #[DataProvider('pagesInvite')]
    public function test_le_site_invite_repond(string $chemin): void
    {
        $event = $this->mariagePublie(['plan' => 'signature']);

        $this->get($this->url($event, $chemin))->assertOk();
    }

    public static function pagesOrganisateur(): array
    {
        return [
            'tableau de bord'   => ['/espace'],
            'informations'      => ['/espace/informations'],
            'modules'           => ['/espace/modules'],
            'réglage module'    => ['/espace/modules/photobooth'],
            'pages'             => ['/espace/pages'],
            'page menu'         => ['/espace/pages/menu'],
            'ajout de bloc'     => ['/espace/pages/menu/ajouter/plat'],
            'cagnotte'          => ['/espace/paiements'],
            'livre souvenir'    => ['/espace/livre-souvenir'],
            'jour J'            => ['/admin'],
            'déroulé'           => ['/admin/etapes-ceremonie'],
            'questions'         => ['/admin/questions'],
            'jeux'              => ['/admin/sessions'],
            'mots croisés'      => ['/admin/mots-croises'],
            'cartes memory'     => ['/admin/memory-cards'],
            'QR codes'          => ['/admin/qrcodes'],
            'contributions'     => ['/maries'],
            'livre d’or'        => ['/maries/livre-or'],
            'galerie'           => ['/maries/galerie'],
            'photos de jeu'     => ['/maries/photos'],
            'messages'          => ['/maries/messages'],
            'statistiques'      => ['/maries/statistiques'],
            'réponses au jeu'   => ['/maries/jeux/qui-de-nous-2'],
        ];
    }

    #[DataProvider('pagesOrganisateur')]
    public function test_lespace_organisateur_repond(string $chemin): void
    {
        $event = $this->mariagePublie(['plan' => 'signature']);

        $this->actingAs($this->proprietaire($event))
            ->get($this->url($event, $chemin))
            ->assertOk();
    }

    public static function pagesConsole(): array
    {
        return [
            'mariages'       => ['/console'],
            'thèmes'         => ['/console/themes'],
            'nouveau thème'  => ['/console/themes/nouveau'],
            'nouveau mariage'=> ['/console/mariages/nouveau'],
        ];
    }

    #[DataProvider('pagesConsole')]
    public function test_la_console_repond(string $chemin): void
    {
        $this->creerMariage();

        $this->actingAs($this->equipeSolen())->get($chemin)->assertOk();
    }

    public function test_la_vitrine_et_lauthentification_repondent(): void
    {
        $this->get('/')->assertOk();
        $this->get('/connexion')->assertOk();
        $this->get('/mot-de-passe')->assertOk();
    }

    public function test_la_fiche_dun_mariage_dans_la_console(): void
    {
        $event = $this->creerMariage();

        $this->actingAs($this->equipeSolen())
            ->get('/console/mariages/' . $event->slug)
            ->assertOk()
            ->assertSee($event->nom);
    }

    public function test_plus_aucune_modale_bootstrap_morte(): void
    {
        // Bootstrap JS n'est pas chargé dans l'espace : une modale y serait
        // un bouton qui ne fait rien.
        $event = $this->mariagePublie();

        $this->actingAs($this->proprietaire($event))
            ->get($this->url($event, '/admin/questions'))
            ->assertOk()
            ->assertDontSee('data-bs-toggle="modal"', false);
    }
}
