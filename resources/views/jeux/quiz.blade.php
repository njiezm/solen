@extends('layout')
@section('titre', 'Quiz des mariés')
@section('largeur', 'cadre--etroit')
@section('content')

{{--
    Même mécanique que « Qui de nous 2 » : une question par écran, la
    suivante arrive au toucher. Les propositions sont empilées, une
    réponse pouvant tenir sur une phrase entière.
--}}

<header class="page-tete">
    <p class="page-tete-sur">Jeu</p>
    <h1>Quiz des mariés</h1>
    <p>{{ $questions->count() }} questions sur {{ $prenoms[0] }} et {{ $prenoms[1] }}. Qui les connaît le mieux ?</p>
</header>

<form method="POST" action="{{ route('jeux.submitQuiz') }}" class="bloc" id="quiz">
    @csrf
    @include('jeux.partials.joueur')

    <div class="quiz-progres" aria-hidden="true"><div id="quiz-barre"></div></div>

    @foreach ($questions as $i => $question)
        <fieldset class="quiz-question" data-index="{{ $loop->index }}">
            <legend>
                <span class="quiz-numero">
                    Question {{ $loop->iteration }} / {{ $questions->count() }}
                    @if ($question['sujet']) · {{ $question['sujet'] }} @endif
                </span>
                {{ $question['question'] }}
            </legend>

            <div class="quiz-choix quiz-choix--pile">
                @foreach ($question['ordre'] as $indice)
                    <label>
                        <input type="radio" name="answers[{{ $i }}]" value="{{ $indice }}" required>
                        <span>{{ $question['choix'][$indice] }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endforeach

    <div class="quiz-actions">
        <button type="button" class="bouton bouton--contour" id="quiz-precedent" hidden>
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Précédente
        </button>
        <button type="submit" class="bouton bouton--plein" id="quiz-valider">
            <i class="fa-solid fa-check" aria-hidden="true"></i> Voir mon score
        </button>
    </div>
</form>

@push('scripts')
<script>
(() => {
    const form = document.getElementById('quiz');
    const questions = [...form.querySelectorAll('.quiz-question')];
    const precedent = document.getElementById('quiz-precedent');
    const valider = document.getElementById('quiz-valider');
    const barre = document.getElementById('quiz-barre');
    let courante = 0;

    form.classList.add('quiz--pas-a-pas');

    const montrer = (n) => {
        courante = Math.max(0, Math.min(questions.length - 1, n));
        questions.forEach((q, i) => q.hidden = i !== courante);
        precedent.hidden = courante === 0;
        const derniere = courante === questions.length - 1;
        const repondue = !!questions[courante].querySelector('input:checked');
        valider.hidden = !(derniere && repondue);
        barre.style.width = `${(questions.filter((q) => q.querySelector('input:checked')).length / questions.length) * 100}%`;
    };

    questions.forEach((q) => q.addEventListener('change', () => {
        setTimeout(() => montrer(courante + 1), 280);
        if (courante === questions.length - 1) montrer(courante);
    }));

    precedent.addEventListener('click', () => montrer(courante - 1));
    montrer(0);
})();
</script>
@endpush

@endsection
