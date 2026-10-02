@extends('layout')
@section('titre', 'Merci')
@section('largeur', 'cadre--etroit')
@section('content')

<section class="bloc" style="text-align:center; padding-block:3rem">
    <span class="pastille" style="width:64px; height:64px; font-size:1.6rem; margin-bottom:1rem">
        <i class="fa-solid fa-heart" aria-hidden="true"></i>
    </span>
    <h1 style="margin:0 0 .6rem; font-size:2.2rem">Merci infiniment</h1>
    <p class="doux" style="margin:0 auto 1.6rem; max-width:420px">
        @if ($montant)
            Votre participation de <strong>{{ number_format((float) $montant, 0, ',', ' ') }} €</strong> a bien été reçue.
        @else
            Votre participation a bien été prise en compte.
        @endif
        Les mariés en seront touchés.
    </p>
    <a href="{{ route('home') }}" class="bouton bouton--plein">Retour au site</a>
</section>

@endsection
