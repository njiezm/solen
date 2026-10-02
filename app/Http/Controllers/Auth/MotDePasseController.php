<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as ReglePassword;
use Illuminate\View\View;

/**
 * Mot de passe oublié.
 *
 * Sans ce parcours, un couple qui perd le mot de passe reçu à l'achat est
 * bloqué et n'a d'autre recours que d'écrire à Solen.
 */
class MotDePasseController extends Controller
{
    public function demande(): View
    {
        return view('auth.mot-de-passe-demande');
    }

    public function envoyer(Request $request): RedirectResponse
    {
        $request->validate(
            ['email' => ['required', 'email']],
            [],
            ['email' => 'adresse e-mail']
        );

        // sendResetLink ne révèle pas si l'adresse existe : c'est voulu,
        // on ne renseigne pas un attaquant sur nos clients.
        Password::sendResetLink($request->only('email'));

        return back()->with('statut',
            'Si un compte existe pour cette adresse, un lien vient d’y être envoyé. '
            . 'Pensez à regarder vos indésirables.');
    }

    public function formulaire(Request $request, string $token): View
    {
        return view('auth.mot-de-passe-reinitialiser', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reinitialiser(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', ReglePassword::min(8)],
        ], [
            'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
        ], [
            'email'    => 'adresse e-mail',
            'password' => 'mot de passe',
        ]);

        $statut = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($statut !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Ce lien n’est plus valide. Demandez-en un nouveau.',
            ]);
        }

        return redirect()
            ->route('auth.login')
            ->with('status', 'Votre mot de passe a été changé. Vous pouvez vous connecter.');
    }
}
