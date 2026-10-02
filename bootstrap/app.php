<?php

use App\Http\Middleware\EnsureIsOrganisateur;
use App\Http\Middleware\EnsureIsSuperAdmin;
use App\Http\Middleware\ResolveEvent;
use App\Solen\AlerteErreur;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Il n'existe plus de page d'accueil neutre : chaque écran de
        // l'espace est rattaché à un mariage, donc à un slug.
        $middleware->redirectUsersTo(
            fn (Request $request) => $request->user()->accueilApresConnexion()
        );

        // La console renvoie vers sa propre page de connexion.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('console', 'console/*')
            ? route('admin.connexion')
            : route('auth.login'));

        $middleware->alias([
            // Résout le mariage courant et cloisonne les requêtes.
            'event'         => ResolveEvent::class,
            // Réserve un écran aux mariés et à leurs collaborateurs.
            'organisateur'  => EnsureIsOrganisateur::class,
            // Brouillon et code d'accès du site invité.
            'acces'         => \App\Http\Middleware\AccesInvite::class,
            // Réserve un écran à l'équipe Solen.
            'super-admin'   => EnsureIsSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Être prévenu qu'une page plante avant que le client n'appelle.
        $exceptions->report(function (Throwable $e) {
            app(AlerteErreur::class)->signaler($e);
        });
    })->create();
