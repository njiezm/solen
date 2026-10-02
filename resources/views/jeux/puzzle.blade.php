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
</div>

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
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/Draggable.min.js"></script>
<script src="{{ asset('js/puzzle.js') }}?v={{ filemtime(public_path('js/puzzle.js')) }}"></script>
@endpush

@endsection
