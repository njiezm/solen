<?php

namespace Database\Seeders;

use App\Models\ContentBlock;
use App\Models\Event;
use App\Solen\CurrentEvent;
use Illuminate\Database\Seeder;

/**
 * Reprend le contenu jusqu'ici écrit en dur et le bascule en blocs éditables.
 *
 * Le contenu réel se trouvait dans les VUES, pas dans les contrôleurs : les
 * tableaux de PratiqueController n'étaient qu'un exemple jamais affiché, la
 * page rendait en fait des hôtels et un contact codés dans le Blade. Ce sont
 * ces valeurs-là qui sont reprises ici.
 */
class ContenuInitialSeeder extends Seeder
{
    /** Repris de PageController::penseePour(). */
    private const HOMMAGES = [
        ['nom' => 'Guy-Albert ZAMON', 'dates' => '1964 – 2025', 'photo' => 'images/decedents/gaz.jpg',
         'message' => 'Un père aimant, protecteur et attentionné. À jamais dans nos cœurs. Un beau-père exceptionnel.'],
        ['nom' => 'Elvire MAXIMIN', 'dates' => '1965 – 2025', 'photo' => 'images/decedents/elvire.jpg',
         'message' => 'Toujours dans nos cœurs, ma titie d’amour, on ne t’oublie pas.'],
        ['nom' => 'Isis LABYLLE', 'dates' => '1941 – 2017', 'photo' => 'images/decedents/isis.jpg',
         'message' => 'Ma mamie, de très fortes pensées vers toi. Je t’aime fort !'],
        ['nom' => 'Tertulien BUVAL', 'dates' => '1939 – 2003', 'photo' => 'images/decedents/buval.jpg',
         'message' => 'Papi, repose en paix. Une étoile qui brille dans le ciel.'],
    ];

    /** Repris de la vue pages/details-pratiques.blade.php. */
    private const HEBERGEMENTS = [
        ['nom' => 'Hôtel Plein Soleil'],
        ['nom' => 'La Frégate Bleue'],
        ['nom' => 'Village de la Pointe'],
    ];

    private const TRANSPORTS = [
        [
            'titre'       => 'Parking',
            'mode'        => 'voiture',
            'description' => 'Un parking gratuit est disponible sur le site du Domaine de l’Apaloosa pour tous les invités.',
        ],
        [
            'titre'     => 'Martinique Taxi',
            'mode'      => 'taxi',
            'telephone' => '+596 596 63 63 62',
            'whatsapp'  => '596596636362',
            'lien'      => 'https://www.martiniquetaxi.com',
        ],
    ];

    private const CONTACTS = [
        [
            'nom'       => 'Jade',
            'role'      => 'Personne ressource',
            'telephone' => '06 96 38 80 72',
            'whatsapp'  => '596696388072',
            'email'     => 'jade.buval@gmail.com',
        ],
    ];

    public function run(): void
    {
        $event = Event::where('slug', 'maeva-gilles')->first();

        if (! $event) {
            $this->command?->warn('Événement maeva-gilles introuvable.');

            return;
        }

        app(CurrentEvent::class)->pretend($event, function () use ($event) {
            $ajoutes = $event->appliquerFormule();
            $this->command?->info("{$ajoutes} module(s) rattaché(s) à la formule « {$event->plan} ».");

            $this->reglerModules($event);

            $this->remplacer('hommage',  'defunt',      self::HOMMAGES);
            $this->remplacer('pratique', 'hebergement', self::HEBERGEMENTS);
            $this->remplacer('pratique', 'transport',   self::TRANSPORTS);
            $this->remplacer('pratique', 'contact',     self::CONTACTS);
        });
    }

    /** Les réglages qui étaient eux aussi figés dans les vues. */
    private function reglerModules(Event $event): void
    {
        $this->regler($event, 'site', [
            'dress_code' => 'Chic & Élégant — vert sapin, rouille et blanc',
        ]);

        $this->regler($event, 'hommage', [
            'titre' => 'Une pensée pour…',
        ]);
    }

    /** @param  array<string, mixed>  $valeurs */
    private function regler(Event $event, string $cleModule, array $valeurs): void
    {
        $module = $event->modules()->where('cle', $cleModule)->first();

        if (! $module) {
            return;
        }

        $actuel = $module->pivot->config;
        $actuel = is_string($actuel) ? (json_decode($actuel, true) ?: []) : ($actuel ?? []);

        $event->modules()->updateExistingPivot($module->id, [
            'config' => json_encode(array_merge($actuel, $valeurs)),
        ]);
    }

    /**
     * Remplace les blocs d'un type donné. Un seeder initial : on veut un
     * état connu, pas une accumulation à chaque exécution.
     *
     * @param  list<array<string, mixed>>  $entrees
     */
    private function remplacer(string $page, string $type, array $entrees): void
    {
        ContentBlock::where('page', $page)->where('type', $type)->delete();

        $ordre = ContentBlock::where('page', $page)->max('ordre') ?? 0;

        foreach ($entrees as $donnees) {
            ContentBlock::create([
                'page'    => $page,
                'type'    => $type,
                'donnees' => $donnees,
                'ordre'   => ++$ordre,
                'actif'   => true,
            ]);
        }

        $this->command?->info('  ' . count($entrees) . " bloc(s) « {$type} » sur la page {$page}.");
    }
}
