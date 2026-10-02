<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use App\Solen\Invitations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** L'invité choisit son mot de passe, puis entre directement dans l'espace. */
class InvitationController extends Controller
{
    private function verifier(int $user, string $slug, string $cle): array
    {
        $compte = User::findOrFail($user);
        $event  = Event::where('slug', $slug)->firstOrFail();

        abort_unless(Invitations::valide($compte, $cle) && $compte->peutGerer($event), 403,
            'Ce lien a déjà servi ou n’est plus valable. Demandez-en un nouveau, ou utilisez « Mot de passe oublié ».');

        return [$compte, $event];
    }

    public function formulaire(int $user, string $slug, string $cle): View
    {
        [$compte, $event] = $this->verifier($user, $slug, $cle);

        return view('auth.invitation', ['compte' => $compte, 'event' => $event]);
    }

    public function accepter(Request $request, int $user, string $slug, string $cle): RedirectResponse
    {
        [$compte, $event] = $this->verifier($user, $slug, $cle);

        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], ['password.confirmed' => 'Les deux mots de passe ne correspondent pas.'], ['password' => 'mot de passe']);

        $compte->update(['password' => Hash::make($request->password)]);

        Auth::login($compte);
        $request->session()->regenerate();

        return redirect()->route('espace.index', $event->slug)->with('ok', 'Bienvenue ! Votre espace est prêt.');
    }
}
