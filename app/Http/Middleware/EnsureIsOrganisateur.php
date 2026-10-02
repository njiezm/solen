<?php

namespace App\Http\Middleware;

use App\Solen\CurrentEvent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve l'accès aux mariés et à leurs collaborateurs (témoin, organisateur).
 * L'équipe Solen passe partout.
 */
class EnsureIsOrganisateur
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('auth.login'));
        }

        if ($user->estSuperAdmin()) {
            return $next($request);
        }

        $event = app(CurrentEvent::class)->get();

        if (! $event || ! $user->peutGerer($event)) {
            abort(403, "Vous n'avez pas accès à l'espace de ce mariage.");
        }

        return $next($request);
    }
}
