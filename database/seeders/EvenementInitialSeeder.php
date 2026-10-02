<?php

namespace Database\Seeders;

use App\Models\EtapeCeremonie;
use App\Models\Event;
use App\Models\EventPart;
use App\Models\Theme;
use App\Models\User;
use App\Solen\CurrentEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Achève la bascule du mariage Maëva & Gilles en locataire à part entière :
 * thème, parties du mariage, rattachement du déroulé existant, et création
 * du compte organisateur.
 *
 * Idempotent : peut être rejoué sans rien dupliquer.
 */
class EvenementInitialSeeder extends Seeder
{
    /** Les parties du mariage du 26 décembre 2025, telles qu'elles ont eu lieu. */
    private const PARTIES = [
        [
            'cle'            => 'mairie',
            'nom'            => 'Mairie',
            'icone'          => 'fa-gavel',
            'type_ceremonie' => 'civil',
            'ordre'          => 1,
        ],
        [
            'cle'            => 'ceremonie',
            'nom'            => 'Cérémonie religieuse',
            'icone'          => 'fa-church',
            'type_ceremonie' => 'catholique',
            'lieu_nom'       => 'Église Saint-Laurent du Lamentin',
            'lieu_adresse'   => '36 Rue Schoelcher, Le Lamentin 97232, Martinique',
            'accueil_at'     => '2025-12-26 13:30:00',
            'debut_at'       => '2025-12-26 14:00:00',
            'ordre'          => 2,
        ],
        [
            'cle'        => 'vin-honneur',
            'nom'        => 'Vin d’honneur',
            'icone'      => 'fa-champagne-glasses',
            'lieu_nom'   => 'Domaine de l’Apaloosa',
            'lieu_adresse' => 'Le François, Martinique',
            'debut_at'   => '2025-12-26 17:00:00',
            'ordre'      => 3,
        ],
        [
            'cle'      => 'diner',
            'nom'      => 'Dîner',
            'icone'    => 'fa-utensils',
            'lieu_nom' => 'Domaine de l’Apaloosa',
            'ordre'    => 4,
        ],
        [
            'cle'      => 'soiree',
            'nom'      => 'Soirée',
            'icone'    => 'fa-music',
            'lieu_nom' => 'Domaine de l’Apaloosa',
            'ordre'    => 5,
        ],
    ];

    public function run(): void
    {
        $event = Event::where('slug', 'maeva-gilles')->first();

        if (! $event) {
            $this->command?->warn('Événement maeva-gilles introuvable — lancez d’abord les migrations.');

            return;
        }

        // Le vert sapin d'origine devient le thème du mariage.
        if (! $event->theme_id && $theme = Theme::where('cle', 'sapin')->first()) {
            $event->update(['theme_id' => $theme->id]);
        }

        app(CurrentEvent::class)->pretend($event, function () use ($event) {
            $this->creerParties();
            $this->rattacherDeroule();
            $this->creerOrganisateur($event);
        });

        $this->command?->info("Événement « {$event->nom} » complété.");
    }

    private function creerParties(): void
    {
        foreach (self::PARTIES as $partie) {
            EventPart::updateOrCreate(
                ['cle' => $partie['cle']],
                $partie + ['actif' => true]
            );
        }
    }

    /**
     * Les étapes du déroulé existantes ont été créées avant l'introduction des
     * parties : elles appartiennent toutes à la cérémonie religieuse.
     */
    private function rattacherDeroule(): void
    {
        $ceremonie = EventPart::where('cle', 'ceremonie')->first();

        if (! $ceremonie) {
            return;
        }

        $rattachees = EtapeCeremonie::whereNull('event_part_id')
            ->update(['event_part_id' => $ceremonie->id]);

        if ($rattachees) {
            $this->command?->info("{$rattachees} étape(s) de déroulé rattachée(s) à la cérémonie.");
        }
    }

    /**
     * Compte organisateur. Le mot de passe vient de l'environnement ; à
     * défaut il est généré et affiché une seule fois, jamais écrit en dur.
     */
    private function creerOrganisateur(Event $event): void
    {
        $email = env('SOLEN_ADMIN_EMAIL', 'contact@solen.app');

        $existant = User::where('email', $email)->first();

        if ($existant) {
            $existant->events()->syncWithoutDetaching([$event->id => ['role' => 'proprietaire']]);
            $this->command?->info("Compte {$email} déjà présent, rattaché au mariage.");

            return;
        }

        // Sans symboles : la console Symfony interprète `<...>` comme une
        // balise de style et mutile l'affichage du mot de passe.
        $motDePasse = env('SOLEN_ADMIN_PASSWORD') ?: Str::password(20, symbols: false);

        $user = User::create([
            'name'     => 'Équipe Solen',
            'email'    => $email,
            'password' => Hash::make($motDePasse),
            'role'     => User::ROLE_SUPER_ADMIN,
        ]);

        $user->events()->attach($event->id, ['role' => 'proprietaire']);

        $this->command?->warn('──────────────────────────────────────────────');
        $this->command?->warn("  Compte créé : {$email}");
        $this->command?->warn("  Mot de passe : {$motDePasse}");
        $this->command?->warn('  Notez-le maintenant, il ne sera plus affiché.');
        $this->command?->warn('──────────────────────────────────────────────');
    }
}
