<?php

namespace App\Solen;

use App\Models\Event;
use App\Models\Participant;
use App\Models\QuizReponse;
use Illuminate\Support\Str;

/**
 * Le quiz en direct, piloté par l'animateur pendant la soirée.
 *
 * L'animateur lance chaque question depuis un lien secret ; les téléphones
 * des invités l'affichent d'eux-mêmes (ils interrogent l'état toutes les
 * secondes et demie) et y répondent tant qu'elle est ouverte. Puis
 * l'animateur révèle la réponse, passe à la suivante, et termine sur le
 * classement.
 *
 * L'état tient en quelques clés dans les réglages du module jeux : pas de
 * file, pas de connexion permanente, rien qui casse sur un réseau moyen.
 *
 *   attente  → rien n'est lancé
 *   question → la question n° index est ouverte aux réponses
 *   reponse  → la bonne réponse de la question n° index est révélée
 *   fin      → le quiz est terminé, place au classement
 */
class QuizDirect
{
    public function __construct(private readonly Jeux $jeux)
    {
    }

    public function actif(Event $event): bool
    {
        return (bool) $event->reglage('jeux', 'quiz_direct', false);
    }

    /** Le lien secret de l'animateur, créé au besoin. */
    public function lienAnimateur(Event $event): string
    {
        $jeton = $event->reglage('jeux', 'quiz_jeton');

        if (! $jeton) {
            $jeton = Str::random(32);
            $event->ecrireReglages('jeux', ['quiz_jeton' => $jeton]);
        }

        return route('quiz.animateur', ['event' => $event->slug, 'jeton' => $jeton]);
    }

    public function jetonValide(Event $event, string $jeton): bool
    {
        $attendu = (string) $event->reglage('jeux', 'quiz_jeton');

        return $attendu !== '' && hash_equals($attendu, $jeton);
    }

    /** @return array{etat: string, index: int, depuis: ?string} */
    public function etat(Event $event): array
    {
        $etat = (array) $event->reglage('jeux', 'quiz_live', []);

        return [
            'etat'   => $etat['etat'] ?? 'attente',
            'index'  => (int) ($etat['index'] ?? 0),
            'depuis' => $etat['depuis'] ?? null,
        ];
    }

    private function poser(Event $event, string $etat, int $index): void
    {
        $event->ecrireReglages('jeux', ['quiz_live' => ['etat' => $etat, 'index' => $index, 'depuis' => now()->toIso8601String()]]);
    }

    // --- Commandes de l'animateur ---------------------------------------------

    public function lancer(Event $event, int $index): void
    {
        $nombre = $this->jeux->quizPret($event)->count();
        $index = max(0, min($index, $nombre - 1));
        $etat = $this->etat($event);

        // La même question déjà ouverte : rien à faire (double-tap).
        if (! ($etat['etat'] === 'question' && $etat['index'] === $index)) {
            $this->poser($event, 'question', $index);
        }
    }

    /*
     * Chaque commande n'agit que dans l'état qui lui correspond : un
     * double-tap, ou un ordre arrivé en retard sur un réseau lent, ne fait
     * jamais revenir le quiz en arrière.
     */
    public function reveler(Event $event): void
    {
        $etat = $this->etat($event);
        if ($etat['etat'] === 'question') {
            $this->poser($event, 'reponse', $etat['index']);
        }
    }

    public function terminer(Event $event): void
    {
        $etat = $this->etat($event);
        if ($etat['etat'] !== 'fin') {
            $this->poser($event, 'fin', $etat['index']);
        }
    }

    /** Tout recommencer : réponses effacées, scores du quiz retirés. */
    public function reinitialiser(Event $event): void
    {
        QuizReponse::query()->delete();
        \App\Models\ScoreJeu::where('type_jeu', 'quiz')->delete();
        $this->poser($event, 'attente', 0);
    }

    // --- Réponses des invités ---------------------------------------------------

    /**
     * Enregistre la réponse si la question est bien celle ouverte. Une seule
     * réponse par question : la première compte.
     */
    public function repondre(Event $event, Participant $joueur, int $index, int $choix): ?QuizReponse
    {
        $etat = $this->etat($event);
        $question = $this->jeux->quizPret($event)->get($index);

        if ($etat['etat'] !== 'question' || $etat['index'] !== $index || ! $question || ! isset($question['choix'][$choix])) {
            return null;
        }

        $reponse = QuizReponse::firstOrCreate(
            ['participant_id' => $joueur->id, 'question' => $index],
            ['choix' => $choix, 'juste' => $choix === $question['bonne']]
        );

        $this->recompter($event, $joueur);

        return $reponse;
    }

    /** Le score du quiz, sur 100 : la part de bonnes réponses sur tout le quiz. */
    private function recompter(Event $event, Participant $joueur): void
    {
        $total = max(1, $this->jeux->quizPret($event)->count());
        $bonnes = QuizReponse::where('participant_id', $joueur->id)->where('juste', true)->count();

        \App\Models\ScoreJeu::updateOrCreate(
            ['participant_id' => $joueur->id, 'type_jeu' => 'quiz'],
            ['points' => (int) round($bonnes / $total * 100), 'detail' => ['bonnes' => $bonnes, 'total' => $total, 'direct' => true]]
        );
    }

    /** @return array{reponses: int, parChoix: array<int, int>} */
    public function statistiques(int $index): array
    {
        $parChoix = QuizReponse::where('question', $index)->selectRaw('choix, count(*) as n')->groupBy('choix')->pluck('n', 'choix')->all();

        return ['reponses' => array_sum($parChoix), 'parChoix' => $parChoix];
    }

    /**
     * Ce qu'un téléphone d'invité doit afficher maintenant.
     *
     * @return array<string, mixed>
     */
    public function pourInvite(Event $event, ?int $joueurId): array
    {
        $etat = $this->etat($event);
        $quiz = $this->jeux->quizPret($event);
        $q = $quiz->get($etat['index']);

        $vue = ['etat' => $etat['etat'], 'index' => $etat['index'], 'total' => $quiz->count()];

        if ($q && in_array($etat['etat'], ['question', 'reponse'], true)) {
            $mienne = $joueurId ? QuizReponse::where('participant_id', $joueurId)->where('question', $etat['index'])->first() : null;

            $vue += [
                'question' => $q['question'],
                'sujet'    => $q['sujet'],
                'choix'    => $q['choix'],
                'mienne'   => $mienne?->choix,
            ];

            // La bonne réponse ne part vers les téléphones qu'une fois révélée.
            if ($etat['etat'] === 'reponse') {
                $vue['bonne'] = $q['bonne'];
            }
        }

        if ($joueurId) {
            $vue['score'] = QuizReponse::where('participant_id', $joueurId)->where('juste', true)->count();
        }

        return $vue;
    }
}
