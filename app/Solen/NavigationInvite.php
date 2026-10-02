<?php

namespace App\Solen;

use App\Models\Event;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Le menu du site invité, déduit des modules actifs et des moments déclarés.
 *
 * Centralisé ici parce que trois endroits en ont besoin — les tuiles de
 * l'accueil, la barre de navigation basse, l'en-tête — et qu'ils doivent
 * dire exactement la même chose.
 */
class NavigationInvite
{
    /** @var array<int, Collection<string, array>> */
    private array $cache = [];

    /**
     * Toutes les entrées, ordonnées.
     *
     * @return Collection<string, array{route: string, params: mixed, nom: string, icone: string, desc: string, ordre: int}>
     */
    public function entrees(Event $event): Collection
    {
        return $this->cache[$event->id] ??= $this->construire($event);
    }

    /**
     * Les entrées les plus utiles le jour J, pour la barre basse : on ne
     * peut pas en afficher plus de cinq sans que ça devienne illisible au
     * pouce. « Accueil » occupe toujours la première place.
     *
     * @return Collection<int, array>
     */
    public function raccourcis(Event $event, int $max = 5): Collection
    {
        $priorite = ['livret', 'photobooth', 'mur', 'livredor', 'pratique', 'cagnotte'];

        $entrees = $this->entrees($event);

        $choisies = collect($priorite)
            ->map(fn (string $cle) => $entrees->get($cle))
            ->filter()
            ->values();

        // S'il reste de la place, on complète par les moments de la journée.
        if ($choisies->count() < $max - 1) {
            $choisies = $choisies->concat(
                $entrees->filter(fn ($_, $cle) => str_starts_with($cle, 'moment.'))->values()
            );
        }

        return $choisies->take($max - 1);
    }

    /**
     * Les entrées rangées par groupe, pour le panneau de navigation.
     *
     * @return Collection<string, array{nom: string, entrees: Collection}>
     */
    public function groupes(Event $event): Collection
    {
        $entrees = $this->entrees($event);

        return collect(config('solen_schema.navigation_groupes'))
            ->map(fn (string $nom, string $cle) => [
                'nom'     => $nom,
                'entrees' => $entrees->where('groupe', $cle)->values(),
            ])
            ->filter(fn (array $groupe) => $groupe['entrees']->isNotEmpty());
    }

    /** @return Collection<string, array> */
    private function construire(Event $event): Collection
    {
        $entrees = collect(config('solen_schema.navigation'))
            ->filter(fn ($nav, $cle) => $event->aModule($cle) && Route::has($nav['route']))
            // Le livret n'apparaît qu'une fois son PDF déposé.
            ->reject(fn ($nav, $cle) => $cle === 'livret' && ! $event->reglage('livret', 'pdf'))
            ->map(fn ($nav) => $nav + ['params' => []]);

        // Un moment de la journée par page : mairie, cérémonie, brunch…
        foreach ($event->parts()->actives()->get() as $partie) {
            $entrees->put("moment.{$partie->cle}", [
                'groupe' => 'jour',
                'route'  => 'partie',
                'params' => $partie->cle,
                'nom'    => $partie->nom,
                'icone'  => $partie->icone ?: 'fa-calendar-day',
                'desc'   => $partie->lieu_nom
                    ?: ($partie->debut_at ? $event->enHeureLocale($partie->debut_at)->format('H\hi') : 'À venir'),
                'ordre'  => 20 + $partie->ordre,
            ]);
        }

        // Les jeux ouverts : cochés, inclus dans la formule, et prêts.
        $jeux = app(Jeux::class)->ouverts($event);

        foreach ($jeux as $cle => $jeu) {
            $entrees->put("jeu.{$cle}", $jeu + ['params' => [], 'groupe' => 'jouer', 'ordre' => 100]);
        }

        if ($jeux->isNotEmpty() && $event->reglage('jeux', 'classement', true)) {
            $entrees->put('jeu.classement', [
                'route' => 'jeux.classement', 'params' => [], 'groupe' => 'jouer', 'ordre' => 110,
                'nom' => 'Classement', 'icone' => 'fa-trophy', 'desc' => 'Qui mène la partie ?',
            ]);
        }

        return $entrees->sortBy('ordre');
    }
}
