@extends('espace.layout')

@section('titre', 'Galerie')
@section('chapeau', 'Toutes les photos partagées par vos invités.')
@section('retour', route('maries.index'))
@section('retour-libelle', 'Contributions')

@section('contenu')

@if ($photos->isEmpty())
    <div class="vide-illustre">
        <i class="fa-solid fa-images" aria-hidden="true"></i>
        <p>Aucune photo pour l’instant.</p>
    </div>
@else
    <p class="carte-aide" style="margin-bottom:.9rem">
        {{ $photos->count() }} photo{{ $photos->count() > 1 ? 's' : '' }}
    </p>

    <div class="vignettes">
        @foreach ($photos as $photo)
            <div class="vignette">
                <a href="{{ Storage::url($photo->path) }}" target="_blank" rel="noopener">
                    <img src="{{ Storage::url($photo->path) }}" alt="" loading="lazy">
                </a>
                <div class="vignette-pied">
                    <strong>{{ $photo->participant?->prenom ?: 'Anonyme' }} {{ $photo->participant?->nom }}</strong>
                    <span>
                        {{ $photo->created_at?->translatedFormat('j F, H\hi') }}
                        @unless ($photo->publie) · <strong>en attente</strong> @endunless
                    </span>
                    <div style="display:flex; gap:.4rem; margin-top:.5rem; flex-wrap:wrap">
                        @unless ($photo->publie)
                            <form method="POST" action="{{ route('maries.galerie.publier', $photo->id) }}">
                                @csrf
                                <button class="mini">Publier</button>
                            </form>
                        @endunless
                        <form method="POST" action="{{ route('maries.galerie.supprimer', $photo->id) }}"
                              onsubmit="return confirm('Retirer cette photo de la galerie ?')">
                            @csrf @method('DELETE')
                            <button class="mini">Retirer</button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection
