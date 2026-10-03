{{-- Qui joue ? Le nom saisi une fois est retenu pour les jeux suivants. --}}
@php $joueur = $joueur ?? []; @endphp

@if (! empty($joueur['prenom']))
    <div class="joueur-connu">
        <span><i class="fa-solid fa-user" aria-hidden="true"></i> Vous jouez en tant que <strong>{{ $joueur['prenom'] }} {{ $joueur['nom'] }}</strong></span>
        <button type="button" class="lien-discret" onclick="this.closest('.joueur-connu').hidden = true; this.closest('form').querySelector('.joueur-champs').hidden = false">Changer</button>
    </div>
@endif

<div class="grille-2 joueur-champs" @if (! empty($joueur['prenom'])) hidden @endif>
    <label class="champ">
        <span>Prénom</span>
        <input class="saisie" name="prenom" value="{{ old('prenom', $joueur['prenom'] ?? '') }}" autocomplete="given-name" required>
    </label>
    <label class="champ">
        {{-- Le classement réunit les scores par prénom + nom : l'initiale
             suffit à ne pas mélanger deux Marie. --}}
        <span>Nom <small>(ou initiale)</small></span>
        <input class="saisie" name="nom" value="{{ old('nom', $joueur['nom'] ?? '') }}" autocomplete="family-name" required>
    </label>
</div>

@if ($errors->any())
    <p class="champ-erreur" style="margin-top:0">{{ $errors->first() }}</p>
@endif
