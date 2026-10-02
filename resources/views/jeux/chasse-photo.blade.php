@extends('layout')
@section('titre', 'Chasse photo')
@section('largeur', 'cadre--etroit')
@section('content')

{{-- Les missions viennent des réglages du module « jeux » : aucun prénom,
     aucun lieu n'est écrit ici. --}}

<header class="page-tete">
    <p class="page-tete-sur">Jeu</p>
    <h1>Chasse photo</h1>
    <p>Immortalisez chaque mission, et envoyez votre photo preuve.</p>
</header>

@if (session('success'))
    <div class="message message--succes" role="status">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>{{ session('success') }}</span>
    </div>
@endif

<section class="bloc">
    <h2 class="bloc-titre"><span class="pastille"><i class="fa-solid fa-list-check" aria-hidden="true"></i></span> Les missions</h2>
    <ol class="fiches">
        @foreach ($missions as $mission)
            <li class="fiche">
                <span class="fiche-icone">{{ $loop->iteration }}</span>
                <div class="fiche-titre" style="font-weight:500">{{ $mission }}</div>
            </li>
        @endforeach
    </ol>
</section>

<form method="POST" action="{{ route('jeux.submitChassePhoto') }}" enctype="multipart/form-data" class="bloc">
    @csrf

    <h2 class="bloc-titre"><span class="pastille"><i class="fa-solid fa-camera" aria-hidden="true"></i></span> Envoyer une photo</h2>

    @include('jeux.partials.joueur')

    <label class="champ">
        <span>Mission</span>
        <select class="saisie" name="indice" required>
            <option value="" selected disabled>Choisissez une mission…</option>
            @foreach ($missions as $mission)
                <option value="{{ $mission }}" @selected(old('indice') === $mission)>{{ $loop->iteration }}. {{ $mission }}</option>
            @endforeach
        </select>
    </label>

    <label class="champ">
        <span>Votre photo</span>
        <input type="file" class="saisie" name="photo" accept="image/*" required>
    </label>

    <button type="submit" class="bouton bouton--plein bouton--large">
        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Envoyer
    </button>
    <p class="doux" style="margin:.8rem 0 0; text-align:center; font-size:.85rem">
        Les mariés valident chaque photo. La plus belle collection gagne !
    </p>
</form>

@endsection
