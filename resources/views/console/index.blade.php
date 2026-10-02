@extends('console.layout')

@section('titre', 'Mariages')
@section('chapeau', 'Tous les mariages de la plateforme.')

@section('contenu')

<div class="tuiles">
    @foreach ($chiffres as $chiffre)
        <div class="tuile">
            <div class="nombre">{{ $chiffre['nombre'] }}</div>
            <div class="quoi">{{ $chiffre['quoi'] }}</div>
        </div>
    @endforeach
</div>

<div class="carte">
    <div class="actions" style="margin:0 0 1.2rem; padding:0; border:0">
        <form method="GET" action="{{ route('console.index') }}" style="display:flex; gap:.5rem; flex:1">
            <input type="search" name="q" value="{{ $recherche }}"
                   placeholder="Rechercher un couple, une ville, un identifiant…"
                   style="flex:1; font:inherit; font-size:.92rem; padding:.6rem .85rem;
                          border:1px solid var(--line); border-radius:var(--r-sm); background:var(--surface)">
            <button class="mini">Chercher</button>
            @if ($recherche)
                <a href="{{ route('console.index') }}" class="mini">Tout voir</a>
            @endif
        </form>
        <a href="{{ route('console.mariage.creer') }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Nouveau mariage
        </a>
    </div>

    @if ($mariages->isEmpty())
        <p class="vide">
            {{ $recherche ? 'Aucun mariage ne correspond à cette recherche.' : 'Aucun mariage pour l’instant.' }}
        </p>
    @else
        <div class="liste">
            @foreach ($mariages as $mariage)
                @php
                    $etat = match ($mariage->statut) {
                        'publie'  => ['En ligne',  'chip-live'],
                        'archive' => ['Archivé',   'chip-next'],
                        default   => ['Brouillon', 'chip-build'],
                    };
                @endphp

                <div class="ligne @if($mariage->statut !== 'publie') inactive @endif">
                    <span class="icone" @if($mariage->theme) style="background:{{ $mariage->theme->surface }}; color:{{ $mariage->theme->ink }}" @endif>
                        <i class="fa-solid fa-heart" aria-hidden="true"></i>
                    </span>

                    <div class="corps">
                        <strong>
                            {{ $mariage->nom }}
                            @if ($mariage->est_demo)
                                <span class="chip chip-build" style="position:static; margin-left:.4rem">démo</span>
                            @endif
                        </strong>
                        <span>
                            {{ $mariage->dateLocale()?->translatedFormat('j F Y') ?? 'Date à définir' }}
                            @if ($mariage->lieu_ville) · {{ $mariage->lieu_ville }} @endif
                            · /{{ $mariage->slug }}
                        </span>
                    </div>

                    <div class="outils">
                        <span class="chip {{ $etat[1] }}" style="position:static">{{ $etat[0] }}</span>
                        <a href="{{ route('console.mariage', $mariage->slug) }}" class="mini">Ouvrir</a>
                        <a href="{{ route('landing', $mariage->slug) }}" target="_blank" rel="noopener" class="mini">Voir</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@endsection
