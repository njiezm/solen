@extends('layout')
@section('titre', 'Paiement annulé')
@section('largeur', 'cadre--etroit')
@section('content')

<section class="bloc" style="text-align:center; padding-block:3rem">
    <h1 style="margin:0 0 .6rem; font-size:2rem">Paiement annulé</h1>
    <p class="doux" style="margin:0 0 1.6rem">Aucun montant n’a été prélevé. Vous pouvez réessayer quand vous le souhaitez.</p>
    <a href="{{ route('urne.index') }}" class="bouton bouton--plein">Revenir à la cagnotte</a>
</section>

@endsection
