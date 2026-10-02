<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Models\Event;
use App\Models\LivreOr;
use App\Models\Participant;
use App\Models\Photo;
use App\Models\Plan;
use App\Models\UrneDon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * La console de l'équipe Solen : tous les mariages, d'un seul endroit.
 *
 * Distincte de l'espace client, qui ne voit qu'un mariage à la fois.
 */
class ConsoleController extends Controller
{
    public function index(Request $request): View
    {
        $recherche = trim((string) $request->query('q'));

        $mariages = Event::query()
            ->with('theme')
            // LOWER + LIKE plutôt que ILIKE : ce dernier n'existe que sous
            // PostgreSQL, et les tests tournent sur SQLite.
            ->when($recherche, function ($q) use ($recherche) {
                $motif = '%' . mb_strtolower($recherche) . '%';

                $q->where(function ($q) use ($motif) {
                    $q->whereRaw('LOWER(nom) LIKE ?', [$motif])
                      ->orWhereRaw('LOWER(slug) LIKE ?', [$motif])
                      ->orWhereRaw('LOWER(COALESCE(lieu_ville, \'\')) LIKE ?', [$motif]);
                });
            })
            ->orderByRaw('date_principale is null')
            ->orderBy('date_principale')
            ->get();

        return view('console.index', [
            'mariages'  => $mariages,
            'recherche' => $recherche,
            'chiffres'  => $this->chiffres($mariages),
        ]);
    }

    /**
     * Chiffres transverses. Les modèles cloisonnés doivent explicitement
     * sortir de leur scope, sinon ils ne compteraient qu'un seul mariage.
     *
     * @param  \Illuminate\Support\Collection<int, Event>  $mariages
     * @return list<array{nombre: string|int, quoi: string}>
     */
    private function chiffres($mariages): array
    {
        $encaisse = UrneDon::tousEvenements()->where('statut', 'payé')->sum('montant');

        return [
            ['nombre' => $mariages->count(),                                      'quoi' => 'mariages'],
            ['nombre' => $mariages->where('statut', Event::STATUT_PUBLIE)->count(), 'quoi' => 'en ligne'],
            ['nombre' => Participant::tousEvenements()->count(),                  'quoi' => 'invités'],
            ['nombre' => number_format((float) $encaisse, 0, ',', ' ') . ' €',    'quoi' => 'de cagnottes'],
        ];
    }

    /** Fiche d'un mariage, vue depuis la console. */
    public function montrer(string $slug): View
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        return view('console.mariage', [
            'mariage'   => $event,
            'formules'  => Plan::actifs()->get(),
            'detail'    => [
                ['nombre' => Participant::pourEvenement($event)->count(),   'quoi' => 'invités'],
                ['nombre' => Photo::pourEvenement($event)->count(),         'quoi' => 'photos'],
                ['nombre' => LivreOr::pourEvenement($event)->count(),       'quoi' => 'messages'],
                ['nombre' => ContentBlock::pourEvenement($event)->count(),  'quoi' => 'blocs de contenu'],
            ],
            'avancement'    => app(\App\Solen\AvancementMariage::class)->pour($event),
            'membres'       => $event->users()->orderBy('name')->get(),
            'modulesActifs' => $event->modules()->wherePivot('actif', true)->count(),
            'modulesTotal'  => $event->modules()->count(),
        ]);
    }
}
