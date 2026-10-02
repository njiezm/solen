@extends('espace.layout')

@section('titre', 'Statistiques')
@section('retour', route('espace.index'))
@section('retour-libelle', 'Tableau de bord')
@section('chapeau', 'Ce que vos invités ont fait sur votre site.')

@section('contenu')

@php
    $sessions = [
        ['nom' => 'Qui de nous 2 ?', 'icone' => 'fa-question',               'liste' => $sessionsQuiDeux],
        ['nom' => 'Chasse photo',    'icone' => 'fa-magnifying-glass-chart', 'liste' => $sessionsChassePhoto],
        ['nom' => 'Mots croisés',    'icone' => 'fa-puzzle-piece',           'liste' => $sessionsMotsCroises],
        ['nom' => 'Memory',          'icone' => 'fa-brain',                  'liste' => $sessionsMemory],
    ];
@endphp

<div class="tuiles">
    <div class="tuile">
        <div class="nombre">{{ $participantsQuiDeux }}</div>
        <div class="quoi">invités ayant joué</div>
    </div>
    <div class="tuile">
        <div class="nombre">{{ $photosChasse }}</div>
        <div class="quoi">photos de la chasse</div>
    </div>
    <div class="tuile">
        <div class="nombre">{{ $scansCount }}</div>
        <div class="quoi">scans de QR codes</div>
    </div>
    <div class="tuile">
        <div class="nombre">{{ collect($sessions)->sum(fn ($s) => $s['liste']->count()) }}</div>
        <div class="quoi">parties créées</div>
    </div>
</div>

@foreach ($sessions as $jeu)
    <div class="carte">
        <h2><i class="fa-solid {{ $jeu['icone'] }}" aria-hidden="true"></i> {{ $jeu['nom'] }}</h2>

        @if ($jeu['liste']->isEmpty())
            <p class="carte-aide" style="margin:0">Aucune partie créée pour ce jeu.</p>
        @else
            <div class="liste">
                @foreach ($jeu['liste'] as $session)
                    <div class="ligne @unless($session->actif) inactive @endunless">
                        <span class="icone"><i class="fa-solid {{ $jeu['icone'] }}" aria-hidden="true"></i></span>

                        <div class="corps">
                            <strong>{{ $session->nom }}</strong>
                            <span>
                                {{ $session->actif ? 'En cours' : 'Terminée' }}
                                @if ($session->debut)
                                    · lancée le {{ $session->debut->translatedFormat('j F à H\hi') }}
                                @endif
                            </span>
                        </div>

                        <div class="outils">
                            @if (Route::has('admin.resultats'))
                                <a href="{{ route('admin.resultats', $session->id) }}" class="mini">Résultats</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endforeach

@endsection
