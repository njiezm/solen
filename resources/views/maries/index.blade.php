@extends('espace.layout')

@section('titre', 'Ce que laissent vos invités')
@section('chapeau', 'Leurs mots, leurs photos, leurs réponses aux jeux.')
@section('retour', route('espace.index'))
@section('retour-libelle', 'Tableau de bord')

@section('contenu')

<div class="tuiles">
    <div class="tuile"><div class="nombre">{{ $messagesCount }}</div><div class="quoi">messages</div></div>
    <div class="tuile"><div class="nombre">{{ $photosCount }}</div><div class="quoi">photos</div></div>
    <div class="tuile"><div class="nombre">{{ $chassePhotosCount }}</div><div class="quoi">photos de jeu</div></div>
    <div class="tuile"><div class="nombre">{{ $participantsCount }}</div><div class="quoi">invités</div></div>
</div>

<div class="carte">
    <div class="liste">
        <div class="ligne">
            <span class="icone"><i class="fa-solid fa-feather-pointed" aria-hidden="true"></i></span>
            <div class="corps"><strong>Livre d’or</strong><span>Les mots qu’ils vous ont laissés</span></div>
            <div class="outils"><a href="{{ route('maries.livreOr') }}" class="mini">Lire</a></div>
        </div>

        <div class="ligne">
            <span class="icone"><i class="fa-solid fa-images" aria-hidden="true"></i></span>
            <div class="corps"><strong>Galerie</strong><span>Toutes les photos partagées</span></div>
            <div class="outils"><a href="{{ route('maries.galerie') }}" class="mini">Voir</a></div>
        </div>

        <div class="ligne">
            <span class="icone"><i class="fa-solid fa-camera" aria-hidden="true"></i></span>
            <div class="corps"><strong>Chasse photo</strong><span>Les clichés envoyés pendant le jeu</span></div>
            <div class="outils"><a href="{{ route('maries.photos') }}" class="mini">Voir</a></div>
        </div>

        <div class="ligne">
            <span class="icone"><i class="fa-solid fa-question" aria-hidden="true"></i></span>
            <div class="corps"><strong>Qui de nous 2 ?</strong><span>Ce qu’ils ont répondu, question par question</span></div>
            <div class="outils"><a href="{{ route('maries.reponsesQuiDeux') }}" class="mini">Voir</a></div>
        </div>

        <div class="ligne">
            <span class="icone"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i></span>
            <div class="corps"><strong>Statistiques</strong><span>L’activité de tous les jeux</span></div>
            <div class="outils"><a href="{{ route('maries.statistiques') }}" class="mini">Voir</a></div>
        </div>
    </div>
</div>

@endsection
