@extends('layout')
@section('content')

@php
    /*
     * La page d'entrée est ce que l'invité voit en premier. Une photo plein
     * écran vaut mieux qu'une carte blanche : c'est ce qui distingue un site
     * de mariage d'un tableau de bord.
     *
     * Sans photo, on retombe sur un dégradé construit avec les couleurs du
     * thème — jamais sur un espace vide.
     */
    $couverture = $event->reglage('site', 'photo_couverture');
    $lieu       = $event->parts->firstWhere('lieu_nom', '!=', null);
    $p1 = $event->partenaire_1;
    $p2 = $event->partenaire_2;
@endphp

<section class="couverture {{ $couverture ? 'couverture--photo' : '' }}">
    @if ($couverture)
        <img class="couverture-image"
             src="{{ Str::startsWith($couverture, ['http', '/']) ? $couverture : Storage::url($couverture) }}"
             alt="" fetchpriority="high">
        <div class="couverture-voile" aria-hidden="true"></div>
    @else
        <div class="couverture-fond" aria-hidden="true"></div>
    @endif

    <div class="couverture-contenu">
        <p class="couverture-sur">Nous nous marions</p>

        <h1 class="couverture-titre">
            @if ($p1 && $p2)
                {{ $p1 }} <span class="et">&amp;</span> {{ $p2 }}
            @else
                {{ $event->nom }}
            @endif
        </h1>

        @if ($mot = $event->reglage('site', 'message_accueil'))
            <p class="couverture-mot">{{ $mot }}</p>
        @endif

        @if ($event->dateLocale())
            <span class="couverture-date">{{ $event->dateLocale()->format('d · m · Y') }}</span>
        @endif

        @if ($lieu?->lieu_nom || $event->lieu_ville)
            <span class="couverture-lieu">
                {{ collect([$lieu?->lieu_nom, $event->lieu_ville])->filter()->unique()->implode(' · ') }}
            </span>
        @endif

        <div class="couverture-action">
            <a href="{{ route('home') }}" class="bouton {{ $couverture ? 'bouton--clair' : 'bouton--plein' }}">
                Entrer <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>

    <a href="{{ route('home') }}" class="couverture-suite" aria-label="Entrer sur le site du mariage">
        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
    </a>
</section>

@endsection
