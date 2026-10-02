@extends('layout')
@section('titre', 'Votre score')
@section('largeur', 'cadre--etroit')
@section('content')

<section class="bloc resultat">
    <span class="pastille resultat-icone"><i class="fa-solid {{ $jeu['icone'] }}" aria-hidden="true"></i></span>
    <p class="page-tete-sur" style="margin:0">{{ $jeu['nom'] }}</p>

    @if ($score)
        <p class="resultat-points"><strong>{{ $score->points }}</strong><span>/100</span></p>
    @endif

    @if ($phrase)
        <p class="resultat-phrase">{{ $phrase }}</p>
    @endif

    @if ($classement && $rang)
        <p class="doux" style="margin:0 0 1.4rem">
            Vous êtes <strong>{{ $rang === 1 ? '1er' : $rang . 'e' }}</strong> au classement général.
        </p>
    @endif

    <div class="resultat-actions">
        <a href="{{ route($jeu['route']) }}" class="bouton bouton--contour">
            <i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Rejouer
        </a>
        @if ($classement)
            <a href="{{ route('jeux.classement') }}" class="bouton bouton--plein">
                <i class="fa-solid fa-trophy" aria-hidden="true"></i> Le classement
            </a>
        @endif
    </div>
    <p class="doux" style="margin:1rem 0 0; font-size:.84rem">Seul votre meilleur score compte.</p>
</section>

@if ($autres->isNotEmpty())
    <section class="section">
        <div class="section-tete"><h2>D’autres jeux</h2></div>
        <div class="tuiles">
            @foreach ($autres as $autre)
                <a href="{{ route($autre['route']) }}" class="tuile">
                    <span class="pastille"><i class="fa-solid {{ $autre['icone'] }}" aria-hidden="true"></i></span>
                    <strong>{{ $autre['nom'] }}</strong>
                    <span class="tuile-desc">{{ $autre['desc'] }}</span>
                </a>
            @endforeach
        </div>
    </section>
@endif

@endsection
