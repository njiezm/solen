<?php

namespace App\Http\Middleware;

use App\Models\Event;
use App\Solen\CurrentEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Détermine à quel mariage appartient la requête.
 *
 * Adressage par chemin : /mariage/{slug}/…
 *
 * Choisi contre les sous-domaines parce qu'il ne demande ni DNS joker, ni
 * certificat SSL par client, ni vhost à créer à chaque vente — et parce que
 * le développement local se comporte exactement comme la production.
 *
 * Un domaine personnalisé reste possible en option payante : il est résolu
 * en second et redirige vers le chemin correspondant.
 */
class ResolveEvent
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('event');

        $event = $slug
            ? Event::where('slug', $slug)->first()
            : Event::where('domaine', $request->getHost())->first();

        if (! $event) {
            abort(404, "Ce mariage n'existe pas ou n'est plus accessible.");
        }

        if ($event->statut === Event::STATUT_ARCHIVE && ! $request->user()) {
            abort(410, 'Ce mariage a été archivé.');
        }

        app(CurrentEvent::class)->set($event, true);

        // Le slug a servi à router : on le retire pour qu'il ne soit pas
        // injecté en premier argument de chaque méthode de contrôleur.
        $request->route()?->forgetParameter('event');

        // …mais il reste fourni automatiquement à route(), pour que les vues
        // continuent d'écrire route('galerie.index') sans se soucier du slug.
        URL::defaults(['event' => $event->slug]);

        view()->share('event', $event);

        return $next($request);
    }
}
