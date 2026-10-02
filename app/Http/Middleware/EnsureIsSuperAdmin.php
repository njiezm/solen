<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve l'accès à l'équipe Solen : administration transverse,
 * gestion des mariages, des formules et des thèmes.
 */
class EnsureIsSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('auth.login'));
        }

        if (! $user->estSuperAdmin()) {
            abort(403, 'Espace réservé à l’équipe Solen.');
        }

        return $next($request);
    }
}
