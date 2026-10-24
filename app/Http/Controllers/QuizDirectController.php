<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Participant;
use App\Solen\CurrentEvent;
use App\Solen\Jeux;
use App\Solen\QuizDirect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Le quiz en direct : les téléphones des invités d'un côté, le pupitre de
 * l'animateur de l'autre (lien secret, sans compte).
 */
class QuizDirectController extends Controller
{
    public function __construct(
        private readonly QuizDirect $quiz,
        private readonly Jeux $jeux,
        private readonly CurrentEvent $courant,
    ) {
    }

    private function event(): Event
    {
        return $this->courant->get();
    }

    // --- Invités ----------------------------------------------------------------

    /** Ce que le téléphone affiche maintenant. Interrogé toutes les 1,5 s. */
    public function etat(): JsonResponse
    {
        abort_unless($this->quiz->actif($this->event()), 404);

        return response()->json($this->quiz->pourInvite($this->event(), $this->jeux->joueurConnu()['id'] ?? null));
    }

    public function joueur(Request $request): JsonResponse
    {
        abort_unless($this->quiz->actif($this->event()), 404);

        $donnees = $request->validate([
            'prenom' => ['required', 'string', 'max:100'],
            'nom'    => ['required', 'string', 'max:100'],
        ], ['prenom.required' => 'Indiquez votre prénom.', 'nom.required' => 'Indiquez votre nom (ou son initiale).']);

        $joueur = $this->jeux->joueur($donnees['prenom'], $donnees['nom']);

        return response()->json(['prenom' => $joueur->prenom]);
    }

    public function repondre(Request $request): JsonResponse
    {
        $event = $this->event();
        abort_unless($this->quiz->actif($event), 404);

        $donnees = $request->validate(['index' => ['required', 'integer', 'min:0'], 'choix' => ['required', 'integer', 'min:0', 'max:2']]);
        $id = $this->jeux->joueurConnu()['id'] ?? null;
        $joueur = $id ? Participant::find($id) : null;

        if (! $joueur) {
            return response()->json(['erreur' => 'Indiquez d’abord votre prénom.'], 422);
        }

        $reponse = $this->quiz->repondre($event, $joueur, (int) $donnees['index'], (int) $donnees['choix']);

        return $reponse
            ? response()->json(['ok' => true, 'choix' => $reponse->choix])
            : response()->json(['erreur' => 'Cette question est fermée.'], 409);
    }

    // --- Animateur --------------------------------------------------------------

    private function verifier(string $jeton): Event
    {
        $event = $this->event();
        abort_unless($this->quiz->jetonValide($event, $jeton), 404);

        return $event;
    }

    public function animateur(string $jeton): View
    {
        $event = $this->verifier($jeton);

        return view('jeux.quiz-animateur', [
            'event' => $event,
            'jeton' => $jeton,
            'quiz'  => $this->jeux->quizPret($event)->values(),
        ]);
    }

    /** L'écran projeté : il suit le pupitre, sans aucun bouton. */
    public function ecran(string $jeton): View
    {
        $event = $this->verifier($jeton);

        return view('jeux.quiz-ecran', [
            'event' => $event,
            'jeton' => $jeton,
            'quiz'  => $this->jeux->quizPret($event)->values(),
        ]);
    }

    public function animateurEtat(string $jeton): JsonResponse
    {
        $event = $this->verifier($jeton);
        $etat = $this->quiz->etat($event);

        return response()->json($etat + [
            'stats'      => $this->quiz->statistiques($etat['index']),
            'joueurs'    => \App\Models\QuizReponse::distinct()->count('participant_id'),
            // Le classement, dès qu'une réponse est révélée : l'écran affiche
            // les meneurs en direct, puis le podium.
            'classement' => in_array($etat['etat'], ['reponse', 'fin'], true)
                ? \App\Models\ScoreJeu::where('type_jeu', 'quiz')->with('participant')->orderByDesc('points')->orderBy('updated_at')->limit(10)->get()
                    ->map(fn ($s) => ['nom' => trim($s->participant?->prenom . ' ' . mb_substr((string) $s->participant?->nom, 0, 1) . '.'), 'bonnes' => $s->detail['bonnes'] ?? null])
                : [],
        ]);
    }

    public function commande(Request $request, string $jeton): JsonResponse
    {
        $event = $this->verifier($jeton);
        $donnees = $request->validate([
            'action' => ['required', 'in:lancer,reveler,terminer,reinitialiser'],
            'index'  => ['nullable', 'integer', 'min:0'],
        ]);

        match ($donnees['action']) {
            'lancer'        => $this->quiz->lancer($event, (int) ($donnees['index'] ?? 0)),
            'reveler'       => $this->quiz->reveler($event),
            'terminer'      => $this->quiz->terminer($event),
            'reinitialiser' => $this->quiz->reinitialiser($event),
        };

        return $this->animateurEtat($jeton);
    }
}
