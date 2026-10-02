@extends('layout')
@section('titre', 'Classement')
@section('largeur', 'cadre--etroit')
@section('content')

<header class="page-tete">
    <p class="page-tete-sur">Les jeux</p>
    <h1>Classement</h1>
    <p>La somme des meilleurs scores de chacun, tous jeux confondus.</p>
</header>

@if ($lot)
    <div class="bandeau-info" style="margin:0 0 1.25rem">
        <i class="fa-solid fa-gift" aria-hidden="true"></i>
        <span>À gagner : <strong>{{ $lot }}</strong></span>
    </div>
@endif

@if ($lignes->isEmpty())
    <div class="vide">
        <i class="fa-solid fa-trophy" aria-hidden="true"></i>
        Personne n’a encore joué. Lancez-vous, la première place est libre !
    </div>
@else
    <ol class="bloc bloc--liste podium">
        @foreach ($lignes as $ligne)
            <li class="{{ $ligne['participant_id'] === $moi ? 'moi' : '' }}">
                <span class="podium-rang rang-{{ min($ligne['rang'], 4) }}">{{ $ligne['rang'] }}</span>
                <span class="podium-nom">
                    {{ $ligne['prenom'] }} {{ Str::substr((string) $ligne['nom'], 0, 1) }}.
                    @if ($ligne['participant_id'] === $moi) (vous) @endif
                    <small class="doux">{{ $ligne['jeux'] }} {{ $ligne['jeux'] > 1 ? 'jeux' : 'jeu' }}</small>
                </span>
                <strong class="podium-points">{{ $ligne['points'] }}</strong>
            </li>
        @endforeach
    </ol>
@endif

@if ($jeux->isNotEmpty())
    <section class="section">
        <div class="section-tete"><h2>Marquer des points</h2></div>
        <div class="tuiles">
            @foreach ($jeux as $jeu)
                <a href="{{ route($jeu['route']) }}" class="tuile">
                    <span class="pastille"><i class="fa-solid {{ $jeu['icone'] }}" aria-hidden="true"></i></span>
                    <strong>{{ $jeu['nom'] }}</strong>
                    <span class="tuile-desc">{{ $jeu['desc'] }}</span>
                </a>
            @endforeach
        </div>
    </section>
@endif

@endsection
