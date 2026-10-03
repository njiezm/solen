<?php

namespace App\Http\Controllers;

use App\Models\ChassePhoto;
use App\Models\Event;
use App\Models\QuestionQuiDeux;
use App\Models\ReponseQuiDeux;
use App\Models\ScoreJeu;
use App\Solen\CurrentEvent;
use App\Solen\GrilleMotsCroises;
use App\Solen\Jeux;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Les jeux, côté invités.
 *
 * Aucun jeu ne dépend plus d'une « session » lancée à la main : il est
 * ouvert dès que les mariés l'ont coché et que son contenu est prêt (voir
 * App\Solen\Jeux). Chaque partie enregistre un score, qui alimente le
 * classement général.
 *
 * Barème, sur 100 points par jeu :
 *   - Qui de nous 2 : la part de bonnes réponses ;
 *   - Mots croisés  : la part de mots trouvés ;
 *   - Memory        : 100, moins 4 points par coup au-delà du parfait ;
 *   - Puzzle        : 100 en moins d'une minute, puis un point de moins
 *                     toutes les trois secondes, 20 au minimum ;
 *   - Chasse photo  : 20 points par photo validée par les mariés, 100 max.
 */
class JeuxController extends Controller
{
    public function __construct(
        private readonly Jeux $jeux,
        private readonly CurrentEvent $courant,
    ) {
    }

    private function event(): Event
    {
        return $this->courant->get();
    }

    /** Refuse un jeu fermé avec une page d'attente plutôt qu'une erreur. */
    private function attente(string $cle): ?View
    {
        $event = $this->event();

        if (! $this->jeux->active($event, $cle)) {
            abort(404);
        }

        if (! $this->jeux->pret($event, $cle)) {
            return view('jeux.en-attente', [
                'message' => 'Les mariés préparent encore ce jeu. Revenez un peu plus tard !',
            ]);
        }

        return null;
    }

    private function regleJoueur(): array
    {
        return [
            'prenom' => ['required', 'string', 'max:100'],
            'nom'    => ['required', 'string', 'max:100'],
        ];
    }

    // --- Quiz des mariés ---------------------------------------------------

    public function quiz(): View
    {
        if ($attente = $this->attente('quiz')) {
            return $attente;
        }

        // Les propositions sont mélangées à l'affichage : la bonne réponse
        // n'est pas toujours la première saisie par les mariés.
        $questions = $this->jeux->quizPret($this->event())->map(fn ($q) => $q + [
            'ordre' => collect(array_keys($q['choix']))->shuffle()->all(),
        ]);

        return view('jeux.quiz', [
            'questions' => $questions,
            'prenoms'   => $this->jeux->prenoms($this->event()),
            'joueur'    => $this->jeux->joueurConnu(),
        ]);
    }

    public function submitQuiz(Request $request): RedirectResponse
    {
        abort_unless($this->jeux->ouvert($this->event(), 'quiz'), 404);

        $request->validate($this->regleJoueur() + ['answers' => ['required', 'array']]);

        $participant = $this->jeux->joueur($request->prenom, $request->nom);
        $questions   = $this->jeux->quizPret($this->event());

        // Le score se lit aussi par marié : « vous connaissez Soizic à 4/5 ».
        $parSujet = [];
        $bonnes   = 0;

        foreach ($questions as $i => $question) {
            $juste = (string) $request->input("answers.{$i}") === (string) $question['bonne'];
            $bonnes += (int) $juste;

            if ($question['sujet']) {
                $parSujet[$question['sujet']]['total'] = ($parSujet[$question['sujet']]['total'] ?? 0) + 1;
                $parSujet[$question['sujet']]['bonnes'] = ($parSujet[$question['sujet']]['bonnes'] ?? 0) + (int) $juste;
            }
        }

        $total = $questions->count();

        $this->jeux->enregistrer($participant, 'quiz', (int) round($bonnes / max($total, 1) * 100), [
            'bonnes' => $bonnes, 'total' => $total, 'par_sujet' => $parSujet,
        ]);

        $phrase = "{$bonnes} bonne" . ($bonnes > 1 ? 's' : '') . ' réponse' . ($bonnes > 1 ? 's' : '') . " sur {$total}";

        if (count($parSujet) > 1) {
            $phrase .= ' — ' . collect($parSujet)
                ->map(fn ($s, $prenom) => "{$prenom} : {$s['bonnes']}/{$s['total']}")
                ->implode(', ');
        }

        return $this->resultat('quiz', $phrase);
    }

    // --- Qui de nous 2 ------------------------------------------------------

    public function quiDeux(): View
    {
        if ($attente = $this->attente('qui_deux')) {
            return $attente;
        }

        return view('jeux.qui2', [
            'questions' => QuestionQuiDeux::pretes()->get(),
            'prenoms'   => $this->jeux->prenoms($this->event()),
            'joueur'    => $this->jeux->joueurConnu(),
        ]);
    }

    public function submitQuiDeux(Request $request): RedirectResponse
    {
        abort_unless($this->jeux->ouvert($this->event(), 'qui_deux'), 404);

        $request->validate($this->regleJoueur() + ['answers' => ['required', 'array']]);

        $participant = $this->jeux->joueur($request->prenom, $request->nom);
        $session     = $this->jeux->session('qui_deux');
        $questions   = QuestionQuiDeux::pretes()->get()->keyBy('id');
        $simplifier  = fn ($v) => Str::lower(Str::ascii(trim((string) $v)));

        $bonnes = 0;

        foreach ($questions as $id => $question) {
            $reponse = $request->input("answers.{$id}");

            if ($reponse === null) {
                continue;
            }

            $juste = $simplifier($reponse) === $simplifier($question->bonne_reponse);
            $bonnes += (int) $juste;

            ReponseQuiDeux::updateOrCreate(
                ['participant_id' => $participant->id, 'question_id' => $id, 'session_jeu_id' => $session->id],
                ['reponse' => $reponse, 'correct' => $juste]
            );
        }

        $total = $questions->count();

        $this->jeux->enregistrer($participant, 'qui_deux', (int) round($bonnes / max($total, 1) * 100), [
            'bonnes' => $bonnes, 'total' => $total,
        ]);

        return $this->resultat('qui_deux', "{$bonnes} bonne" . ($bonnes > 1 ? 's' : '') . " réponse" . ($bonnes > 1 ? 's' : '') . " sur {$total}");
    }

    // --- Mots croisés -----------------------------------------------------

    public function motsCroises(): View
    {
        if ($attente = $this->attente('mots_croises')) {
            return $attente;
        }

        return view('jeux.mots-croises', [
            'grille' => $this->jeux->grille($this->event()),
            'joueur' => $this->jeux->joueurConnu(),
        ]);
    }

    public function submitMotsCroises(Request $request): RedirectResponse
    {
        abort_unless($this->jeux->ouvert($this->event(), 'mots_croises'), 404);

        $request->validate($this->regleJoueur() + ['grid' => ['array']]);

        $participant = $this->jeux->joueur($request->prenom, $request->nom);
        $grille      = $this->jeux->grille($this->event());
        $saisie      = (array) $request->input('grid', []);

        // Corrigé sur la grille régénérée : le navigateur ne connaît jamais les réponses.
        $trouves = collect($grille['mots'])->filter(function ($mot) use ($saisie) {
            [$dx, $dy] = $mot['direction'] === 'horizontal' ? [1, 0] : [0, 1];
            $lu = '';
            for ($i = 0; $i < $mot['longueur']; $i++) {
                $lu .= GrilleMotsCroises::normaliser((string) ($saisie[$mot['y'] + $dy * $i][$mot['x'] + $dx * $i] ?? ''));
            }

            return $lu === $mot['mot'];
        })->count();

        $total = count($grille['mots']);

        $this->jeux->enregistrer($participant, 'mots_croises', (int) round($trouves / max($total, 1) * 100), [
            'trouves' => $trouves, 'total' => $total,
        ]);

        return $this->resultat('mots_croises', "{$trouves} mot" . ($trouves > 1 ? 's' : '') . " trouvé" . ($trouves > 1 ? 's' : '') . " sur {$total}");
    }

    // --- Memory -----------------------------------------------------------

    public function memory(): View
    {
        if ($attente = $this->attente('memory')) {
            return $attente;
        }

        return view('jeux.memory', [
            'cartes' => $this->jeux->cartesMemory($this->event()),
            'joueur' => $this->jeux->joueurConnu(),
        ]);
    }

    public function submitMemory(Request $request): RedirectResponse
    {
        abort_unless($this->jeux->ouvert($this->event(), 'memory'), 404);

        $request->validate($this->regleJoueur() + [
            'coups' => ['required', 'integer', 'min:1', 'max:999'],
            'temps' => ['required', 'integer', 'min:1', 'max:36000'],
        ]);

        $participant = $this->jeux->joueur($request->prenom, $request->nom);
        $paires = 8;
        $coups  = max((int) $request->coups, $paires);
        $points = max(10, 100 - ($coups - $paires) * 4);

        $this->jeux->enregistrer($participant, 'memory', $points, [
            'coups' => $coups, 'temps' => (int) $request->temps,
        ]);

        return $this->resultat('memory', "Terminé en {$coups} coups et " . $this->duree((int) $request->temps));
    }

    // --- Puzzle -------------------------------------------------------------

    public function puzzle(): View
    {
        if ($attente = $this->attente('puzzle')) {
            return $attente;
        }

        return view('jeux.puzzle', [
            'image'  => $this->jeux->imagesPuzzle($this->event())->random(),
            'joueur' => $this->jeux->joueurConnu(),
        ]);
    }

    public function submitPuzzle(Request $request): RedirectResponse
    {
        abort_unless($this->jeux->ouvert($this->event(), 'puzzle'), 404);

        $request->validate($this->regleJoueur() + [
            'temps'  => ['required', 'integer', 'min:1', 'max:36000'],
            'resolu' => ['nullable', 'boolean'],
        ]);

        $participant = $this->jeux->joueur($request->prenom, $request->nom);
        $temps = (int) $request->temps;

        // Le bouton « Résoudre » termine la partie, mais ne rapporte rien.
        $points = $request->boolean('resolu') ? 0 : max(20, min(100, 100 - intdiv(max(0, $temps - 60), 3)));

        $this->jeux->enregistrer($participant, 'puzzle', $points, ['temps' => $temps]);

        return $this->resultat('puzzle', $request->boolean('resolu')
            ? 'Puzzle résolu automatiquement'
            : 'Reconstitué en ' . $this->duree($temps));
    }

    // --- Chasse photo -----------------------------------------------------

    public function chassePhoto(): View
    {
        if ($attente = $this->attente('chasse_photo')) {
            return $attente;
        }

        return view('jeux.chasse-photo', [
            'missions' => $this->jeux->missions($this->event()),
            'joueur'   => $this->jeux->joueurConnu(),
        ]);
    }

    public function submitChassePhoto(Request $request): RedirectResponse
    {
        abort_unless($this->jeux->ouvert($this->event(), 'chasse_photo'), 404);

        $missions = $this->jeux->missions($this->event())->all();

        $request->validate($this->regleJoueur() + [
            'indice' => ['required', 'string', \Illuminate\Validation\Rule::in($missions)],
            'photo'  => ['required', 'image', 'max:8192'],
        ]);

        $participant = $this->jeux->joueur($request->prenom, $request->nom);

        ChassePhoto::create([
            'participant_id' => $participant->id,
            'session_jeu_id' => $this->jeux->session('chasse_photo')->id,
            'indice'         => $request->indice,
            'photo_path'     => $request->file('photo')->store('chasse-photos/' . $this->event()->id, 'public'),
            'valide'         => false,
        ]);

        return back()->with('success', 'Photo envoyée ! Les mariés la valideront, et vos points s’ajouteront au classement.');
    }

    // --- Résultats et classement ------------------------------------------

    private function resultat(string $jeu, string $phrase): RedirectResponse
    {
        return redirect()->route('jeux.resultat', $jeu)->with('phrase', $phrase);
    }

    public function afficherResultat(string $jeu): View|RedirectResponse
    {
        abort_unless(config("solen_schema.jeux.{$jeu}"), 404);

        $joueur = $this->jeux->joueurConnu();

        if (empty($joueur['id'])) {
            return redirect()->route(config("solen_schema.jeux.{$jeu}.route"));
        }

        return view('jeux.resultat', [
            'jeu'        => config("solen_schema.jeux.{$jeu}") + ['cle' => $jeu],
            'phrase'     => session('phrase'),
            'score'      => ScoreJeu::where('participant_id', $joueur['id'])->where('type_jeu', $jeu)->first(),
            'rang'       => $this->jeux->rang($joueur['id']),
            'classement' => (bool) $this->event()->reglage('jeux', 'classement', true),
            'autres'     => $this->jeux->ouverts($this->event())->except($jeu),
        ]);
    }

    public function classement(): View
    {
        $event = $this->event();

        abort_unless($event->aModule('jeux') && $event->reglage('jeux', 'classement', true), 404);

        return view('jeux.classement', [
            'lignes' => $this->jeux->classement(),
            'lot'    => $event->reglage('jeux', 'lot'),
            'moi'    => $this->jeux->joueurConnu()['id'] ?? null,
            'jeux'   => $this->jeux->ouverts($event),
        ]);
    }

    private function duree(int $secondes): string
    {
        return $secondes < 60
            ? "{$secondes} s"
            : intdiv($secondes, 60) . ' min ' . str_pad((string) ($secondes % 60), 2, '0', STR_PAD_LEFT);
    }
}
