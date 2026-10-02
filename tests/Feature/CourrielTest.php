<?php

namespace Tests\Feature;

use App\Mail\AlerteVente;
use App\Mail\CommandeConfirmee;
use App\Mail\RelanceBrouillon;
use App\Models\Commande;
use App\Models\Theme;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;

/**
 * Les gabarits doivent se rendre : un e-mail qui plante à l'envoi bloquerait
 * une vente déjà encaissée.
 */
class CourrielTest extends TestCase
{
    public function test_les_gabarits_se_rendent_sans_erreur(): void
    {
        $event = $this->creerMariage();

        $commande = Commande::create([
            'plan' => 'celebration', 'nom' => $event->nom, 'email' => 'clara@exemple.fr',
            'timezone' => 'Europe/Paris', 'montant_centimes' => 24900,
            'statut' => Commande::HONOREE, 'event_id' => $event->id,
            'theme_id' => Theme::first()->id,
        ]);

        $avancement = app(\App\Solen\AvancementMariage::class)->pour($event);

        foreach ([
            new CommandeConfirmee($commande, 'MotDePasse123'),
            new AlerteVente($commande),
            new AlerteVente($commande, echec: true),
            new RelanceBrouillon($event, $avancement),
        ] as $courriel) {
            $rendu = $courriel->render();

            $this->assertNotEmpty($rendu);
            $this->assertStringContainsString('solen', mb_strtolower($rendu));
        }
    }

    public function test_la_confirmation_contient_les_acces(): void
    {
        $event = $this->creerMariage();

        $commande = Commande::create([
            'plan' => 'decouverte', 'nom' => $event->nom, 'email' => 'clara@exemple.fr',
            'timezone' => 'Europe/Paris', 'statut' => Commande::HONOREE, 'event_id' => $event->id,
        ]);

        $rendu = (new CommandeConfirmee($commande, 'SecretDuJour'))->render();

        $this->assertStringContainsString('clara@exemple.fr', $rendu);
        $this->assertStringContainsString('SecretDuJour', $rendu);
        $this->assertStringContainsString($event->slug, $rendu);
    }

    public function test_le_lien_de_reinitialisation_est_envoye(): void
    {
        Notification::fake();

        $event = $this->mariagePublie();
        $user  = $this->proprietaire($event);

        $this->post('/mot-de-passe', ['email' => $user->email])->assertSessionHas('statut');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_une_adresse_inconnue_ne_revele_rien(): void
    {
        Notification::fake();

        $this->post('/mot-de-passe', ['email' => 'personne@exemple.fr'])
            ->assertSessionHas('statut')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_la_relance_ne_part_quune_fois(): void
    {
        Mail::fake();

        $event = $this->creerMariage();
        $this->proprietaire($event);
        $event->forceFill(['created_at' => now()->subDays(10)])->save();

        $this->artisan('solen:relancer-brouillons')->assertSuccessful();
        Mail::assertSent(RelanceBrouillon::class, 1);

        // Deuxième passage : plus rien, sous peine d'être signalé en spam.
        $this->artisan('solen:relancer-brouillons')->assertSuccessful();
        Mail::assertSent(RelanceBrouillon::class, 1);
    }

    public function test_un_mariage_publie_nest_jamais_relance(): void
    {
        Mail::fake();

        $event = $this->mariagePublie();
        $this->proprietaire($event);
        $event->forceFill(['created_at' => now()->subDays(30)])->save();

        $this->artisan('solen:relancer-brouillons')->assertSuccessful();

        Mail::assertNothingSent();
    }
}
