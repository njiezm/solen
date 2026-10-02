@extends('layout')
@section('titre', 'Menu')
@section('largeur', 'cadre--etroit')
@section('content')

{{-- Le menu vient des blocs « plat », regroupés par moment du repas. --}}

<header class="page-tete">
    <p class="page-tete-sur">À table</p>
    <h1>Le menu</h1>
    @if ($introduction)
        <p>{{ $introduction }}</p>
    @endif
</header>

@if ($sections->isEmpty())
    <div class="vide">
        <i class="fa-solid fa-utensils" aria-hidden="true"></i>
        Le menu sera publié prochainement.
    </div>
@else
    @foreach ($sections as $section => $plats)
        <section class="menu-section">
            <h2>{{ $categories[$section] ?? $section }}</h2>

            <div class="bloc">
                @foreach ($plats as $plat)
                    <div class="plat">
                        <strong>{{ $plat->nom }}</strong>
                        @if ($plat->description)
                            <p>{{ $plat->description }}</p>
                        @endif
                        @if ($plat->allergenes)
                            <p class="plat-allergenes">
                                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                                Contient : {{ $plat->allergenes }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
@endif

@if ($collecteAllergies)
    <section class="bloc" style="margin-top:2rem; text-align:center">
        <h2 class="bloc-titre" style="justify-content:center">
            <span class="pastille"><i class="fa-solid fa-wheat-awn-circle-exclamation" aria-hidden="true"></i></span>
            Une allergie, un régime particulier ?
        </h2>
        <p>
            Dites-le-nous
            @if ($dateLimite)
                avant le {{ $dateLimite->translatedFormat('j F Y') }}
            @endif
            pour que le traiteur puisse s’organiser.
        </p>

        {{-- Avec le RSVP, les allergies sont recueillies avec la réponse : le
             traiteur les retrouve dans l'export, sans boîte mail à dépouiller. --}}
        @if ($event->aModule('rsvp'))
            <a href="{{ route('rsvp') }}" class="bouton bouton--plein">
                <i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i> L’indiquer avec ma réponse
            </a>
        @elseif ($contact)
            <a href="mailto:{{ $contact }}" class="bouton bouton--plein">
                <i class="fa-solid fa-envelope" aria-hidden="true"></i> Nous prévenir
            </a>
        @endif
    </section>
@endif

@endsection
