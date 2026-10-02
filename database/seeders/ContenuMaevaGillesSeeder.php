<?php

namespace Database\Seeders;

use App\Models\ContentBlock;
use App\Models\Event;
use App\Models\EventPart;
use App\Solen\CurrentEvent;
use Illuminate\Database\Seeder;

/**
 * Récupère le contenu du mariage Maëva & Gilles qui vivait dans les vues
 * Blade (frise de leur histoire, carte du repas, lieux et horaires) et le
 * transpose en données.
 *
 * Sans lui, la bascule des vues vers les blocs aurait fait disparaître leur
 * contenu réel. Ce seeder ne concerne que ce mariage-là.
 */
class ContenuMaevaGillesSeeder extends Seeder
{
    /** Repris de pages/histoire.blade.php. */
    private const HISTOIRE = [
        [
            'titre' => 'Une rencontre sur le sable',
            'date'  => '2014',
            'texte' => "C'est en 2014, lors d'un match de beach-volley entre amis, que nos chemins se sont croisés.\n\nNous ne savions pas encore que cette rencontre marquerait le début d'une aventure bien plus grande. Entre sourires, esprit d'équipe et légèreté, une connexion naturelle s'est installée.",
            'image' => 'images/histoire/avril-2016.jpg',
        ],
        [
            'titre' => 'Se découvrir, se rapprocher',
            'date'  => '2015 – 2017',
            'texte' => "Après cette première rencontre, les occasions de se revoir se multiplient. Les discussions s'allongent, les silences deviennent confortables, et l'évidence s'installe doucement.",
            'image' => 'images/histoire/juin-2017.jpg',
        ],
        [
            'titre' => 'Nos premiers voyages',
            'date'  => '2017',
            'texte' => "La Guadeloupe, puis d'autres horizons. Voyager ensemble nous a appris à nous connaître autrement.",
            'image' => 'images/histoire/guadeloupe-2017.jpg',
        ],
        [
            'titre' => 'La Barbade',
            'date'  => '2023',
            'texte' => "Une parenthèse à deux, loin de tout, qui a confirmé ce que nous savions déjà.",
            'image' => 'images/histoire/barbade-2023.jpg',
        ],
        [
            'titre' => 'Le grand jour',
            'date'  => '26 décembre 2025',
            'texte' => "Entourés de ceux que nous aimons, en Martinique.",
        ],
    ];

    /** Repris de pages/menu.blade.php. */
    private const MENU = [
        ['categorie' => 'entree',  'nom' => 'Soupe de pâté en pot',
         'description' => 'Soupe traditionnelle de pâté en pot de bœuf, délicatement parfumée.'],
        ['categorie' => 'entree',  'nom' => 'Buffet froid',
         'description' => 'Assortiment d’entrées fraîches et de spécialités créoles.'],
        ['categorie' => 'plat',    'nom' => 'Plat principal',
         'description' => 'Viande et poisson accompagnés de leurs garnitures créoles.'],
        ['categorie' => 'dessert', 'nom' => 'Pièce montée',
         'description' => 'Le gâteau des mariés, suivi d’un buffet de douceurs.'],
        ['categorie' => 'boisson', 'nom' => 'Punch et rhums arrangés',
         'description' => 'Sélection maison, avec et sans alcool.'],
    ];

    /** Lieux et horaires, jusqu'ici écrits dans les vues. */
    private const LIEUX = [
        'ceremonie' => [
            'lieu_nom'     => 'Église Saint-Laurent du Lamentin',
            'lieu_adresse' => '36 Rue Schoelcher, Le Lamentin 97232, Martinique',
            'accueil_at'   => '2025-12-26 13:30:00',
            'debut_at'     => '2025-12-26 14:00:00',
        ],
        'vin-honneur' => [
            'lieu_nom'     => 'Domaine de l’Apaloosa',
            'lieu_adresse' => 'Le François, Martinique',
            'debut_at'     => '2025-12-26 17:00:00',
        ],
        'diner' => [
            'lieu_nom' => 'Domaine de l’Apaloosa',
            'debut_at' => '2025-12-26 20:00:00',
        ],
        'soiree' => [
            'lieu_nom' => 'Domaine de l’Apaloosa',
            'debut_at' => '2025-12-26 22:30:00',
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
            $this->remplacer('histoire', 'etape_histoire', self::HISTOIRE);
            $this->remplacer('menu', 'plat', self::MENU);
            $this->completerLieux($event);
        });
    }

    /**
     * Les horaires sont saisis en heure de Martinique et stockés en UTC :
     * 14 h sur place, pas 14 h à Paris.
     */
    private function completerLieux(Event $event): void
    {
        foreach (self::LIEUX as $cle => $donnees) {
            $partie = EventPart::where('cle', $cle)->first();

            if (! $partie) {
                continue;
            }

            foreach (['accueil_at', 'debut_at'] as $champ) {
                if (isset($donnees[$champ])) {
                    $donnees[$champ] = \Illuminate\Support\Carbon::parse($donnees[$champ], $event->timezone)->utc();
                }
            }

            $partie->update($donnees);
        }

        $this->command?->info('  Lieux et horaires renseignés sur les parties.');
    }

    /** @param list<array<string, mixed>> $entrees */
    private function remplacer(string $page, string $type, array $entrees): void
    {
        ContentBlock::where('page', $page)->where('type', $type)->delete();

        foreach ($entrees as $index => $donnees) {
            ContentBlock::create([
                'page'    => $page,
                'type'    => $type,
                'donnees' => $donnees,
                'ordre'   => $index + 1,
                'actif'   => true,
            ]);
        }

        $this->command?->info('  ' . count($entrees) . " bloc(s) « {$type} » sur la page {$page}.");
    }
}
