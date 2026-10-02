@extends('layout')
@section('titre', $partie->nom)
@section('largeur', 'cadre--etroit')
@section('content')

{{--
    Une seule vue pour tous les moments de la journée. Aucun lieu, aucun
    horaire n'est écrit ici : tout vient de la partie et de son programme,
    éditables depuis l'espace des mariés.

    Le suivi en direct est archivé : le programme s'affiche tel quel, et le
    texte de la cérémonie se lit dans le livret PDF.
--}}

<header class="page-tete">
    <p class="page-tete-sur">
        @if ($partie->debut_at)
            {{ $event->enHeureLocale($partie->debut_at)->translatedFormat('l j F') }}
        @else
            Le jour J
        @endif
    </p>
    <h1>{{ $partie->nom }}</h1>
    @if ($partie->description)
        <p>{{ $partie->description }}</p>
    @endif
</header>

@if ($partie->lieu_nom || $partie->debut_at || $partie->lieu_adresse)
    <section class="bloc moment-lieu">
        @if ($partie->lieu_nom)
            <h2>{{ $partie->lieu_nom }}</h2>
        @endif

        <div class="moment-horaires">
            @if ($partie->accueil_at)
                <span class="moment-horaire">Accueil <strong>{{ $event->enHeureLocale($partie->accueil_at)->format('H\hi') }}</strong></span>
            @endif
            @if ($partie->debut_at)
                <span class="moment-horaire">Début <strong>{{ $event->enHeureLocale($partie->debut_at)->format('H\hi') }}</strong></span>
            @endif
        </div>

        @if ($partie->lieu_adresse)
            <p class="moment-adresse">
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                {{ $partie->lieu_adresse }}
            </p>
        @endif

        @if ($lien = $partie->lienCarte())
            <a href="{{ $lien }}" class="bouton bouton--fort" target="_blank" rel="noopener">
                <i class="fa-solid fa-route" aria-hidden="true"></i> Itinéraire
            </a>
        @endif
    </section>
@endif

@if ($livret)
    <a href="{{ route('livret') }}" class="tuile tuile--vedette" style="margin-top:1rem; flex-direction:row; align-items:center">
        <span class="pastille"><i class="fa-solid fa-book-bible" aria-hidden="true"></i></span>
        <span style="flex:1">
            <strong style="display:block">Ouvrir le livret</strong>
            <span class="tuile-desc">Lectures, chants et prières, page par page</span>
        </span>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    </a>
@endif

@if ($remerciements)
    <section class="bloc" style="margin-top:1rem; text-align:center">
        <h2 class="bloc-titre" style="justify-content:center">{{ $remerciements->titre }}</h2>
        <p>{!! nl2br(e($remerciements->contenu)) !!}</p>
        @if ($remerciements->signatures)
            <p style="margin:0; font-weight:600">{{ $remerciements->signatures }}</p>
        @endif
    </section>
@endif

@if ($etapes->isNotEmpty())
    <section class="section">
        <div class="section-tete"><h2>Le déroulé</h2></div>

        <ol class="etapes">
            @foreach ($etapes as $etape)
                <li class="etape">
                    <span class="etape-point">
                        <i class="fa-solid {{ $etape->icone ?: 'fa-circle' }}" aria-hidden="true"></i>
                    </span>
                    <div class="etape-corps">
                        <h3>{{ $etape->titre }}</h3>
                        @if ($etape->description)
                            <p>{{ $etape->description }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </section>
@endif

@endsection
