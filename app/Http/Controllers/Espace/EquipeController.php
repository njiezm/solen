<?php

namespace App\Http\Controllers\Espace;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Solen\CurrentEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * L'équipe d'un mariage : les mariés n'administrent pas tout seuls.
 *
 * Un témoin, une wedding planner ou un parent peut être invité à aider. Il
 * reçoit un lien pour choisir son mot de passe, et accède au même espace.
 * Seuls les propriétaires (les mariés) et l'équipe Solen peuvent inviter
 * ou retirer quelqu'un.
 */
class EquipeController extends Controller
{
    public const ROLES = [
        'proprietaire'  => 'Marié·e',
        'collaborateur' => 'Aide à l’organisation',
    ];

    public function __construct(private readonly CurrentEvent $courant)
    {
    }

    public function index(Request $request): View
    {
        $event = $this->courant->get();

        return view('espace.equipe', [
            'membres'  => $event->users()->orderByPivot('role', 'desc')->orderBy('name')->get(),
            'roles'    => self::ROLES,
            'peutGerer' => $this->peutInviter($request->user()),
        ]);
    }

    public function inviter(Request $request): RedirectResponse
    {
        abort_unless($this->peutInviter($request->user()), 403);

        $donnees = $request->validate([
            'name'  => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'role'  => ['required', Rule::in(array_keys(self::ROLES))],
        ], [], ['name' => 'nom', 'email' => 'adresse e-mail']);

        $event = $this->courant->get();
        $user  = User::where('email', $donnees['email'])->first();

        if ($user?->estSuperAdmin()) {
            return back()->with('erreur', 'Ce compte appartient à l’équipe Solen, il a déjà accès.');
        }

        if (! $user) {
            // Mot de passe aléatoire jamais communiqué : l'invité choisit le sien via le lien.
            $user = User::create([
                'name'     => $donnees['name'],
                'email'    => $donnees['email'],
                'password' => Hash::make(Str::random(40)),
                'role'     => User::ROLE_ORGANISATEUR,
            ]);
        }

        $event->users()->syncWithoutDetaching([$user->id => ['role' => $donnees['role']]]);

        $lien = \App\Solen\Invitations::lien($user, $event);

        try {
            app(\App\Solen\Documents::class)->envoyer($user->email, "Vous êtes invité·e à préparer le mariage {$event->nom}",
                "Bonjour {$donnees['name']},\n\n" . ($request->user()->name ?? 'Les mariés') . " vous invite à aider à préparer le mariage {$event->nom} sur Solen.\n\n"
                . 'Choisissez votre mot de passe ici (lien valable ' . \App\Solen\Invitations::VALIDITE_JOURS . " jours) :\n{$lien}", [], $event);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('erreur', 'L’accès est créé, mais l’e-mail n’est pas parti. Lien à transmettre : ' . $lien);
        }

        return back()->with('ok', "{$donnees['name']} a été invité·e : un e-mail lui permet de choisir son mot de passe.");
    }

    public function retirer(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->peutInviter($request->user()), 403);

        $event  = $this->courant->get();
        $membre = $event->users()->whereKey($id)->firstOrFail();

        $proprietaires = $event->users()->wherePivot('role', 'proprietaire')->count();

        if ($membre->pivot->role === 'proprietaire' && $proprietaires <= 1) {
            return back()->with('erreur', 'Le mariage doit garder au moins un·e marié·e propriétaire.');
        }

        if ($membre->is($request->user())) {
            return back()->with('erreur', 'Vous ne pouvez pas vous retirer vous-même.');
        }

        $event->users()->detach($membre->id);

        return back()->with('ok', "{$membre->name} n’a plus accès à l’espace.");
    }

    private function peutInviter(?User $user): bool
    {
        return $user && ($user->estSuperAdmin() || $user->estProprietaire($this->courant->get()));
    }
}
