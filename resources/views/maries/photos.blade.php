@extends('espace.layout')

@section('titre', 'Photos de la chasse')
@section('retour', route('maries.galerie'))
@section('retour-libelle', 'Photos')
@section('chapeau', 'Les clichés envoyés par vos invités pendant le jeu de la chasse photo.')

@section('contenu')

@if ($photos->isEmpty())
    <p class="vide">Aucune photo pour l’instant.</p>
@else
    <div class="carte">
        <h2>{{ $photos->count() }} photo{{ $photos->count() > 1 ? 's' : '' }}</h2>
        <p class="carte-aide">Les photos en attente sont visibles de vous seuls tant qu’elles ne sont pas validées.</p>

        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:1rem">
            @foreach ($photos as $photo)
                <figure style="margin:0">
                    <img src="{{ Storage::url($photo->photo_path) }}"
                         alt="{{ $photo->indice }}"
                         loading="lazy"
                         style="width:100%; aspect-ratio:1; object-fit:cover; border-radius:var(--r-sm); border:1px solid var(--line)">

                    <figcaption style="font-size:.8rem; color:var(--ink-mute); margin-top:.45rem">
                        <strong style="color:var(--ink)">{{ $photo->indice }}</strong><br>
                        {{ $photo->participant?->prenom }} {{ $photo->participant?->nom }}<br>
                        <span class="chip {{ $photo->valide ? 'chip-live' : 'chip-next' }}">
                            {{ $photo->valide ? 'Validée' : 'En attente' }}
                        </span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
@endif

@endsection
