<?php

namespace Database\Seeders;

use App\Models\ContentBlock;
use App\Models\Event;
use App\Models\EventPart;
use App\Models\LivreOr;
use App\Models\MemoryCard;
use App\Models\MotCroise;
use App\Models\MotsCroises;
use App\Models\Participant;
use App\Models\QuestionQuiDeux;
use App\Models\ReponseQuiDeux;
use App\Models\SessionJeu;
use App\Solen\CurrentEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder as BaseSeeder;

/**
 * Remplit un mariage de démonstration de bout en bout : contenu, invités,
 * messages, et surtout les JEUX, qui sans données ne montrent rien.
 *
 * Usage :
 *   php artisan db:seed --class=DemonstrationSeeder
 *   SOLEN_DEMO_SLUG=un-autre-mariage php artisan db:seed --class=DemonstrationSeeder
 *
 * Idempotent : il vide ce qu'il gère avant de le recréer.
 */
class DemonstrationSeeder extends Seeder
{
    private const HISTOIRE = [
        ['titre' => 'Le premier regard', 'date' => 'Mars 2017',
         'texte' => "Une soirée d'anniversaire à laquelle aucun des deux ne voulait aller. Clara est arrivée en retard, Maël s'apprêtait à partir. Ils ont parlé jusqu'à quatre heures du matin."],
        ['titre' => 'Le premier voyage', 'date' => 'Été 2018',
         'texte' => "Trois semaines au Portugal avec un sac trop lourd et aucun plan. On a découvert qu'on savait se supporter, même perdus, même trempés, même affamés."],
        ['titre' => 'Notre appartement', 'date' => 'Janvier 2021',
         'texte' => "Quarante-deux mètres carrés, un radiateur capricieux et une vue sur les toits. On y a été très heureux."],
        ['titre' => 'La demande', 'date' => 'Août 2025',
         'texte' => "Au Cap Ferret, à marée basse, sans témoin et sans discours préparé. La réponse a mis une demi-seconde."],
    ];

    private const MENU = [
        ['categorie' => 'cocktail', 'nom' => 'Huîtres du Bassin',        'description' => 'Ouvertes à la demande, échalote et citron.', 'allergenes' => 'mollusques'],
        ['categorie' => 'cocktail', 'nom' => 'Gougères au comté',        'description' => 'Servies tièdes.',                            'allergenes' => 'gluten, lait, œuf'],
        ['categorie' => 'entree',   'nom' => 'Tartare de daurade',       'description' => 'Agrumes, aneth, huile d’olive.',             'allergenes' => 'poisson'],
        ['categorie' => 'plat',     'nom' => 'Filet de bœuf',            'description' => 'Jus corsé, pommes grenaille, légumes de saison.'],
        ['categorie' => 'plat',     'nom' => 'Risotto d’automne',        'description' => 'Option végétarienne : champignons et parmesan.', 'allergenes' => 'lait'],
        ['categorie' => 'fromage',  'nom' => 'Plateau affiné',           'description' => 'Cinq fromages, pain aux noix.',              'allergenes' => 'lait, gluten, fruits à coque'],
        ['categorie' => 'dessert',  'nom' => 'Pièce montée',             'description' => 'Choux vanille et caramel.',                  'allergenes' => 'gluten, lait, œuf'],
        ['categorie' => 'boisson',  'nom' => 'Vins du Bordelais',        'description' => 'Sélection blanche et rouge, et de quoi trinquer sans alcool.'],
    ];

    private const HEBERGEMENTS = [
        ['nom' => 'Hôtel des Pins',        'adresse' => '12 avenue de l’Océan, Arcachon', 'telephone' => '05 56 00 00 01',
         'prix' => 'à partir de 110 €', 'distance' => '5 min à pied', 'code_promo' => 'CLARAMAEL'],
        ['nom' => 'Villa Marine',          'adresse' => '3 rue du Port, Arcachon',        'telephone' => '05 56 00 00 02',
         'prix' => 'à partir de 85 €',  'distance' => '10 min à pied'],
        ['nom' => 'Camping de la Dune',    'adresse' => 'Route de Biscarrosse',           'telephone' => '05 56 00 00 03',
         'prix' => 'à partir de 40 €',  'distance' => '15 min en voiture'],
    ];

    private const TRANSPORTS = [
        ['titre' => 'Parking', 'mode' => 'voiture',
         'description' => 'Parking gratuit et surveillé sur place, 80 places.'],
        ['titre' => 'Navette depuis la gare', 'mode' => 'navette',
         'description' => 'Deux navettes gratuites : 14 h et 16 h 30, devant la gare d’Arcachon. Retour à 1 h et 3 h.',
         'telephone' => '06 12 34 56 78'],
        ['titre' => 'Taxis Bassin', 'mode' => 'taxi',
         'telephone' => '05 56 00 00 09', 'whatsapp' => '33612345678'],
    ];

    private const CONTACTS = [
        ['nom' => 'Léa', 'role' => 'Témoin de Clara', 'telephone' => '06 11 22 33 44', 'whatsapp' => '33611223344', 'email' => 'lea@exemple.fr'],
        ['nom' => 'Hugo', 'role' => 'Témoin de Maël', 'telephone' => '06 55 66 77 88', 'email' => 'hugo@exemple.fr'],
    ];

    private const INVITES = [
        ['Léa', 'Bertrand'], ['Hugo', 'Marchand'], ['Sophie', 'Nguyen'], ['Karim', 'Belaïd'],
        ['Camille', 'Rousseau'], ['Thomas', 'Lefèvre'], ['Inès', 'Da Silva'], ['Antoine', 'Perrin'],
        ['Nadia', 'Toumi'], ['Julien', 'Girard'], ['Chloé', 'Meunier'], ['Marc', 'Oliveira'],
    ];

    private const MESSAGES = [
        'Quelle journée ! Merci de nous avoir accueillis, on ne l’oubliera pas.',
        'Vous deux, c’était une évidence depuis le début. Tous nos vœux.',
        'La cérémonie était bouleversante. Bravo pour vos vœux, Clara.',
        'Le risotto valait le déplacement à lui seul. Et vous aussi, bien sûr.',
        'À votre bonheur, aujourd’hui et pour longtemps.',
        'Merci pour la piste de danse. Mes pieds s’en souviennent encore.',
    ];

    /** Le jeu « Qui de nous 2 » : la réponse est un des deux prénoms. */
    private const QUESTIONS = [
        ['Qui a dit « je t’aime » en premier ?',            'Maël'],
        ['Qui se lève le plus tôt le week-end ?',           'Clara'],
        ['Qui a le pire sens de l’orientation ?',           'Maël'],
        ['Qui cuisine le dimanche ?',                       'Clara'],
        ['Qui a organisé le voyage au Portugal ?',          'Clara'],
        ['Qui chante faux sous la douche ?',                'Maël'],
        ['Qui perd toujours ses clés ?',                    'Maël'],
        ['Qui a choisi le lieu du mariage ?',               'Clara'],
    ];

    private const CHASSE = [
        'La plus belle paire de chaussures de la soirée',
        'Trois générations sur une même photo',
        'Quelqu’un qui rit aux éclats',
        'Le plus beau chapeau',
        'Les mariés surpris sans qu’ils vous voient',
        'Une table entière, tout le monde dedans',
    ];

    private const MOTS = [
        ['mot' => 'ALLIANCE', 'definition' => 'Ce qu’on échange au doigt', 'x' => 0, 'y' => 2, 'direction' => 'horizontal'],
        ['mot' => 'AMOUR',    'definition' => 'La raison de tout ceci',    'x' => 2, 'y' => 0, 'direction' => 'vertical'],
        ['mot' => 'TEMOIN',   'definition' => 'Il signe et il raconte',    'x' => 1, 'y' => 5, 'direction' => 'horizontal'],
        ['mot' => 'DANSE',    'definition' => 'Ce qui ouvre le bal',       'x' => 6, 'y' => 1, 'direction' => 'vertical'],
        ['mot' => 'BOUQUET',  'definition' => 'On le lance derrière soi',  'x' => 0, 'y' => 8, 'direction' => 'horizontal'],
    ];

    private const MEMORY = [
        'anneaux' => 'Les alliances', 'baiser' => 'Le baiser', 'eglise' => 'La cérémonie',
        'livret'  => 'Le livret',     'photo'  => 'Le photobooth', 'mairie' => 'La mairie',
    ];

    public function run(): void
    {
        $slug  = env('SOLEN_DEMO_SLUG', 'clara-et-mael');
        $event = Event::where('slug', $slug)->first();

        if (! $event) {
            $this->command?->error("Mariage « {$slug} » introuvable. Créez-le d’abord depuis /console.");

            return;
        }

        app(CurrentEvent::class)->pretend($event, function () use ($event) {
            $this->lieux($event);
            $this->contenu();
            $invites = $this->invites();
            $this->livreDor($invites);
            $this->jeux($event, $invites);
        });

        $this->command?->info("Mariage « {$event->nom} » rempli et prêt à être montré.");
    }

    // ── Lieux et horaires ────────────────────────────────────────────────

    private function lieux(Event $event): void
    {
        $lieux = [
            'mairie'      => ['lieu_nom' => 'Mairie d’Arcachon',   'lieu_adresse' => 'Place Lucien de Gracia, 33120 Arcachon', 'debut_at' => '11:00'],
            'ceremonie'   => ['lieu_nom' => 'Chapelle des Dunes',  'lieu_adresse' => '18 boulevard de la Plage, 33120 Arcachon', 'accueil_at' => '14:30', 'debut_at' => '15:00'],
            'vin-honneur' => ['lieu_nom' => 'Villa Sablonne',      'lieu_adresse' => 'Route du Cap Ferret, 33970 Lège', 'debut_at' => '17:00'],
            'diner'       => ['lieu_nom' => 'Villa Sablonne',      'debut_at' => '20:00'],
            'soiree'      => ['lieu_nom' => 'Villa Sablonne',      'debut_at' => '23:00'],
        ];

        $jour = $event->date_principale?->format('Y-m-d') ?? now()->addMonths(3)->format('Y-m-d');

        foreach ($lieux as $cle => $donnees) {
            $partie = EventPart::where('cle', $cle)->first();

            if (! $partie) {
                continue;
            }

            foreach (['accueil_at', 'debut_at'] as $champ) {
                if (isset($donnees[$champ])) {
                    $donnees[$champ] = Carbon::parse("{$jour} {$donnees[$champ]}", $event->timezone)->utc();
                }
            }

            $partie->update($donnees);
        }
    }

    // ── Contenu éditorial ────────────────────────────────────────────────

    private function contenu(): void
    {
        $this->blocs('histoire', 'etape_histoire', self::HISTOIRE);
        $this->blocs('menu', 'plat', self::MENU);
        $this->blocs('pratique', 'hebergement', self::HEBERGEMENTS);
        $this->blocs('pratique', 'transport', self::TRANSPORTS);
        $this->blocs('pratique', 'contact', self::CONTACTS);
        $this->blocs('pratique', 'faq', [
            ['question' => 'Peut-on venir avec les enfants ?',
             'reponse'  => 'Bien sûr. Une garderie est prévue à partir de 21 h, avec deux animatrices.'],
            ['question' => 'Y a-t-il un dress code ?',
             'reponse'  => 'Tenue de cocktail. Évitez les talons fins : la réception est sur l’herbe et le sable.'],
            ['question' => 'À quelle heure se termine la soirée ?',
             'reponse'  => 'La musique s’arrête à 3 h. Les navettes repartent à 1 h et à 3 h.'],
        ]);
    }

    /** @param list<array<string, mixed>> $entrees */
    private function blocs(string $page, string $type, array $entrees): void
    {
        ContentBlock::where('page', $page)->where('type', $type)->delete();

        $depart = ContentBlock::where('page', $page)->max('ordre') ?? 0;

        foreach ($entrees as $i => $donnees) {
            ContentBlock::create([
                'page'    => $page,
                'type'    => $type,
                'donnees' => $donnees,
                'ordre'   => $depart + $i + 1,
                'actif'   => true,
            ]);
        }
    }

    // ── Invités et livre d'or ────────────────────────────────────────────

    /** @return \Illuminate\Support\Collection<int, Participant> */
    private function invites()
    {
        return collect(self::INVITES)->map(fn ($p) => Participant::firstOrCreate(
            ['prenom' => $p[0], 'nom' => $p[1]],
            ['email' => mb_strtolower($p[0]) . '@exemple.fr'],
        ));
    }

    private function livreDor($invites): void
    {
        LivreOr::query()->delete();

        foreach (self::MESSAGES as $i => $texte) {
            LivreOr::create([
                'participant_id' => $invites[$i % $invites->count()]->id,
                'message'        => $texte,
            ]);
        }

        $this->command?->info('  ' . count(self::MESSAGES) . ' messages au livre d’or.');
    }

    // ── Jeux ─────────────────────────────────────────────────────────────

    private function jeux(Event $event, $invites): void
    {
        $this->quiDeux($invites);
        $this->chassePhoto();
        $this->motsCroises();
        $this->memory();

        $this->command?->info('  4 jeux prêts à jouer.');
    }

    private function quiDeux($invites): void
    {
        ReponseQuiDeux::query()->delete();
        QuestionQuiDeux::query()->delete();
        SessionJeu::where('type_jeu', 'qui_deux')->delete();

        $session = SessionJeu::create([
            'nom'         => 'Qui de nous 2 ?',
            'description' => 'Testez ce que vous savez vraiment du couple.',
            'type_jeu'    => 'qui_deux',
            'actif'       => true,
            'debut'       => now(),
        ]);

        foreach (self::QUESTIONS as [$question, $reponse]) {
            $q = QuestionQuiDeux::create([
                'question'      => $question,
                'bonne_reponse' => $reponse,
                'active'        => true,
            ]);

            // Quelques réponses déjà données, pour que les statistiques
            // ne soient pas vides à la démonstration.
            foreach ($invites->take(5) as $index => $invite) {
                $choix = ($index % 3 === 0) ? $this->autreQue($reponse) : $reponse;

                ReponseQuiDeux::create([
                    'participant_id' => $invite->id,
                    'question_id'    => $q->id,
                    'reponse'        => $choix,
                    'correct'        => $choix === $reponse,
                    'session_jeu_id' => $session->id,
                ]);
            }
        }
    }

    private function autreQue(string $prenom): string
    {
        return $prenom === 'Clara' ? 'Maël' : 'Clara';
    }

    private function chassePhoto(): void
    {
        SessionJeu::where('type_jeu', 'chasse_photo')->delete();

        SessionJeu::create([
            'nom'         => 'Chasse photo',
            'description' => implode(' · ', self::CHASSE),
            'type_jeu'    => 'chasse_photo',
            'actif'       => true,
            'debut'       => now(),
        ]);
    }

    private function motsCroises(): void
    {
        MotsCroises::query()->each(fn ($g) => $g->mots()->delete());
        MotsCroises::query()->delete();
        SessionJeu::where('type_jeu', 'mots_croises')->delete();

        $grille = MotsCroises::create([
            'titre'       => 'Les mots du mariage',
            'description' => 'Neuf cases de côté, cinq mots à retrouver.',
            'taille'      => 10,
            'actif'       => true,
        ]);

        foreach (self::MOTS as $mot) {
            MotCroise::create([
                'mots_croise_id' => $grille->id,
                'mot'            => $mot['mot'],
                'definition'     => $mot['definition'],
                'position_x'     => $mot['x'],
                'position_y'     => $mot['y'],
                'direction'      => $mot['direction'],
            ]);
        }

        SessionJeu::create([
            'nom'      => 'Mots croisés',
            'type_jeu' => 'mots_croises',
            'actif'    => true,
            'debut'    => now(),
        ]);
    }

    private function memory(): void
    {
        MemoryCard::query()->delete();
        SessionJeu::where('type_jeu', 'memory')->delete();

        foreach (self::MEMORY as $fichier => $titre) {
            // Deux cartes par paire, c'est le principe du jeu.
            foreach ([1, 2] as $exemplaire) {
                MemoryCard::create([
                    'titre'       => $titre,
                    'description' => $titre,
                    'image_path'  => "/images/memory-cards/{$fichier}.jpg",
                    'pair_id'     => $fichier,
                    'actif'       => true,
                ]);
            }
        }

        SessionJeu::create([
            'nom'      => 'Memory',
            'type_jeu' => 'memory',
            'actif'    => true,
            'debut'    => now(),
        ]);
    }
}
