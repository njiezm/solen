@extends('layout')
@section('titre', 'Infos pratiques')
@section('largeur', 'cadre--etroit')
@section('content')

{{--
    Aucun contenu n'est écrit dans cette vue : tout provient des blocs
    éditables depuis l'espace des mariés.
--}}

@php
    $tel = fn (?string $n) => 'tel:' . preg_replace('/[^\d+]/', '', (string) $n);
    $iconesTransport = [
        'voiture' => 'fa-square-parking', 'navette' => 'fa-van-shuttle', 'avion' => 'fa-plane',
        'taxi'    => 'fa-taxi',           'train'   => 'fa-train',
    ];
@endphp

<header class="page-tete">
    <p class="page-tete-sur">Pour venir</p>
    <h1>Infos pratiques</h1>
    @if ($introduction)
        <p>{{ $introduction }}</p>
    @endif
</header>

@if ($hebergements->isEmpty() && $transports->isEmpty() && $infos->isEmpty() && $contacts->isEmpty() && ! $dressCode)
    <div class="vide">
        <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
        Les informations pratiques seront publiées prochainement.
    </div>
@endif

@if ($hebergements->isNotEmpty())
    <section class="bloc">
        <h2 class="bloc-titre"><span class="pastille"><i class="fa-solid fa-hotel" aria-hidden="true"></i></span> Où dormir</h2>

        <ul class="fiches">
            @foreach ($hebergements as $lieu)
                <li class="fiche">
                    <span class="fiche-icone"><i class="fa-solid fa-bed" aria-hidden="true"></i></span>
                    <div class="fiche-titre">
                        {{ $lieu->nom }}
                        @if ($lieu->distance) <small>· {{ $lieu->distance }}</small> @endif
                    </div>
                    @if ($lieu->adresse || $lieu->prix || $lieu->code_promo)
                        <div class="fiche-meta">
                            {{ $lieu->adresse }}
                            @if ($lieu->prix)<br>{{ $lieu->prix }}@endif
                            @if ($lieu->code_promo) · code <strong>{{ $lieu->code_promo }}</strong>@endif
                        </div>
                    @endif
                    @if ($lieu->telephone || $lieu->lien)
                        <div class="fiche-actions">
                            @if ($lieu->telephone)
                                <a href="{{ $tel($lieu->telephone) }}" class="bouton bouton--contour bouton--petit">
                                    <i class="fa-solid fa-phone" aria-hidden="true"></i> {{ $lieu->telephone }}
                                </a>
                            @endif
                            @if ($lieu->lien)
                                <a href="{{ $lieu->lien }}" target="_blank" rel="noopener" class="bouton bouton--contour bouton--petit">
                                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Réserver
                                </a>
                            @endif
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>

        @php $premier = $hebergements->first(); @endphp
        @if ($premier->adresse)
            <div class="carte-integree">
                <iframe src="https://www.google.com/maps?q={{ urlencode($premier->adresse) }}&output=embed"
                        loading="lazy" title="Carte : {{ $premier->nom }}"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        @endif
    </section>
@endif

@if ($transports->isNotEmpty())
    <section class="bloc">
        <h2 class="bloc-titre"><span class="pastille"><i class="fa-solid fa-car-side" aria-hidden="true"></i></span> Venir et repartir</h2>

        <ul class="fiches">
            @foreach ($transports as $transport)
                <li class="fiche">
                    <span class="fiche-icone"><i class="fa-solid {{ $iconesTransport[$transport->mode] ?? 'fa-location-arrow' }}" aria-hidden="true"></i></span>
                    <div class="fiche-titre">{{ $transport->titre }}</div>
                    @if ($transport->description)
                        <div class="fiche-meta">{{ $transport->description }}</div>
                    @endif
                    @if ($transport->telephone || $transport->whatsapp || $transport->lien)
                        <div class="fiche-actions">
                            @if ($transport->telephone)
                                <a href="{{ $tel($transport->telephone) }}" class="bouton bouton--contour bouton--petit">
                                    <i class="fa-solid fa-phone" aria-hidden="true"></i> {{ $transport->telephone }}
                                </a>
                            @endif
                            @if ($transport->whatsapp)
                                <a href="https://wa.me/{{ $transport->whatsapp }}" target="_blank" rel="noopener" class="bouton bouton--whatsapp bouton--petit">
                                    <i class="fa-brands fa-whatsapp" aria-hidden="true"></i> WhatsApp
                                </a>
                            @endif
                            @if ($transport->lien)
                                <a href="{{ $transport->lien }}" target="_blank" rel="noopener" class="bouton bouton--contour bouton--petit">
                                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Site
                                </a>
                            @endif
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
@endif

@foreach ($infos as $info)
    <section class="bloc">
        <h2 class="bloc-titre"><span class="pastille"><i class="fa-solid {{ $info->icone ?: 'fa-circle-info' }}" aria-hidden="true"></i></span> {{ $info->titre }}</h2>
        <p style="margin:0">{!! nl2br(e($info->contenu)) !!}</p>
    </section>
@endforeach

@if ($dressCode)
    <section class="bloc" style="text-align:center">
        <h2 class="bloc-titre" style="justify-content:center"><span class="pastille"><i class="fa-solid fa-shirt" aria-hidden="true"></i></span> Code vestimentaire</h2>
        <p class="titre-display" style="margin:0; font-size:1.5rem; color:var(--c-accent-carte)">{{ $dressCode }}</p>
    </section>
@endif

@php
    $contactJour = $event->reglage('pratique', 'contact_nom');
    $telJour     = $event->reglage('pratique', 'contact_telephone');
@endphp

@if ($contactJour || $telJour)
    <section class="bloc" style="background:var(--c-accent-pale)">
        <h2 class="bloc-titre"><span class="pastille" style="background:var(--c-carte)"><i class="fa-solid fa-phone-volume" aria-hidden="true"></i></span> Le jour J, un souci ?</h2>
        <p style="margin:0 0 .8rem">Appelez {{ $contactJour ?: 'notre contact' }}, qui s’occupe de tout pour nous.</p>
        @if ($telJour)
            <a href="{{ $tel($telJour) }}" class="bouton bouton--fort"><i class="fa-solid fa-phone" aria-hidden="true"></i> {{ $telJour }}</a>
        @endif
    </section>
@endif

@if ($contacts->isNotEmpty())
    <section class="bloc">
        <h2 class="bloc-titre"><span class="pastille"><i class="fa-solid fa-headset" aria-hidden="true"></i></span> Une question ?</h2>

        <ul class="fiches">
            @foreach ($contacts as $contact)
                <li class="fiche">
                    <span class="fiche-icone"><i class="fa-solid fa-user" aria-hidden="true"></i></span>
                    <div class="fiche-titre">
                        {{ $contact->nom }}
                        @if ($contact->role) <small>· {{ $contact->role }}</small> @endif
                    </div>
                    <div class="fiche-actions">
                        @if ($contact->telephone)
                            <a href="{{ $tel($contact->telephone) }}" class="bouton bouton--contour bouton--petit">
                                <i class="fa-solid fa-phone" aria-hidden="true"></i> Appeler
                            </a>
                        @endif
                        @if ($contact->whatsapp)
                            <a href="https://wa.me/{{ $contact->whatsapp }}" target="_blank" rel="noopener" class="bouton bouton--whatsapp bouton--petit">
                                <i class="fa-brands fa-whatsapp" aria-hidden="true"></i> WhatsApp
                            </a>
                        @endif
                        @if ($contact->email)
                            <a href="mailto:{{ $contact->email }}" class="bouton bouton--contour bouton--petit">
                                <i class="fa-solid fa-envelope" aria-hidden="true"></i> E-mail
                            </a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
@endif

@endsection
