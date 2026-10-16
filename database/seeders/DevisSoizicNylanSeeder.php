<?php

namespace Database\Seeders;

use App\Models\DocumentCommercial;
use App\Models\Event;
use Illuminate\Database\Seeder;

/**
 * Le devis de Soizic & Nylan, aligné sur ce qui a réellement été préparé.
 *
 * Base : la formule Célébration (249 €). En plus : le thème sur mesure.
 * Offert et listé : les développements faits pour eux. En moins : une
 * remise forfaitaire pour les modules de la formule qu'ils n'utilisent pas.
 *
 * Rejouable : le devis du mariage est retrouvé et mis à jour, ou créé en
 * brouillon. Un devis déjà accepté ou facturé n'est jamais touché.
 *
 *   php artisan db:seed --class=DevisSoizicNylanSeeder --force
 */
class DevisSoizicNylanSeeder extends Seeder
{
    private const SLUG = 'soizic-nylan';

    private const LIGNES = [
        ['designation' => 'Formule Célébration',
         'detail'      => 'Retenus : mini-site, compte à rebours, notre histoire, infos pratiques, galerie et photobooth sans limite, quiz, puzzle, mots croisés, urne. Non retenus : RSVP, livre d’or, menu, livret, « Une pensée pour », QR codes, Memory, Qui de nous 2.',
         'quantite'    => 1, 'prix_unitaire_centimes' => 24900],
        ['designation' => 'Thème sur mesure',
         'detail'      => 'Couleurs, typographies, fleurs et visuels du faire-part (prénoms, date)',
         'quantite'    => 1, 'prix_unitaire_centimes' => 8900],
        ['designation' => 'Cadre du photobooth à vos couleurs',
         'quantite'    => 1, 'prix_unitaire_centimes' => 0],
        ['designation' => 'Quiz des mariés, à trois propositions',
         'quantite'    => 1, 'prix_unitaire_centimes' => 0],
        ['designation' => 'Urne reliée à votre cagnotte Leetchi',
         'quantite'    => 1, 'prix_unitaire_centimes' => 0],
        ['designation' => 'Programme intégré aux infos pratiques',
         'quantite'    => 1, 'prix_unitaire_centimes' => 0],
        ['designation' => 'Notre histoire : votre poème en frise, avec vos photos',
         'quantite'    => 1, 'prix_unitaire_centimes' => 0],
    ];

    private const REMISE = [
        'remise_type'    => 'montant',
        'remise_valeur'  => 50,
        'remise_libelle' => 'Modules non retenus',
    ];

    public function run(): void
    {
        $event = Event::where('slug', self::SLUG)->first();

        if (! $event) {
            $this->command?->warn('Mariage soizic-nylan introuvable : lancez d’abord MariageSoizicNylanSeeder.');

            return;
        }

        $devis = DocumentCommercial::where('type', DocumentCommercial::DEVIS)
            ->where('event_id', $event->id)
            ->latest('id')
            ->first();

        if ($devis && in_array($devis->statut, ['accepte', 'converti'], true)) {
            $this->command?->warn("Devis {$devis->numero} déjà {$devis->statut} : non modifié.");

            return;
        }

        $contenu = [
            'objet'           => 'Mariage Soizic & Nylan — 24 octobre 2026',
            'lignes'          => self::LIGNES,
            'valide_jusqu_au' => $devis?->valide_jusqu_au ?? now()->addDays(15)->toDateString(),
        ] + self::REMISE;

        if ($devis) {
            $devis->update($contenu);
        } else {
            $devis = DocumentCommercial::create($contenu + [
                'type'       => DocumentCommercial::DEVIS,
                'statut'     => 'brouillon',
                'event_id'   => $event->id,
                'client_nom' => 'Soizic & Nylan',
            ]);
        }

        $this->command?->info(sprintf(
            '%s : %s, remise %s, total %s.',
            $devis->libelle(),
            DocumentCommercial::euros($devis->sous_total_centimes),
            DocumentCommercial::euros($devis->remise_centimes),
            DocumentCommercial::euros($devis->total_centimes),
        ));
    }
}
