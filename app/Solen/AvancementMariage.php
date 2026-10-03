<?php

namespace App\Solen;

use App\Models\ContentBlock;
use App\Models\Event;
use Illuminate\Support\Collection;

/**
 * Où en est un couple dans la préparation de son site.
 *
 * Sert à afficher une progression sur le tableau de bord. C'est ce qui fait
 * revenir : voir « 60 % » donne envie d'aller chercher les 40 % restants,
 * là où une liste de tâches sans fin décourage.
 *
 * Les étapes sont ordonnées de la plus structurante à la plus accessoire, et
 * seules celles qui concernent la formule du couple sont comptées.
 */
class AvancementMariage
{
    /**
     * @return array{pourcentage: int, faites: int, total: int, etapes: Collection<int, array>}
     */
    public function pour(Event $event): array
    {
        // Quelle prise en charge Solen couvre quelle étape.
        $couverture = [
            'lieux' => 'programme', 'histoire' => 'contenu', 'pratique' => 'contenu',
            'menu'  => 'contenu',   'apparence' => 'apparence',
        ];

        $etapes = collect($this->etapes($event))
            ->filter(fn (array $e) => $e['concerne'] ?? true)
            ->map(fn (array $e) => $e + [
                'solen' => isset($couverture[$e['cle']]) && $event->accompagne($couverture[$e['cle']]),
            ])
            ->values();

        $faites = $etapes->where('fait', true)->count();
        $total  = max($etapes->count(), 1);

        return [
            'pourcentage' => (int) round($faites / $total * 100),
            'faites'      => $faites,
            'total'       => $etapes->count(),
            'etapes'      => $etapes,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function etapes(Event $event): array
    {
        $partiesSituees = $event->parts()->whereNotNull('lieu_nom')->exists();

        return [
            [
                'cle'   => 'date',
                'titre' => 'Indiquer la date du mariage',
                'aide'  => 'Elle pilote le compte à rebours de vos invités.',
                'fait'  => (bool) $event->date_principale,
                'route' => 'espace.informations',
            ],
            [
                'cle'   => 'lieux',
                'titre' => 'Renseigner les lieux et les horaires',
                'aide'  => 'Chaque moment de la journée a besoin de son adresse.',
                'fait'  => $partiesSituees,
                'route' => 'espace.programme',
            ],
            [
                'cle'   => 'histoire',
                'titre' => 'Raconter votre histoire',
                'aide'  => 'La page que vos invités liront en premier.',
                'fait'  => ContentBlock::pourEvenement($event)->where('page', 'histoire')->exists(),
                'route' => 'espace.pages',
                'concerne' => $event->aModule('histoire'),
            ],
            [
                'cle'   => 'pratique',
                'titre' => 'Ajouter les infos pratiques',
                'aide'  => 'Hébergement, transport, contact du jour J.',
                'fait'  => ContentBlock::pourEvenement($event)->where('page', 'pratique')->exists(),
                'route' => 'espace.pages',
                'concerne' => $event->aModule('pratique'),
            ],
            [
                'cle'   => 'menu',
                'titre' => 'Composer le menu',
                'aide'  => 'Vos invités pourront vous signaler leurs allergies.',
                'fait'  => ContentBlock::pourEvenement($event)->where('page', 'menu')->exists(),
                'route' => 'espace.pages',
                'concerne' => $event->aModule('menu'),
            ],
            [
                'cle'   => 'cagnotte',
                'titre' => 'Raccorder votre compte bancaire',
                'aide'  => 'Sans cela, la cagnotte reste fermée.',
                // Une cagnotte tenue ailleurs (Leetchi…) ne demande aucun raccordement.
                'fait'  => (bool) $event->stripe_paiements_actifs || (bool) $event->reglage('cagnotte', 'lien_externe'),
                'route' => 'espace.paiements',
                'concerne' => $event->aModule('cagnotte'),
            ],
            [
                'cle'   => 'apparence',
                'titre' => 'Choisir votre thème',
                'aide'  => 'Les couleurs de votre site et de vos photos.',
                'fait'  => (bool) $event->theme_id,
                'route' => 'espace.informations',
            ],
            [
                'cle'   => 'publier',
                'titre' => 'Publier votre site',
                'aide'  => 'Tant qu’il est en brouillon, vos invités n’y accèdent pas.',
                'fait'  => $event->estPublie(),
                'route' => 'espace.informations',
            ],
        ];
    }
}
