@extends('layout')
@section('largeur', 'cadre--etroit')
@section('content')

<section class="bloc" style="text-align:center; padding-block:3rem">
    <span class="pastille" style="width:64px; height:64px; font-size:1.6rem; margin-bottom:1rem">
        <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
    </span>
    <h1 style="margin:0 0 .6rem; font-size:2rem">Le jeu n’a pas encore commencé</h1>
    <p class="doux" style="margin:0 auto 1.6rem; max-width:420px">{{ $message }}</p>
    <a href="{{ route('home') }}" class="bouton bouton--plein">Retour à l’accueil</a>
</section>

@endsection
