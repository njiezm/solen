<?php

namespace App\Http\Controllers;

use App\Models\EventPart;
use App\Models\Invite;
use App\Solen\CurrentEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Le RSVP, côté invités.
 *
 * Chaque foyer a un lien personnel (/rsvp/CODE) : pas de compte, pas de
 * mot de passe, ses informations déjà pré-remplies. Sans lien, on peut
 * saisir son code ; et si les mariés l'acceptent, répondre librement.
 */
class RsvpController extends Controller
{
    public function __construct(private readonly CurrentEvent $courant)
    {
    }

    private function verifierModule(): void
    {
        abort_unless($this->courant->get()->aModule('rsvp'), 404);
    }

    public function index(Request $request): View|RedirectResponse
    {
        $this->verifierModule();

        if ($code = $request->session()->get('rsvp.' . $this->courant->id())) {
            return redirect()->route('rsvp.personnel', $code);
        }

        return view('pages.rsvp', $this->contexte() + ['invite' => null]);
    }

    /** Saisie du code reçu sur l'invitation. */
    public function code(Request $request): RedirectResponse
    {
        $this->verifierModule();

        $code = strtoupper(preg_replace('/\s+/', '', (string) $request->input('code')));
        $invite = Invite::where('code', $code)->first();

        return $invite
            ? redirect()->route('rsvp.personnel', $invite->code)
            : back()->withErrors(['code' => 'Ce code ne correspond à aucune invitation. Il figure sur votre faire-part ou dans le message reçu.'])->withInput();
    }

    public function personnel(Request $request, string $code): View
    {
        $this->verifierModule();

        $invite = Invite::where('code', strtoupper($code))->firstOrFail();
        $request->session()->put('rsvp.' . $this->courant->id(), $invite->code);

        return view('pages.rsvp', $this->contexte() + ['invite' => $invite]);
    }

    public function repondre(Request $request, string $code): RedirectResponse
    {
        $this->verifierModule();

        $invite = Invite::where('code', strtoupper($code))->firstOrFail();
        $invite->update($this->valider($request, $invite->places) + ['repondu_le' => now()]);

        return redirect()->route('rsvp.personnel', $invite->code)->with('merci', true);
    }

    /** Réponse spontanée d'une personne absente de la liste, si les mariés l'acceptent. */
    public function repondreLibre(Request $request): RedirectResponse
    {
        $this->verifierModule();
        abort_unless($this->courant->get()->reglage('rsvp', 'ouvert_a_tous', false), 403);

        $identite = $request->validate([
            'nom'       => ['required', 'string', 'max:120'],
            'email'     => ['nullable', 'email', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:40'],
        ], [], ['nom' => 'nom']);

        $invite = Invite::create($identite + ['places' => 10, 'source' => 'libre'] + $this->valider($request, 10) + ['repondu_le' => now()]);
        $request->session()->put('rsvp.' . $this->courant->id(), $invite->code);

        return redirect()->route('rsvp.personnel', $invite->code)->with('merci', true);
    }

    // --- Outils -------------------------------------------------------------

    private function contexte(): array
    {
        $event = $this->courant->get();

        return [
            'moments'      => EventPart::actives()->orderBy('ordre')->get(),
            'dateLimite'   => $event->reglage('rsvp', 'date_limite') ? \Illuminate\Support\Carbon::parse($event->reglage('rsvp', 'date_limite')) : null,
            'avecMoments'  => (bool) $event->reglage('rsvp', 'demander_moments', true),
            'avecRegimes'  => (bool) $event->reglage('rsvp', 'demander_regimes', true),
            'ouvert'       => (bool) $event->reglage('rsvp', 'ouvert_a_tous', false),
            'merciTexte'   => $event->reglage('rsvp', 'message_merci', 'Merci ! Votre réponse est bien enregistrée.'),
        ];
    }

    private function valider(Request $request, int $places): array
    {
        $cles = EventPart::actives()->pluck('cle')->all();

        $donnees = $request->validate([
            'reponse'          => ['required', Rule::in(['oui', 'non'])],
            'presents'         => ['required_if:reponse,oui', 'nullable', 'integer', 'min:1', "max:{$places}"],
            'noms_presents'    => ['nullable', 'array', "max:{$places}"],
            'noms_presents.*'  => ['nullable', 'string', 'max:80'],
            'moments'          => ['nullable', 'array'],
            'moments.*'        => [Rule::in($cles)],
            'regimes'          => ['nullable', 'string', 'max:1000'],
            'message'          => ['nullable', 'string', 'max:1000'],
        ], [
            'presents.max'         => "Votre invitation compte {$places} place" . ($places > 1 ? 's' : '') . '.',
            'presents.required_if' => 'Indiquez combien vous serez.',
        ], ['presents' => 'nombre de personnes']);

        if ($donnees['reponse'] === 'non') {
            $donnees['presents'] = 0;
            $donnees['moments'] = [];
            $donnees['noms_presents'] = [];
        }

        $donnees['noms_presents'] = array_values(array_filter($donnees['noms_presents'] ?? [], 'filled'));

        return $donnees;
    }
}
