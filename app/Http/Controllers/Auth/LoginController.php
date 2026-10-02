<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Connexion des organisateurs.
 *
 * Volontairement écrit à la main plutôt qu'installé via Breeze : les
 * échafaudages de Laravel arrivent avec des vues Tailwind, incompatibles
 * avec le choix de rester sur Bootstrap.
 */
class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    /** La porte de la console, distincte de celle des mariés. */
    public function showAdmin(): View
    {
        return view('auth.login-admin');
    }

    /**
     * Connexion à la console : seuls les comptes de l'équipe Solen passent.
     * Un compte de mariés est refusé ici, avec le même message qu'un mauvais
     * mot de passe, pour ne pas révéler qu'il existe.
     */
    public function loginAdmin(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [], ['email' => 'adresse e-mail', 'password' => 'mot de passe']);

        $this->verifierCadence($request);

        $ok = Auth::validate($donnees)
            && \App\Models\User::where('email', $donnees['email'])->first()?->estSuperAdmin();

        if (! $ok) {
            RateLimiter::hit($this->cleCadence($request));

            throw ValidationException::withMessages(['email' => 'Ces identifiants ne donnent pas accès à la console.']);
        }

        Auth::attempt($donnees, $request->boolean('remember'));
        RateLimiter::clear($this->cleCadence($request));
        $request->session()->regenerate();
        $request->user()->forceFill(['derniere_connexion_at' => now()])->save();

        return redirect()->intended(route('console.index'));
    }

    public function login(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [], [
            'email'    => 'adresse e-mail',
            'password' => 'mot de passe',
        ]);

        $this->verifierCadence($request);

        if (! Auth::attempt($donnees, $request->boolean('remember'))) {
            RateLimiter::hit($this->cleCadence($request));

            throw ValidationException::withMessages([
                'email' => 'Ces identifiants ne correspondent à aucun compte.',
            ]);
        }

        RateLimiter::clear($this->cleCadence($request));
        $request->session()->regenerate();

        $request->user()->forceFill(['derniere_connexion_at' => now()])->save();

        return redirect()->intended($request->user()->accueilApresConnexion());
    }

    public function logout(Request $request): RedirectResponse
    {
        $etaitAdmin = (bool) $request->user()?->estSuperAdmin();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($etaitAdmin ? 'admin.connexion' : 'auth.login')->with('status', 'Vous êtes déconnecté.');
    }

    /** Cinq tentatives par minute et par couple e-mail / adresse IP. */
    private function verifierCadence(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->cleCadence($request), 5)) {
            return;
        }

        $secondes = RateLimiter::availableIn($this->cleCadence($request));

        throw ValidationException::withMessages([
            'email' => "Trop de tentatives. Réessayez dans {$secondes} secondes.",
        ]);
    }

    private function cleCadence(Request $request): string
    {
        return 'login:' . mb_strtolower((string) $request->input('email')) . '|' . $request->ip();
    }
}
