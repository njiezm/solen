@extends('layout')
@section('titre', 'Puzzle')
@section('largeur', 'cadre--etroit')
@section('content')

{{-- Une image tirée au hasard parmi celles des mariés, à chaque partie. --}}

<header class="page-tete">
    <p class="page-tete-sur">Jeu</p>
    <h1>Puzzle</h1>
    <p>Faites glisser les pièces dans le cadre. Le chrono démarre à la première.</p>
</header>

<div class="puzzle-barre">
    <span><i class="fa-regular fa-clock" aria-hidden="true"></i> <strong id="puzzle-chrono">0:00</strong></span>
    <span style="display:flex; gap:.5rem">
        <a href="{{ route('jeux.puzzle') }}" class="bouton bouton--contour bouton--petit">
            <i class="fa-solid fa-shuffle" aria-hidden="true"></i> Autre image
        </a>
        <button type="button" class="bouton bouton--contour bouton--petit" id="puzzle-resoudre">
            <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Résoudre
        </button>
    </span>
</div>

<div class="puzzle-cadre">
    <svg class="puzzle" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 220 400" aria-label="Puzzle à reconstituer">
        <rect class="box" width="125" height="100" fill="currentColor" fill-opacity="0.06"
              stroke="currentColor" stroke-opacity="0.5" stroke-width="1.2" stroke-dasharray="5 4" rx="3" />
        <g class="ghost"></g>
        <use class="endImg" href="#imgSrc" opacity="0" />
        <g class="pieces"></g>
        @include('jeux.partials.puzzle-pieces')
    </svg>
    {{-- Pendant que le puzzle se prépare : un cœur qui bat, et une phrase
         sur l'amour tirée au hasard à chaque partie. --}}
    @php
        $phrases = [
            'Le bonheur se construit à deux, pièce par pièce.',
            'Aimer, c’est choisir l’autre chaque jour.',
            'Deux cœurs, une seule histoire.',
            'Ensemble, tout trouve sa place.',
            'Chaque souvenir est une pièce du puzzle.',
            'Le plus beau voyage se fait à deux.',
            'L’amour, c’est le plus beau des assemblages.',
            'Un oui, et tout s’emboîte.',
        ];
    @endphp
    <div class="puzzle-chargement" role="status" aria-live="polite">
        <span class="puzzle-chargeur" aria-hidden="true"><i class="fa-solid fa-heart"></i></span>
        <p>{{ \Illuminate\Support\Arr::random($phrases) }}</p>
    </div>
</div>

{{-- Confirmation de « Résoudre », aux couleurs du mariage plutôt que la
     boîte grise du navigateur. --}}
<dialog class="modale" id="puzzle-confirmation" aria-labelledby="puzzle-confirmation-titre">
    <span class="modale-icone" aria-hidden="true"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
    <h2 id="puzzle-confirmation-titre">Résoudre le puzzle ?</h2>
    <p>Les pièces se mettront en place toutes seules, mais la partie ne rapportera aucun point au classement.</p>
    <div class="modale-actions">
        <button type="button" class="bouton bouton--plein" data-modale="continuer" autofocus>
            <i class="fa-solid fa-puzzle-piece" aria-hidden="true"></i> Je continue
        </button>
        <button type="button" class="bouton bouton--contour" data-modale="resoudre">Résoudre quand même</button>
    </div>
</dialog>

<form method="POST" action="{{ route('jeux.submitPuzzle') }}" class="bloc" id="puzzle-fin" hidden style="margin-top:1rem">
    @csrf
    <input type="hidden" name="temps" value="1">
    <input type="hidden" name="resolu" value="0">
    <h2 class="bloc-titre" style="justify-content:center" data-titre></h2>
    @include('jeux.partials.joueur')
    <button class="bouton bouton--plein bouton--large"><i class="fa-solid fa-trophy" aria-hidden="true"></i> Enregistrer mon score</button>
</form>

@push('scripts')
<script>window.puzzleConfig = { image: @json($image) };</script>
<script>
(() => {
    // On arrive directement sur le jeu : la barre du puzzle se cale sous
    // l'en-tête, et le cadre prend exactement la hauteur qui reste, quelle
    // que soit la taille du téléphone. Plus rien à faire défiler.
    const barreJeu = document.querySelector('.puzzle-barre');
    const cadre = document.querySelector('.puzzle-cadre');

    const ajuster = () => {
        const entete = document.getElementById('entete')?.getBoundingClientRect().height ?? 0;
        const basse = document.querySelector('.barre-basse');
        const bas = basse && getComputedStyle(basse).display !== 'none' ? Math.max(0, innerHeight - basse.getBoundingClientRect().top) : 0;
        const dispo = innerHeight - entete - bas - barreJeu.offsetHeight - 30;
        cadre.style.setProperty('--puzzle-hauteur', `${Math.max(260, dispo)}px`);
    };

    const caler = () => {
        ajuster();
        // Seulement à l'arrivée, et si l'invité n'a pas déjà défilé lui-même.
        if (scrollY < 40) barreJeu.scrollIntoView({ block: 'start', behavior: 'smooth' });
    };

    addEventListener('resize', ajuster);
    if (document.readyState === 'complete') caler(); else addEventListener('load', caler);
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/Draggable.min.js"></script>
<script src="{{ asset('js/puzzle.js') }}?v={{ filemtime(public_path('js/puzzle.js')) }}"></script>
@endpush

@endsection
