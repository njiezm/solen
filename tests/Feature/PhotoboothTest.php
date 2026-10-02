<?php

namespace Tests\Feature;

use App\Mail\PhotoPhotobooth;
use App\Models\Photo;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoboothTest extends TestCase
{
    /** Un pixel JPEG valide, en dataURL. */
    private function cliche(): string
    {
        return 'data:image/jpeg;base64,' . base64_encode(
            base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AKp//2Q==')
        );
    }

    public function test_la_page_saffiche_quand_le_module_est_actif(): void
    {
        $event = $this->mariagePublie();

        $this->get($this->url($event, '/photobooth'))->assertOk()->assertSee('Photobooth');
    }

    public function test_la_page_est_introuvable_si_le_module_est_inactif(): void
    {
        $event  = $this->mariagePublie();
        $module = $event->modules()->where('cle', 'photobooth')->first();
        $event->modules()->updateExistingPivot($module->id, ['actif' => false]);

        $this->get($this->url($event, '/photobooth'))->assertNotFound();
    }

    public function test_un_cliche_est_enregistre_et_rejoint_la_galerie(): void
    {
        Storage::fake('public');
        $event = $this->mariagePublie();

        $this->postJson($this->url($event, '/photobooth'), [
            'image'  => $this->cliche(),
            'prenom' => 'Léa',
            'nom'    => 'Martin',
        ])->assertOk()->assertJson(['ok' => true]);

        $photo = $this->dansLeMariage($event, fn () => Photo::first());

        $this->assertNotNull($photo);
        Storage::disk('public')->assertExists($photo->path);
        $this->assertSame($event->id, $photo->event_id);
    }

    public function test_une_charge_utile_qui_nest_pas_une_image_est_refusee(): void
    {
        Storage::fake('public');
        $event = $this->mariagePublie();

        $this->postJson($this->url($event, '/photobooth'), ['image' => 'data:text/html;base64,PHNjcmlwdD4='])
            ->assertStatus(422);

        $this->assertSame(0, $this->dansLeMariage($event, fn () => Photo::count()));
    }

    public function test_le_cliche_est_envoye_par_courriel_si_une_adresse_est_donnee(): void
    {
        Storage::fake('public');
        Mail::fake();
        $event = $this->mariagePublie();

        $this->postJson($this->url($event, '/photobooth'), [
            'image' => $this->cliche(),
            'email' => 'invite@exemple.fr',
        ])->assertOk()->assertJson(['envoye' => true]);

        Mail::assertQueued(PhotoPhotobooth::class, fn ($m) => $m->hasTo('invite@exemple.fr'));
    }

    public function test_sans_adresse_aucun_courriel_ne_part(): void
    {
        Storage::fake('public');
        Mail::fake();
        $event = $this->mariagePublie();

        $this->postJson($this->url($event, '/photobooth'), ['image' => $this->cliche()])
            ->assertOk()->assertJson(['envoye' => false]);

        Mail::assertNothingQueued();
    }
}
