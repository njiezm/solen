<?php

namespace App\Http\Controllers\Espace;

use App\Http\Controllers\Controller;
use App\Models\EtapeCeremonie;
use App\Models\LivreOr;
use App\Models\Photo;
use App\Solen\CurrentEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Livre souvenir imprimable.
 *
 * Assemble les messages, les photos et le déroulé en un PDF prêt à être
 * porté chez un imprimeur ou relié en ligne. Solen n'imprime pas : le
 * fichier appartient au couple, qui en fait ce qu'il veut.
 */
class LivreSouvenirController extends Controller
{
    public function index(CurrentEvent $courant): View
    {
        $event = $courant->get();

        abort_unless($event->aModule('pdf'), 404, 'Ce module n’est pas inclus dans votre formule.');

        return view('espace.livre-souvenir', [
            'messages' => LivreOr::publies()->count(),
            'photos'   => Photo::publies()->count(),
            'etapes'   => EtapeCeremonie::count(),
        ]);
    }

    /** Aperçu HTML : plus rapide à itérer qu'un PDF régénéré à chaque fois. */
    public function apercu(CurrentEvent $courant): View
    {
        $event = $courant->get();

        abort_unless($event->aModule('pdf'), 404);

        return view('pdf.livre-souvenir', $this->contenu($event, true) + ['apercu' => true]);
    }

    public function telecharger(CurrentEvent $courant): Response
    {
        $event = $courant->get();

        abort_unless($event->aModule('pdf'), 404);

        $format = $event->reglage('pdf', 'format', 'a5');

        $pdf = Pdf::loadView('pdf.livre-souvenir', $this->contenu($event, false) + ['apercu' => false])
            ->setPaper($format, 'portrait')
            ->setOptions([
                // Les photos vivent sur le disque public : DomPDF doit être
                // autorisé à les lire depuis le système de fichiers.
                'isRemoteEnabled'      => true,
                'isHtml5ParserEnabled' => true,
                'chroot'               => public_path(),
                'defaultFont'          => 'DejaVu Sans',
            ]);

        $nom = \Illuminate\Support\Str::slug($event->nom) . '-livre-souvenir.pdf';

        return $pdf->download($nom);
    }

    /**
     * Le contenu du livre. Les photos sont limitées : au-delà d'une centaine,
     * DomPDF s'effondre et le fichier devient inexploitable chez l'imprimeur.
     *
     * @return array<string, mixed>
     */
    private function contenu($event, bool $pourNavigateur): array
    {
        $avecPhotos = (bool) $event->reglage('pdf', 'inclure_photos', true);
        $couverture = $event->reglage('pdf', 'couverture');

        // Une image du disque public, sous la forme que chaque lecteur sait
        // ouvrir : son adresse web pour l'aperçu, ses octets pour DomPDF —
        // qui refuse de suivre le lien public/storage, hors de son « chroot ».
        $source = function (?string $chemin) use ($pourNavigateur): ?string {
            if (! $chemin || ! Storage::disk('public')->exists($chemin)) {
                return null;
            }

            if ($pourNavigateur) {
                return Storage::url($chemin);
            }

            $fichier = Storage::disk('public')->path($chemin);

            return 'data:' . (mime_content_type($fichier) ?: 'image/jpeg') . ';base64,' . base64_encode((string) file_get_contents($fichier));
        };

        return [
            'event'      => $event,
            'titre'      => $event->reglage('pdf', 'titre', 'Notre livre souvenir'),
            'dedicace'   => $event->reglage('pdf', 'dedicace'),
            'couverture' => $source($couverture),
            'messages'   => LivreOr::publies()->with('participant')->orderBy('created_at')->get(),
            'photos'     => $avecPhotos
                ? Photo::publies()->with('participant')->orderBy('created_at')->limit(100)->get()
                    ->each(fn ($photo) => $photo->setAttribute('source', $source($photo->path)))
                    ->filter(fn ($photo) => $photo->source)
                    ->values()
                : collect(),
            'etapes'     => (bool) $event->reglage('pdf', 'inclure_deroule', true)
                ? EtapeCeremonie::with('part')->ordonnees()->get()->groupBy(fn ($e) => $e->part?->nom ?? 'Déroulé')
                : collect(),
        ];
    }
}
