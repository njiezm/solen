@extends('console.layout')

@section('titre', 'Codes promo')
@section('chapeau', 'Utilisables sur les devis et factures de la console, et par vos clients à la commande en ligne.')
@section('retour', route('console.facturation'))
@section('retour-libelle', 'Facturation')

@section('contenu')

@php
    $affiche = fn ($c) => $c->type === 'pourcentage'
        ? rtrim(rtrim(number_format((float) $c->valeur, 2, ',', ''), '0'), ',') . ' %'
        : \App\Models\DocumentCommercial::euros((int) round($c->valeur * 100));
@endphp

<details class="ajout" @if ($errors->any() || $codes->isEmpty()) open @endif>
    <summary><i class="fa-solid fa-plus" aria-hidden="true"></i> Créer un code</summary>
    <div class="ajout-corps">
        <form method="POST" action="{{ route('console.facturation.codes.enregistrer') }}">
            @csrf
            <div class="grille-champs">
                <div class="champ">
                    <label for="code">Code</label>
                    <input type="text" id="code" name="code" value="{{ old('code') }}" required placeholder="MARIAGE2027" style="text-transform:uppercase">
                    @error('code') <p class="champ-erreur">{{ $message }}</p> @enderror
                </div>
                <div class="champ">
                    <label for="libelle">Libellé</label>
                    <input type="text" id="libelle" name="libelle" value="{{ old('libelle') }}" placeholder="Salon du mariage de Fort-de-France">
                </div>
                <div class="champ">
                    <label for="type">Type</label>
                    <select id="type" name="type">
                        <option value="pourcentage">Pourcentage</option>
                        <option value="montant" @selected(old('type') === 'montant')>Montant en euros</option>
                    </select>
                </div>
                <div class="champ">
                    <label for="valeur">Valeur</label>
                    <input type="number" id="valeur" name="valeur" value="{{ old('valeur', 10) }}" step="0.01" min="0.01" required>
                    @error('valeur') <p class="champ-erreur">{{ $message }}</p> @enderror
                </div>
                <div class="champ">
                    <label for="debut">Valable du</label>
                    <input type="date" id="debut" name="debut_le" value="{{ old('debut_le') }}">
                </div>
                <div class="champ">
                    <label for="fin">au</label>
                    <input type="date" id="fin" name="fin_le" value="{{ old('fin_le') }}">
                    @error('fin_le') <p class="champ-erreur">{{ $message }}</p> @enderror
                </div>
                <div class="champ">
                    <label for="max">Utilisations maximum</label>
                    <input type="number" id="max" name="utilisations_max" value="{{ old('utilisations_max') }}" min="1" placeholder="Illimité">
                </div>
                <div class="champ">
                    <label>Formules concernées</label>
                    <div class="champ-cases">
                        @foreach ($formules as $cle => $nom)
                            <label class="champ-bascule"><input type="checkbox" name="formules[]" value="{{ $cle }}"> {{ $nom }}</label>
                        @endforeach
                        <p class="champ-aide" style="margin:0">Rien de coché : toutes les formules.</p>
                    </div>
                </div>
            </div>
            <div class="actions"><button class="btn btn-primary">Créer le code</button></div>
        </form>
    </div>
</details>

<div class="liste">
    @forelse ($codes as $code)
        <div class="ligne @unless ($code->actif) inactive @endunless">
            <span class="icone"><i class="fa-solid fa-ticket" aria-hidden="true"></i></span>
            <div class="corps">
                <strong>{{ $code->code }} · −{{ $affiche($code) }}</strong>
                <span>
                    {{ $code->libelle }}
                    · {{ $code->formules ? collect($code->formules)->map(fn ($f) => $formules[$f] ?? $f)->implode(', ') : 'toutes formules' }}
                    @if ($code->debut_le || $code->fin_le) · {{ $code->debut_le?->format('d/m/Y') ?? '…' }} → {{ $code->fin_le?->format('d/m/Y') ?? '…' }} @endif
                    · {{ $code->utilisations }}{{ $code->utilisations_max ? ' / ' . $code->utilisations_max : '' }} utilisation{{ $code->utilisations > 1 ? 's' : '' }}
                    @if ($refus = $code->refus()) · <em>{{ $refus }}</em> @endif
                </span>
            </div>
            <div class="outils">
                <form method="POST" action="{{ route('console.facturation.codes.basculer', $code->id) }}">
                    @csrf <button class="mini">{{ $code->actif ? 'Désactiver' : 'Réactiver' }}</button>
                </form>
            </div>
        </div>
    @empty
        <p class="carte-aide">Aucun code promo.</p>
    @endforelse
</div>

@endsection
