<?php

namespace App\Http\Controllers;

use App\Models\EtapeCeremonie;
use App\Models\EventPart;
use App\Solen\CurrentEvent;
use Illuminate\Http\JsonResponse;

/**
 * L'état du mariage en temps réel.
 *
 * Interrogé toutes les quelques secondes par les pages du site invité. Le
 * choix du sondage plutôt que des WebSockets est assumé : aucune infra
 * supplémentaire, ça fonctionne sur n'importe quel hébergement, et quelques
 * secondes de latence sont imperceptibles pendant une cérémonie.
 *
 * La réponse porte une empreinte : tant qu'elle ne change pas, la page n'a
 * rien à redessiner.
 */
class DirectController extends Controller
{
    public function __invoke(CurrentEvent $courant, ?string $cle = null): JsonResponse
    {
        $event = $courant->get();

        if (! $event->aModule('deroule')) {
            return response()->json(['actif' => false]);
        }

        $etapes = EtapeCeremonie::query()
            ->when($cle, function ($q) use ($cle) {
                $partie = EventPart::where('cle', $cle)->first();

                return $partie ? $q->where('event_part_id', $partie->id) : $q;
            })
            ->ordonnees()
            ->get(['id', 'titre', 'en_cours', 'termine', 'event_part_id']);

        $enCours = $etapes->firstWhere('en_cours', true);

        return response()->json([
            'actif'     => true,
            'empreinte' => md5($etapes->map(fn ($e) => $e->id . $e->statut())->implode('|')),
            'enCours'   => $enCours ? ['id' => $enCours->id, 'titre' => $enCours->titre] : null,
            'etapes'    => $etapes->map(fn ($e) => [
                'id'     => $e->id,
                'statut' => $e->statut(),
            ])->values(),
            'avancement' => $etapes->isEmpty()
                ? 0
                : (int) round($etapes->where('termine', true)->count() / $etapes->count() * 100),
        ])->header('Cache-Control', 'no-store');
    }
}
