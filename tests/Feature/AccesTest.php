<?php

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Les espaces privés contiennent les messages et les photos des invités.
 * Ils étaient publics avant la refonte : ce test garantit qu'ils ne le
 * redeviennent jamais.
 */
class AccesTest extends TestCase
{
    public static function espacesPrives(): array
    {
        return [
            'espace organisateur' => ['/espace'],
            'modules'             => ['/espace/modules'],
            'pages'               => ['/espace/pages'],
            'cagnotte'            => ['/espace/paiements'],
            'administration'      => ['/admin'],
            'déroulé'             => ['/admin/etapes-ceremonie'],
            'contributions'       => ['/maries'],
            'livre d’or'          => ['/maries/livre-or'],
        ];
    }

    #[DataProvider('espacesPrives')]
    public function test_un_anonyme_est_renvoye_vers_la_connexion(string $chemin): void
    {
        $event = $this->mariagePublie();

        $this->get($this->url($event, $chemin))->assertRedirect(route('auth.login'));
    }

    #[DataProvider('espacesPrives')]
    public function test_le_proprietaire_accede(string $chemin): void
    {
        $event = $this->mariagePublie();

        $this->actingAs($this->proprietaire($event))
            ->get($this->url($event, $chemin))
            ->assertOk();
    }

    public function test_un_organisateur_ne_peut_pas_ouvrir_le_mariage_dun_autre(): void
    {
        $sien    = $this->mariagePublie(['nom' => 'Alice & Bob']);
        $etranger = $this->mariagePublie(['nom' => 'Chloé & David']);

        $this->actingAs($this->proprietaire($sien))
            ->get($this->url($etranger, '/espace'))
            ->assertForbidden();
    }

    public function test_lequipe_solen_passe_partout(): void
    {
        $event = $this->mariagePublie();

        $this->actingAs($this->equipeSolen())
            ->get($this->url($event, '/espace'))
            ->assertOk();
    }

    public function test_la_console_est_reservee_a_lequipe_solen(): void
    {
        $event = $this->mariagePublie();

        $this->get('/console')->assertRedirect(route('auth.login'));

        $this->actingAs($this->proprietaire($event))->get('/console')->assertForbidden();
        $this->actingAs($this->equipeSolen())->get('/console')->assertOk();
    }

    public function test_la_connexion_fonctionne_et_mene_a_son_mariage(): void
    {
        $event = $this->mariagePublie();
        $user  = $this->proprietaire($event);
        $user->update(['password' => 'motdepasse-solide']);

        $this->post('/connexion', [
            'email'    => $user->email,
            'password' => 'motdepasse-solide',
        ])->assertRedirect(route('espace.index', $event->slug));

        $this->assertAuthenticatedAs($user);
    }

    public function test_un_mauvais_mot_de_passe_est_refuse(): void
    {
        $user = User::factory()->create();

        $this->post('/connexion', ['email' => $user->email, 'password' => 'faux'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
