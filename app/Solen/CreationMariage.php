<?php

namespace App\Solen;

use App\Models\EtapeCeremonie;
use App\Models\Event;
use App\Models\EventPart;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Crée un mariage complet à partir des réponses de l'assistant.
 *
 * Tout ce qui peut être déduit l'est : les parties de la journée reçoivent
 * leur déroulé type selon le culte choisi, la formule active ses modules,
 * et le compte des mariés est créé au passage. Le couple n'a plus qu'à
 * corriger, jamais à partir d'une page blanche.
 */
class CreationMariage
{
    public function __construct(private readonly CurrentEvent $courant)
    {
    }

    /**
     * @param  array<string, mixed>  $reponses  Les cinq étapes de l'assistant.
     * @return array{event: Event, user: ?User, motDePasse: ?string}
     */
    public function executer(array $reponses): array
    {
        return DB::transaction(function () use ($reponses) {
            $event = $this->creerEvenement($reponses);

            // Le reste est cloisonné : sans contexte, event_id resterait vide.
            $resultat = $this->courant->pretend($event, function () use ($event, $reponses) {
                $this->creerParties($event, $reponses);
                $event->appliquerFormule();

                // Les jeux sont prêts dès l'achat : questions types, grille
                // et missions par défaut. Les mariés n'ont qu'à répondre.
                app(Jeux::class)->preparer($event);

                return $this->creerOrganisateur($event, $reponses);
            });

            return ['event' => $event->fresh(), ...$resultat];
        });
    }

    /** @param array<string, mixed> $reponses */
    private function creerEvenement(array $reponses): Event
    {
        return Event::create([
            'slug'            => $this->slugLibre($reponses['slug'] ?? $reponses['nom']),
            'nom'             => $reponses['nom'],
            'partenaire_1'    => $reponses['partenaire_1'] ?? null,
            'partenaire_2'    => $reponses['partenaire_2'] ?? null,
            'date_principale' => $reponses['date_principale'] ?? null,
            'timezone'        => $reponses['timezone'] ?? 'Europe/Paris',
            'lieu_ville'      => $reponses['lieu_ville'] ?? null,
            'lieu_pays'       => $reponses['lieu_pays'] ?? 'France',
            'theme_id'        => $reponses['theme_id'] ?? Theme::where('cle', 'ivoire')->value('id'),
            'plan'            => $reponses['plan'] ?? 'essentiel',
            'statut'          => Event::STATUT_BROUILLON,
            'est_demo'        => (bool) ($reponses['est_demo'] ?? false),
        ]);
    }

    /**
     * Les parties cochées, dans l'ordre du catalogue, avec leur déroulé type.
     *
     * @param  array<string, mixed>  $reponses
     */
    private function creerParties(Event $event, array $reponses): void
    {
        $catalogue = config('solen_schema.parties');
        $choisies  = (array) ($reponses['parties'] ?? ['ceremonie']);
        $culte     = $reponses['type_ceremonie'] ?? 'civil';
        $date      = $reponses['date_principale'] ?? null;

        // Horaires indicatifs, pour que le compte à rebours ait du sens
        // dès la création. Le couple les ajustera.
        $heures = [
            'mairie' => '11:00', 'ceremonie' => '15:00', 'vin-honneur' => '17:00',
            'diner'  => '20:00', 'soiree'    => '23:00', 'brunch'      => '11:00',
        ];

        $ordre = 0;

        foreach ($catalogue as $cle => $definition) {
            if (! in_array($cle, $choisies, true)) {
                continue;
            }

            $debut = null;

            if ($date && isset($heures[$cle])) {
                $jour  = $cle === 'brunch' ? Carbon::parse($date)->addDay() : Carbon::parse($date);
                $debut = Carbon::parse($jour->format('Y-m-d') . ' ' . $heures[$cle], $event->timezone)->utc();
            }

            $part = EventPart::create([
                'event_id'       => $event->id,
                'cle'            => $cle,
                'nom'            => $definition['nom'],
                'icone'          => $definition['icone'],
                'type_ceremonie' => $definition['ceremonie'] ? ($cle === 'mairie' ? 'civil' : $culte) : null,
                'debut_at'       => $debut,
                'ordre'          => ++$ordre,
                'actif'          => true,
            ]);

            if ($definition['ceremonie']) {
                $this->creerDeroule($event, $part, $cle === 'mairie' ? 'civil' : $culte);
            }
        }
    }

    private function creerDeroule(Event $event, EventPart $part, string $culte): void
    {
        $trame = config("solen_schema.deroules.{$culte}", []);

        foreach ($trame as $index => $etape) {
            EtapeCeremonie::create([
                'event_id'      => $event->id,
                'event_part_id' => $part->id,
                'titre'         => $etape['titre'],
                'icone'         => $etape['icone'],
                'ordre'         => $index + 1,
                'en_cours'      => false,
                'termine'       => false,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $reponses
     * @return array{user: ?User, motDePasse: ?string}
     */
    private function creerOrganisateur(Event $event, array $reponses): array
    {
        $email = $reponses['email'] ?? null;

        if (! $email) {
            return ['user' => null, 'motDePasse' => null];
        }

        $existant = User::where('email', $email)->first();

        if ($existant) {
            $existant->events()->syncWithoutDetaching([$event->id => ['role' => 'proprietaire']]);

            return ['user' => $existant, 'motDePasse' => null];
        }

        // Sans symboles : la console Symfony mutile l'affichage sinon, et le
        // mot de passe doit rester recopiable à la main par le client.
        $motDePasse = Str::password(14, symbols: false);

        $user = User::create([
            'name'     => $reponses['nom'],
            'email'    => $email,
            'password' => Hash::make($motDePasse),
            'role'     => User::ROLE_ORGANISATEUR,
        ]);

        $user->events()->attach($event->id, ['role' => 'proprietaire']);

        return ['user' => $user, 'motDePasse' => $motDePasse];
    }

    /** Garantit l'unicité du slug : clara-mael, clara-mael-2, etc. */
    private function slugLibre(string $base): string
    {
        $slug = Str::slug($base) ?: 'mariage';
        $essai = $slug;
        $n = 1;

        while (Event::withTrashed()->where('slug', $essai)->exists()) {
            $essai = $slug . '-' . (++$n);
        }

        return $essai;
    }
}
