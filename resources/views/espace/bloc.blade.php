@extends('espace.layout')

@section('titre', $bloc ? $bloc->etiquette() : 'Nouveau : ' . $definition['nom'])
@section('chapeau', $page['nom'])
@section('retour', route('espace.page', $cle))
@section('retour-libelle', $page['nom'])

@section('contenu')

{{-- Formulaire entièrement déduit du type de bloc. --}}
<form method="POST"
      action="{{ $bloc
                  ? route('espace.bloc.maj', $bloc->id)
                  : route('espace.bloc.stocker', [$cle, $type]) }}"
      @if (app(App\Solen\Champs::class)->contientUnFichier($definition['champs'])) enctype="multipart/form-data" @endif>
    @csrf

    <div class="carte">
        <h2>{{ $definition['nom'] }}</h2>

        <div class="grille-champs">
            @foreach ($definition['champs'] as $champ)
                <x-champ :champ="$champ" :valeur="$valeurs[$champ['cle']] ?? null" />
            @endforeach
        </div>
    </div>

    @if ($bloc)
        <div class="carte">
            <x-champ :champ="['cle' => 'actif', 'type' => 'booleen', 'label' => 'Afficher cet élément sur le site']"
                     :valeur="$bloc->actif" prefixe="" />
        </div>
    @endif

    <div class="actions">
        <button type="submit" class="btn btn-primary">
            {{ $bloc ? 'Enregistrer' : 'Ajouter' }}
        </button>
        <a href="{{ route('espace.page', $cle) }}" class="btn btn-ghost">Annuler</a>
    </div>
</form>

@endsection
