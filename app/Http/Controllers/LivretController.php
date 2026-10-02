<?php

namespace App\Http\Controllers;

use App\Solen\CurrentEvent;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Le livret de cérémonie : un PDF déposé par les mariés, lu dans une liseuse.
 *
 * Remplace le déroulé en direct, qui exigeait que quelqu'un marque chaque
 * étape pendant la cérémonie. Ici, chacun tourne les pages à son rythme.
 */
class LivretController extends Controller
{
    public function __invoke(CurrentEvent $courant): View
    {
        $event = $courant->get();

        abort_unless($event->aModule('livret'), 404);

        $pdf = $event->reglage('livret', 'pdf');

        abort_unless($pdf && Storage::disk('public')->exists($pdf), 404);

        return view('pages.livret', [
            'titre'          => $event->reglage('livret', 'titre', 'Livret de cérémonie'),
            'url'            => Storage::url($pdf),
            'telechargeable' => (bool) $event->reglage('livret', 'telechargeable', true),
        ]);
    }
}
