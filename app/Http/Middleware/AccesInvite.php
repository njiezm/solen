<?php

namespace App\Http\Middleware;

use App\Models\Event;
use App\Solen\CurrentEvent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Qui peut voir le site des invités.
 *
 * - Un mariage en brouillon n'est visible que de ses organisateurs : la
 *   promesse était affichée dans l'espace, rien ne l'appliquait.
 * - Si les mariés ont choisi un code d'accès, il est demandé une fois,
 *   puis retenu pour la session.
 *
 * Les organisateurs connectés passent toujours : ils doivent pouvoir
 * relire leur site avant de le publier.
 */
class AccesInvite
{
    public function handle(Request $request, Closure $next): Response
    {
        $event = app(CurrentEvent::class)->get();
        $user  = $request->user();

        if ($user && ($user->estSuperAdmin() || $user->peutGerer($event))) {
            return $next($request);
        }

        if ($event->statut === Event::STATUT_BROUILLON) {
            return response()->view('acces.bientot', [], 200)->header('X-Robots-Tag', 'noindex');
        }

        $code = trim((string) $event->reglage('site', 'mot_de_passe'));

        if ($code !== '' && ! $request->session()->get("acces.{$event->id}") && ! $request->routeIs('acces.verifier')) {
            if ($request->isMethod('GET')) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return response()->view('acces.code', [], 200)->header('X-Robots-Tag', 'noindex');
        }

        return $next($request);
    }
}
