<?php

namespace App\Http\Controllers;

use App\Models\ContentBlock;
use App\Solen\CurrentEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Les pages dont le contenu vient entièrement des blocs éditables.
 *
 * Rien n'est écrit ici ni dans les vues correspondantes : ni les étapes de
 * l'histoire du couple, ni les plats du menu, ni les personnes disparues.
 */
class PageController extends Controller
{
    /** Hommage aux proches disparus. */
    public function penseePour(CurrentEvent $courant): View
    {
        $decedents = $this->blocs('hommage', 'defunt', ['nom', 'photo', 'dates', 'message'])
            ->map(fn ($bloc) => (object) [
                'name'    => $bloc->nom,
                'photo'   => $bloc->photo,
                'dates'   => $bloc->dates,
                'message' => $bloc->message,
            ]);

        return view('pensee-pour', [
            'decedents'    => $decedents,
            'titre'        => $courant->get()?->reglage('hommage', 'titre', 'Une pensée pour…'),
            'introduction' => $courant->get()?->reglage('hommage', 'texte_intro'),
        ]);
    }

    /** Frise de la rencontre du couple. */
    public function histoire(CurrentEvent $courant): View
    {
        return view('pages.histoire', [
            'etapes'       => $this->blocs('histoire', 'etape_histoire', ['titre', 'date', 'texte', 'image']),
            'introduction' => $courant->get()?->reglage('site', 'message_accueil'),
        ]);
    }

    /** Carte du repas, regroupée par moment. */
    public function menu(CurrentEvent $courant): View
    {
        $event  = $courant->get();
        $ordre  = ['cocktail', 'entree', 'plat', 'fromage', 'dessert', 'boisson'];
        $labels = collect(config('solen_schema.blocs.plat.champs'))
            ->firstWhere('cle', 'categorie')['options'] ?? [];

        $plats = $this->blocs('menu', 'plat', ['categorie', 'nom', 'description', 'allergenes']);

        $limite = $event?->reglage('menu', 'date_limite');

        return view('pages.menu', [
            'sections'         => $plats
                ->groupBy('categorie')
                ->sortBy(fn ($_, $cle) => array_search($cle, $ordre, true) ?: 99),
            'categories'       => $labels,
            'introduction'     => $event?->reglage('menu', 'texte_intro'),
            'collecteAllergies' => (bool) $event?->reglage('menu', 'collecte_allergies', true),
            'dateLimite'       => $limite ? Carbon::parse($limite) : null,
            'contact'          => $event?->reglage('pratique', 'contact_email'),
        ]);
    }

    /**
     * Aplatit les blocs d'un type en objets simples pour la vue.
     *
     * @param  list<string>  $champs
     * @return Collection<int, object>
     */
    private function blocs(string $page, string $type, array $champs): Collection
    {
        return ContentBlock::pourPage($page)
            ->deType($type)
            ->get()
            ->map(fn (ContentBlock $bloc) => (object) collect($champs)
                ->mapWithKeys(fn (string $cle) => [$cle => $bloc->valeur($cle)])
                ->all())
            ->values();
    }
}
