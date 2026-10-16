<?php

namespace Database\Seeders;

use App\Models\DocumentCommercial;
use App\Models\Event;
use Illuminate\Database\Seeder;

/**
 * Le devis de Soizic & Nylan, aligné sur ce qui a réellement été préparé.
 *
 * Base : la formule Célébration (249 €). En plus : le thème sur mesure.
 * Offerts : le quiz des mariés et la configuration du site. En moins : une
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
        ['designation' => 'Quiz des mariés, à trois propositions',
         'quantite'    => 1, 'prix_unitaire_centimes' => 0],
        // Les éléments fournis par le couple (cadre, contenus) ne sont pas
        // facturés : leur mise en place est regroupée ici.
        ['designation' => 'Configuration',
         'quantite'    => 1, 'prix_unitaire_centimes' => 0],
    ];

    /**
     * Le devis n'a qu'une remise : les deux gestes y sont réunis, détaillés
     * dans le libellé. 50 € de modules non retenus, 10 % de la formule.
     */
    private const REMISE = [
        'remise_type'    => 'montant',
        'remise_valeur'  => 74.90,
        'remise_libelle' => 'Modules non retenus (50 €) et 10 % sur la formule (24,90 €)',
    ];

    /** Les délais, calés sur le mariage du samedi 24 octobre 2026. */
    private const VALIDITE = '2026-10-20';

    private const ACOMPTE_POURCENT = 30;

    private const SOLDE_AVANT = '2026-10-23';

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
            'valide_jusqu_au' => self::VALIDITE,
        ] + self::REMISE;

        if ($devis) {
            $devis->update($contenu);
            $devis->update(['notes' => $this->conditions($devis)]);
        } else {
            $devis = DocumentCommercial::create($contenu + [
                'type'       => DocumentCommercial::DEVIS,
                'statut'     => 'brouillon',
                'event_id'   => $event->id,
                'client_nom' => 'Soizic & Nylan',
            ]);
            $devis->update(['notes' => $this->conditions($devis)]);
        }

        $this->command?->info('  ' . str_replace("
", ' ', $devis->notes));
        $this->command?->info(sprintf(
            '%s : %s, remise %s, total %s.',
            $devis->libelle(),
            DocumentCommercial::euros($devis->sous_total_centimes),
            DocumentCommercial::euros($devis->remise_centimes),
            DocumentCommercial::euros($devis->total_centimes),
        ));
    }

    /** Les conditions de règlement, chiffrées sur le total du devis. */
    private function conditions(DocumentCommercial $devis): string
    {
        $acompte = (int) round($devis->total_centimes * self::ACOMPTE_POURCENT / 100);
        $solde   = \Illuminate\Support\Carbon::parse(self::SOLDE_AVANT)->translatedFormat('j F Y');

        return 'Règlement : acompte de ' . self::ACOMPTE_POURCENT . ' % à la signature ('
            . DocumentCommercial::euros($acompte) . '), solde de '
            . DocumentCommercial::euros($devis->total_centimes - $acompte)
            . " au plus tard le {$solde}, veille du mariage.";
    }
}
