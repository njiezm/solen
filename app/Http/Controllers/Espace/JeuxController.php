<?php

namespace App\Http\Controllers\Espace;

use App\Http\Controllers\Controller;
use App\Models\ChassePhoto;
use App\Models\Participant;
use App\Models\QuestionQuiDeux;
use App\Solen\CurrentEvent;
use App\Solen\GrilleMotsCroises;
use App\Solen\Jeux;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * L'écran « Jeux » des mariés : tout au même endroit.
 *
 * Remplace les écrans « sessions », « questions », « mots croisés » et
 * « cartes memory » de l'ancienne administration. Plus rien à lancer à la
 * main : on coche un jeu, on personnalise si l'on veut, il est en ligne.
 *
 * Toutes les recherches sont explicites (findOrFail sur un modèle
 * cloisonné) : la liaison implicite des routes s'exécute avant que le
 * mariage courant ne soit connu, elle ne filtrerait rien.
 */
class JeuxController extends Controller
{
    public const PUZZLE_MAX = 5;

    public function __construct(
        private readonly Jeux $jeux,
        private readonly CurrentEvent $courant,
    ) {
    }

    public function index(): View
    {
        $event = $this->courant->get();

        abort_unless($event->aModule('jeux'), 404);

        $catalogue = collect(config('solen_schema.jeux'))->map(fn ($jeu, $cle) => $jeu + [
            'cle'    => $cle,
            'inclus' => $this->jeux->inclus($event, $cle),
            'active' => $this->jeux->active($event, $cle),
            'pret'   => $this->jeux->pret($event, $cle),
        ]);

        return view('espace.jeux', [
            'catalogue'    => $catalogue,
            'prenoms'      => $this->jeux->prenoms($event),
            'quiz'         => $this->jeux->quiz($event),
            'quizPretes'   => $this->jeux->quizPret($event)->count(),
            'quizMax'      => Jeux::QUIZ_MAX,
            'questions'    => QuestionQuiDeux::withCount('reponses')->orderBy('ordre')->orderBy('id')->get(),
            'texteMots'    => $this->jeux->texteMots($event),
            'grille'       => $this->jeux->grille($event),
            'missions'     => $this->jeux->missions($event)->implode("\n"),
            'imagesPuzzle' => $this->jeux->imagesPuzzleDeposees($event),
            'photosCouple' => $this->jeux->photosDuCouple($event),
            'chasse'       => ChassePhoto::with('participant')->orderBy('valide')->latest()->get(),
            'classement'   => $this->jeux->classement(10),
            'lot'          => $event->reglage('jeux', 'lot'),
            'avecClassement' => (bool) $event->reglage('jeux', 'classement', true),
            'questionsMin' => Jeux::QUESTIONS_MIN,
            'puzzleMax'    => self::PUZZLE_MAX,
        ]);
    }

    // --- Activation ---------------------------------------------------------

    public function basculer(string $cle): RedirectResponse
    {
        $event = $this->courant->get();
        abort_unless(config("solen_schema.jeux.{$cle}") && $this->jeux->inclus($event, $cle), 404);

        $actifs = collect((array) $event->reglage('jeux', 'actifs', []));
        $actifs = $actifs->contains($cle) ? $actifs->reject(fn ($c) => $c === $cle) : $actifs->push($cle);

        $event->ecrireReglages('jeux', ['actifs' => $actifs->values()->all()]);

        return back()->with('ok', 'Les jeux proposés ont été mis à jour.');
    }

    public function reglages(Request $request): RedirectResponse
    {
        $donnees = $request->validate(['lot' => ['nullable', 'string', 'max:255']]);

        $this->courant->get()->ecrireReglages('jeux', [
            'lot'        => $donnees['lot'] ?? null,
            'classement' => $request->boolean('classement'),
        ]);

        return back()->with('ok', 'Le classement a été mis à jour.');
    }

    // --- Quiz des mariés ---------------------------------------------------

    /**
     * Le quiz est réécrit d'un bloc : dix questions, trois propositions
     * chacune, on corrige et on renvoie le tout. Les lignes entièrement
     * vides sont écartées ; celles à moitié remplies sont gardées, pour
     * qu'un témoin puisse finir ce qu'un autre a commencé.
     */
    public function enregistrerQuiz(Request $request): RedirectResponse
    {
        $event   = $this->courant->get();
        $prenoms = $this->jeux->prenoms($event);

        $donnees = $request->validate([
            'quiz'              => ['nullable', 'array', 'max:' . Jeux::QUIZ_MAX],
            'quiz.*.sujet'      => ['nullable', Rule::in($prenoms)],
            'quiz.*.question'   => ['nullable', 'string', 'max:255'],
            'quiz.*.choix'      => ['nullable', 'array', 'size:' . Jeux::QUIZ_CHOIX],
            'quiz.*.choix.*'    => ['nullable', 'string', 'max:150'],
            'quiz.*.bonne'      => ['nullable', 'integer', 'min:0', 'max:' . (Jeux::QUIZ_CHOIX - 1)],
        ], [], ['quiz.*.question' => 'question', 'quiz.*.choix.*' => 'proposition']);

        $quiz = collect($donnees['quiz'] ?? [])
            ->map(fn ($q) => [
                'sujet'    => $q['sujet'] ?? null,
                'question' => trim((string) ($q['question'] ?? '')),
                'choix'    => array_map(fn ($c) => trim((string) $c), array_values($q['choix'] ?? array_fill(0, Jeux::QUIZ_CHOIX, ''))),
                'bonne'    => isset($q['bonne']) ? (int) $q['bonne'] : null,
            ])
            ->reject(fn ($q) => $q['question'] === '' && ! array_filter($q['choix']))
            ->values()
            ->all();

        $event->ecrireReglages('jeux', ['quiz' => $quiz]);

        return back()->with('ok', 'Le quiz a été enregistré.')->withFragment('quiz');
    }

    // --- Qui de nous 2 ------------------------------------------------------

    public function ajouterQuestion(Request $request): RedirectResponse
    {
        $donnees = $request->validate(['question' => ['required', 'string', 'max:255']]);

        QuestionQuiDeux::create([
            'question' => $donnees['question'],
            'active'   => true,
            'ordre'    => (int) QuestionQuiDeux::max('ordre') + 1,
        ]);

        return back()->with('ok', 'Question ajoutée. Désignez maintenant lequel de vous deux.')->withFragment('qui-deux');
    }

    public function repondre(Request $request, int $id): RedirectResponse
    {
        $question = QuestionQuiDeux::findOrFail($id);

        $donnees = $request->validate([
            'reponse' => ['required', Rule::in($this->jeux->prenoms($this->courant->get()))],
        ]);

        $question->update(['bonne_reponse' => $donnees['reponse']]);

        return back()->withFragment('question-' . $question->id);
    }

    public function basculerQuestion(int $id): RedirectResponse
    {
        $question = QuestionQuiDeux::findOrFail($id);
        $question->update(['active' => ! $question->active]);

        return back()->withFragment('question-' . $question->id);
    }

    public function supprimerQuestion(int $id): RedirectResponse
    {
        QuestionQuiDeux::findOrFail($id)->delete();

        return back()->with('ok', 'Question supprimée.')->withFragment('qui-deux');
    }

    // --- Mots croisés et chasse photo -----------------------------------

    public function mots(Request $request): RedirectResponse
    {
        $donnees = $request->validate(['mots' => ['nullable', 'string', 'max:5000']]);
        $texte = trim((string) ($donnees['mots'] ?? ''));

        if ($texte !== '' && count(GrilleMotsCroises::lire($texte)) < 4) {
            return back()->withInput()->with('erreur', 'Il faut au moins quatre mots, au format « MOT : définition ».')->withFragment('mots-croises');
        }

        // Vide : retour à la liste par défaut.
        $this->courant->get()->ecrireReglages('jeux', ['mots' => $texte ?: null]);

        return back()->with('ok', 'La grille a été régénérée.')->withFragment('mots-croises');
    }

    public function missions(Request $request): RedirectResponse
    {
        $donnees = $request->validate(['missions' => ['nullable', 'string', 'max:3000']]);

        $this->courant->get()->ecrireReglages('jeux', ['missions' => trim((string) ($donnees['missions'] ?? '')) ?: null]);

        return back()->with('ok', 'Les missions ont été enregistrées.')->withFragment('chasse');
    }

    public function validerPhoto(int $id): RedirectResponse
    {
        $photo = ChassePhoto::findOrFail($id);
        $photo->update(['valide' => ! $photo->valide]);

        $this->recompterChasse($photo->participant_id);

        return back()->withFragment('chasse');
    }

    public function supprimerPhoto(int $id): RedirectResponse
    {
        $photo = ChassePhoto::findOrFail($id);
        Storage::disk('public')->delete($photo->photo_path);
        $photo->delete();

        $this->recompterChasse($photo->participant_id);

        return back()->with('ok', 'Photo retirée.')->withFragment('chasse');
    }

    /** 20 points par photo validée, 100 au plus. */
    private function recompterChasse(int $participantId): void
    {
        $participant = Participant::find($participantId);

        if (! $participant) {
            return;
        }

        $validees = ChassePhoto::where('participant_id', $participantId)->where('valide', true)->count();

        \App\Models\ScoreJeu::updateOrCreate(
            ['participant_id' => $participantId, 'type_jeu' => 'chasse_photo'],
            ['points' => min(100, $validees * 20), 'detail' => ['validees' => $validees]]
        );
    }

    // --- Puzzle -------------------------------------------------------------

    public function ajouterImagesPuzzle(Request $request): RedirectResponse
    {
        $event = $this->courant->get();
        abort_unless($this->jeux->inclus($event, 'puzzle'), 404);

        $actuelles = $this->jeux->imagesPuzzleDeposees($event);
        $place = self::PUZZLE_MAX - $actuelles->count();

        $request->validate([
            'images'   => ['required', 'array', 'min:1', 'max:' . max(1, $place)],
            'images.*' => ['image', 'max:8192'],
        ], [
            'images.max' => "Le puzzle accepte {$this->puzzleMaxTexte()} au total.",
        ]);

        if ($place <= 0) {
            return back()->with('erreur', "Le puzzle accepte {$this->puzzleMaxTexte()} au total. Retirez-en une d’abord.")->withFragment('puzzle');
        }

        $nouvelles = collect($request->file('images'))
            ->take($place)
            ->map(fn ($f) => $f->store("jeux/{$event->id}/puzzle", 'public'));

        $event->ecrireReglages('jeux', ['puzzle_images' => $actuelles->concat($nouvelles)->values()->all()]);

        return back()->with('ok', 'Images du puzzle enregistrées.')->withFragment('puzzle');
    }

    public function retirerImagePuzzle(int $index): RedirectResponse
    {
        $event = $this->courant->get();
        $images = $this->jeux->imagesPuzzleDeposees($event);

        abort_unless($images->has($index), 404);

        Storage::disk('public')->delete($images[$index]);
        $event->ecrireReglages('jeux', ['puzzle_images' => $images->forget($index)->values()->all()]);

        return back()->with('ok', 'Image retirée.')->withFragment('puzzle');
    }

    private function puzzleMaxTexte(): string
    {
        return self::PUZZLE_MAX . ' images';
    }
}
