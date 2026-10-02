@extends('espace.layout')

@section('titre', 'Programme')
@section('chapeau', 'Les moments de votre journée, leurs lieux, leurs horaires et leur déroulé. Tout ce que vos invités verront sur le site.')

@section('contenu')

@if ($parts->isEmpty())
    <div class="vide-illustre">
        <i class="fa-solid fa-timeline" aria-hidden="true"></i>
        <p>Aucun moment pour l’instant. Ajoutez la mairie, la cérémonie, le vin d’honneur…</p>
    </div>
@endif

@foreach ($parts as $part)
    <details class="depliant carte-moment" id="moment-{{ $part->id }}" @if ($loop->first && $parts->count() === 1) open @endif>
        <summary>
            <span class="icone"><i class="fa-solid {{ $part->icone ?: 'fa-calendar-day' }}" aria-hidden="true"></i></span>
            <span class="titre-ligne">
                <strong>{{ $part->nom }}</strong>
                <span>
                    @if ($part->debut_at) {{ $part->debut_at->setTimezone($event->timezone)->translatedFormat('l j F, H\hi') }} @else Horaire à préciser @endif
                    · {{ $part->lieu_nom ?: 'Lieu à préciser' }}
                    · {{ $part->etapes->count() }} étape{{ $part->etapes->count() > 1 ? 's' : '' }}
                </span>
            </span>
            <span class="chip {{ $part->actif ? 'chip-live' : 'chip-next' }}" style="position:static">{{ $part->actif ? 'affiché' : 'masqué' }}</span>
        </summary>

        <div class="depliant-corps">
            <form method="POST" action="{{ route('espace.programme.moment.maj', $part->id) }}">
                @csrf
                <div class="grille-champs">
                    <div class="champ">
                        <label for="nom-{{ $part->id }}">Nom du moment</label>
                        <input type="text" id="nom-{{ $part->id }}" name="nom" value="{{ old('nom', $part->nom) }}" required>
                    </div>
                    @if ($part->type_ceremonie)
                        <div class="champ">
                            <label for="culte-{{ $part->id }}">Type de cérémonie</label>
                            <select id="culte-{{ $part->id }}" name="type_ceremonie">
                                @foreach ($cultes as $cle => $libelle)
                                    <option value="{{ $cle }}" @selected($part->type_ceremonie === $cle)>{{ $libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="champ">
                        <label for="lieu-{{ $part->id }}">Lieu</label>
                        <input type="text" id="lieu-{{ $part->id }}" name="lieu_nom" value="{{ $part->lieu_nom }}" placeholder="Domaine des Pins">
                    </div>
                    <div class="champ">
                        <label for="adresse-{{ $part->id }}">Adresse</label>
                        <input type="text" id="adresse-{{ $part->id }}" name="lieu_adresse" value="{{ $part->lieu_adresse }}" placeholder="12 route du Lac, 97232 Le Lamentin">
                        <p class="champ-aide">Sert à l’itinéraire de vos invités.</p>
                    </div>
                    <div class="champ">
                        <label for="accueil-{{ $part->id }}">Accueil des invités</label>
                        <input type="datetime-local" id="accueil-{{ $part->id }}" name="accueil_at" value="{{ $local($part->accueil_at) }}" min="{{ $jour ? $jour . 'T00:00' : '' }}">
                    </div>
                    <div class="champ">
                        <label for="debut-{{ $part->id }}">Début</label>
                        <input type="datetime-local" id="debut-{{ $part->id }}" name="debut_at" value="{{ $local($part->debut_at) }}">
                        <p class="champ-aide">Heure de {{ $event->timezone === 'Europe/Paris' ? 'Paris' : str_replace(['America/', 'Indian/', '_'], ['', '', ' '], $event->timezone) }}. Alimente le compte à rebours.</p>
                    </div>
                    <div class="champ" style="grid-column:1 / -1">
                        <label for="desc-{{ $part->id }}">Un mot pour vos invités <span class="champ-aide" style="display:inline">(facultatif)</span></label>
                        <textarea id="desc-{{ $part->id }}" name="description" rows="2">{{ $part->description }}</textarea>
                    </div>
                </div>
                <label class="champ-bascule" style="margin-top:.8rem">
                    <input type="checkbox" name="actif" value="1" @checked($part->actif)> Afficher ce moment sur le site
                </label>
                <div class="actions"><button class="btn btn-primary">Enregistrer</button></div>
            </form>

            <h3 style="font-size:.95rem; margin:1.6rem 0 .7rem">Le déroulé</h3>
            <div class="liste">
                @forelse ($part->etapes as $etape)
                    <div class="ligne">
                        <span class="icone">{{ $loop->iteration }}</span>
                        <form method="POST" action="{{ route('espace.programme.etape.maj', $etape->id) }}" class="corps etape-edition">
                            @csrf
                            <input type="text" name="titre" value="{{ $etape->titre }}" required aria-label="Titre de l’étape">
                            <input type="text" name="description" value="{{ $etape->description }}" placeholder="Précision (facultatif)" aria-label="Précision">
                            <button class="mini" title="Enregistrer"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
                        </form>
                        <div class="outils">
                            @unless ($loop->first)
                                <form method="POST" action="{{ route('espace.programme.etape.deplacer', [$etape->id, 'monter']) }}">@csrf<button class="mini" title="Monter"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button></form>
                            @endunless
                            @unless ($loop->last)
                                <form method="POST" action="{{ route('espace.programme.etape.deplacer', [$etape->id, 'descendre']) }}">@csrf<button class="mini" title="Descendre"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button></form>
                            @endunless
                            <form method="POST" action="{{ route('espace.programme.etape.supprimer', $etape->id) }}" onsubmit="return confirm('Supprimer cette étape ?')">
                                @csrf @method('DELETE')
                                <button class="mini mini--danger" title="Supprimer"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="carte-aide" style="margin:0">Aucune étape. Le déroulé est facultatif.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('espace.programme.etape.ajouter', $part->id) }}" class="ajout-ligne">
                @csrf
                <input type="text" name="titre" placeholder="Ajouter une étape : Entrée des mariés" required maxlength="160">
                <button class="btn btn-primary">Ajouter</button>
            </form>

            <div class="actions" style="justify-content:space-between; margin-top:1.6rem; border-top:1px solid var(--line); padding-top:1rem">
                <span style="display:flex; gap:.4rem">
                    @unless ($loop->first)
                        <form method="POST" action="{{ route('espace.programme.moment.deplacer', [$part->id, 'monter']) }}">@csrf<button class="mini"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i> Plus tôt</button></form>
                    @endunless
                    @unless ($loop->last)
                        <form method="POST" action="{{ route('espace.programme.moment.deplacer', [$part->id, 'descendre']) }}">@csrf<button class="mini"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i> Plus tard</button></form>
                    @endunless
                </span>
                <form method="POST" action="{{ route('espace.programme.moment.supprimer', $part->id) }}"
                      onsubmit="return confirm('Retirer « {{ $part->nom }} » et son déroulé du programme ?')">
                    @csrf @method('DELETE')
                    <button class="mini mini--danger">Retirer ce moment</button>
                </form>
            </div>
        </div>
    </details>
@endforeach

<div class="carte" style="margin-top:1.2rem">
    <h2>Ajouter un moment</h2>
    <form method="POST" action="{{ route('espace.programme.moment.ajouter') }}" class="ajout-ligne" id="ajout-moment">
        @csrf
        <select name="cle" onchange="this.form.querySelector('[name=nom]').hidden = this.value !== 'autre'" style="flex:1 1 200px; min-height:42px; padding:.5rem .7rem; border:1px solid var(--line); border-radius:10px; background:var(--surface-card)">
            @foreach ($catalogue as $cle => $definition)
                <option value="{{ $cle }}">{{ $definition['nom'] }}</option>
            @endforeach
            <option value="autre">Autre moment…</option>
        </select>
        <input type="text" name="nom" placeholder="Nom du moment : Feu d’artifice" @if ($catalogue->isNotEmpty()) hidden @endif maxlength="80">
        <button class="btn btn-primary">Ajouter</button>
    </form>
</div>

@endsection
