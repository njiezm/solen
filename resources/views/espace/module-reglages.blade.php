@extends('espace.layout')

@section('titre', $module->nom)
@section('chapeau', $module->description)
@section('retour', route('espace.modules'))
@section('retour-libelle', 'Tous les modules')

@section('contenu')

{{--
    Ce formulaire n'existe pour aucun module en particulier : il est déduit
    de la déclaration des champs. Ajouter un réglage dans
    config/solen_schema.php suffit à le voir apparaître ici.
--}}
<form method="POST"
      action="{{ route('espace.modules.enregistrer', $module->cle) }}"
      @if (app(App\Solen\Champs::class)->contientUnFichier($module->champs)) enctype="multipart/form-data" @endif>
    @csrf

    <div class="carte">
        <h2>Réglages</h2>
        <p class="carte-aide">Ces choix s’appliquent immédiatement au site de vos invités.</p>

        <div class="grille-champs">
            @foreach ($module->champs as $champ)
                <x-champ :champ="$champ" :valeur="$valeurs[$champ['cle']] ?? null" prefixe="reglages" />
            @endforeach
        </div>
    </div>

    <div class="actions">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="{{ route('espace.modules') }}" class="btn btn-ghost">Retour aux modules</a>
    </div>
</form>

@endsection
