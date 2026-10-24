<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Solen\Exports;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Les exports d'un mariage, côté console : albums, livre d'or, livre
 * souvenir et résultats des jeux en PDF aux couleurs du thème, plus
 * l'archive des photos d'origine. Réservé à l'équipe Solen.
 */
class ExportsController extends Controller
{
    public function __construct(private readonly Exports $exports)
    {
    }

    public function index(string $slug): View
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        return view('console.exports', [
            'mariage' => $event,
            'types'   => Exports::TYPES,
            'compte'  => $this->exports->compter($event),
        ]);
    }

    public function pdf(string $slug, string $type): Response
    {
        $event = Event::where('slug', $slug)->firstOrFail();
        abort_unless(isset(Exports::TYPES[$type]), 404);

        return response($this->exports->pdf($event, $type), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => (request()->boolean('voir') ? 'inline' : 'attachment')
                . '; filename="' . $this->exports->nomFichier($event, $type) . '"',
        ]);
    }

    public function zip(string $slug): Response
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        return response($this->exports->zip($event), 200, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . Str::slug($event->nom) . '-photos.zip"',
        ]);
    }
}
