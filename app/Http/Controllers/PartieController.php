<?php

namespace App\Http\Controllers;

use App\Models\EventPart;
use App\Models\Remerciement;
use App\Solen\CurrentEvent;
use Illuminate\View\View;

/**
 * Une page par moment de la journée.
 *
 * Remplace les vues « mairie » et « ceremonie » qui décrivaient en dur
 * l'église Saint-Laurent et le Domaine de l'Apaloosa. La même vue sert
 * désormais la mairie, la cérémonie, le vin d'honneur, la soirée ou le
 * brunch : c'est le contenu qui change, pas le code.
 */
class PartieController extends Controller
{
    public function __invoke(string $cle, CurrentEvent $courant): View
    {
        $partie = EventPart::actives()->where('cle', $cle)->firstOrFail();
        $event  = $courant->get();

        $estCeremonie = in_array($partie->type_ceremonie, ['civil', 'catholique', 'evangelique', 'laique'], true);

        // Le livret PDF n'est proposé que sur un moment de cérémonie.
        $livret = $estCeremonie
            && $event->aModule('livret')
            && $event->reglage('livret', 'pdf');

        return view('pages.partie', [
            'partie'        => $partie,
            'etapes'        => $partie->etapes()->get(),
            'livret'        => (bool) $livret,
            'remerciements' => $estCeremonie && $partie->type_ceremonie !== 'civil' ? Remerciement::first() : null,
        ]);
    }
}
