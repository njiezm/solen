@extends('espace.layout')

@section('titre', 'Invités et RSVP')
@section('chapeau', 'Votre liste d’invités et leurs réponses. Chaque foyer répond depuis son lien personnel, sans compte ni mot de passe.')

@section('contenu')

@php use App\Models\Invite; @endphp

<div class="tuiles">
    <div class="tuile"><div class="nombre">{{ $chiffres['presents'] }}</div><div class="quoi">personnes présentes</div></div>
    <div class="tuile"><div class="nombre">{{ $chiffres['attente'] }}</div><div class="quoi">foyers sans réponse</div></div>
    <div class="tuile"><div class="nombre">{{ $chiffres['absents'] }}</div><div class="quoi">absents</div></div>
    <div class="tuile"><div class="nombre">{{ $chiffres['foyers'] }}</div><div class="quoi">foyers · {{ $chiffres['places'] }} places</div></div>
</div>

@if ($chiffres['par_moment']->filter()->isNotEmpty())
    <div class="carte">
        <h2>Par moment de la journée</h2>
        <div class="liste">
            @foreach ($chiffres['par_moment'] as $nom => $n)
                <div class="ligne"><div class="corps"><strong>{{ $nom }}</strong></div><div class="outils"><strong>{{ $n }}</strong> personne{{ $n > 1 ? 's' : '' }}</div></div>
            @endforeach
        </div>
    </div>
@endif

<div class="carte">
    <h2>Le lien de réponse</h2>
    <p class="carte-aide">
        Le plus simple : envoyez à chaque foyer son lien personnel, avec le bouton WhatsApp de la liste ci-dessous.
        Le lien général, à mettre sur le faire-part, demande le code de l’invitation :
    </p>
    <div class="ligne">
        <span class="icone"><i class="fa-solid fa-link" aria-hidden="true"></i></span>
        <div class="corps"><strong id="lien-rsvp">{{ route('rsvp') }}</strong></div>
        <div class="outils"><button class="mini" data-copier="lien-rsvp">Copier</button>
            <a href="{{ route('espace.modules.editer', 'rsvp') }}" class="mini">Réglages</a></div>
    </div>
</div>

{{-- ──────────────────────────────────────────────────── Ajouter ── --}}
<details class="ajout" @if ($errors->any() || $invites->isEmpty()) open @endif>
    <summary><i class="fa-solid fa-plus" aria-hidden="true"></i> Ajouter des invités</summary>
    <div class="ajout-corps">
        <form method="POST" action="{{ route('espace.invites.ajouter') }}">
            @csrf
            <div class="grille-champs">
                <div class="champ"><label for="i-nom">Foyer</label><input type="text" id="i-nom" name="nom" required placeholder="Famille Dupont, ou Léa Martin"></div>
                <div class="champ"><label for="i-places">Places</label><input type="number" id="i-places" name="places" value="2" min="1" max="20" required></div>
                <div class="champ"><label for="i-tel">WhatsApp</label><input type="tel" id="i-tel" name="telephone" placeholder="0696 12 34 56"></div>
                <div class="champ"><label for="i-email">E-mail</label><input type="email" id="i-email" name="email"></div>
                <div class="champ"><label for="i-groupe">Groupe</label><input type="text" id="i-groupe" name="groupe" list="groupes" placeholder="Famille de la mariée"></div>
            </div>
            <div class="actions"><button class="btn btn-primary">Ajouter</button></div>
        </form>

        <form method="POST" action="{{ route('espace.invites.importer') }}" style="margin-top:1.4rem; border-top:1px solid var(--line); padding-top:1.2rem">
            @csrf
            <div class="champ">
                <label for="liste-collee">Ou collez toute votre liste</label>
                <textarea id="liste-collee" name="liste" rows="6" spellcheck="false" placeholder="Famille Dupont ; 4 ; 0696 12 34 56 ; dupont@exemple.fr ; Famille de la mariée&#10;Léa Martin ; 1 ; 0690 11 22 33&#10;Hugo et Inès ; 2">{{ old('liste') }}</textarea>
                <p class="champ-aide">Une ligne par foyer : nom, puis dans l’ordre que vous voulez le nombre de places, le téléphone, l’e-mail et le groupe. Un copier-coller depuis Excel ou Google Sheets fonctionne.</p>
            </div>
            <div class="actions"><button class="btn btn-primary">Importer la liste</button></div>
        </form>
    </div>
</details>
<datalist id="groupes">@foreach ($groupes as $g)<option value="{{ $g }}">@endforeach</datalist>

{{-- ────────────────────────────────────────────────────── Liste ── --}}
<div class="carte" id="liste">
    <div class="actions" style="margin:0 0 1rem; justify-content:space-between; flex-wrap:wrap">
        <span style="display:flex; gap:.4rem; flex-wrap:wrap">
            <a href="{{ route('espace.invites') }}" class="mini {{ ! $filtre ? 'choisi' : '' }}">Tous</a>
            @foreach (Invite::REPONSES as $cle => $libelle)
                <a href="{{ route('espace.invites', ['filtre' => $cle]) }}#liste" class="mini {{ $filtre === $cle ? 'choisi' : '' }}">{{ $libelle }}</a>
            @endforeach
        </span>
        <a href="{{ route('espace.invites.exporter') }}" class="mini"><i class="fa-solid fa-file-csv" aria-hidden="true"></i> Exporter pour le traiteur</a>
    </div>

    @if ($invites->isEmpty())
        <div class="vide-illustre"><i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i><p>Aucun invité{{ $filtre ? ' dans cette catégorie' : ' pour l’instant' }}.</p></div>
    @else
        <div class="liste">
            @foreach ($invites as $invite)
                <details class="depliant" id="invite-{{ $invite->id }}">
                    <summary>
                        <span class="icone"><i class="fa-solid {{ ['oui' => 'fa-check', 'non' => 'fa-xmark', 'attente' => 'fa-hourglass-half'][$invite->reponse] }}" aria-hidden="true"></i></span>
                        <span class="titre-ligne">
                            <strong>{{ $invite->nom }}</strong>
                            <span>
                                @if ($invite->reponse === 'oui') {{ $invite->presents }}/{{ $invite->places }} présent{{ $invite->presents > 1 ? 's' : '' }}
                                @elseif ($invite->reponse === 'non') Ne viendra pas
                                @else {{ $invite->places }} place{{ $invite->places > 1 ? 's' : '' }} · sans réponse @if ($invite->relance_le)· lien envoyé le {{ $invite->relance_le->translatedFormat('j M') }}@endif
                                @endif
                                @if ($invite->groupe) · {{ $invite->groupe }} @endif
                                @if ($invite->regimes) · <i class="fa-solid fa-wheat-awn-circle-exclamation" aria-hidden="true"></i> {{ \Illuminate\Support\Str::limit($invite->regimes, 40) }} @endif
                            </span>
                        </span>
                        <span class="outils" onclick="event.stopPropagation()">
                            @if ($invite->telephone)
                                <a href="{{ route('espace.invites.whatsapp', $invite->id) }}" target="_blank" rel="noopener" class="mini" style="color:#1A7F45" title="Envoyer son lien sur WhatsApp"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a>
                            @endif
                            <button type="button" class="mini" data-copier-texte="{{ $invite->lien() }}" title="Copier son lien">Lien</button>
                        </span>
                    </summary>
                    <div class="depliant-corps">
                        @if ($invite->aRepondu())
                            <p class="carte-aide">
                                Répondu le {{ $invite->repondu_le?->translatedFormat('j F à H\hi') }}{{ $invite->source === 'libre' ? ' (réponse spontanée)' : '' }}.
                                @if ($invite->noms_presents) Prénoms : {{ implode(', ', $invite->noms_presents) }}. @endif
                                @if ($invite->moments) Moments : {{ $moments->whereIn('cle', $invite->moments)->pluck('nom')->implode(', ') }}. @endif
                                @if ($invite->message) « {{ $invite->message }} » @endif
                            </p>
                        @endif
                        <form method="POST" action="{{ route('espace.invites.maj', $invite->id) }}">
                            @csrf @method('PUT')
                            <div class="grille-champs">
                                <div class="champ"><label>Foyer</label><input type="text" name="nom" value="{{ $invite->nom }}" required></div>
                                <div class="champ"><label>Places</label><input type="number" name="places" value="{{ $invite->places }}" min="1" max="20" required></div>
                                <div class="champ"><label>WhatsApp</label><input type="tel" name="telephone" value="{{ $invite->telephone }}"></div>
                                <div class="champ"><label>E-mail</label><input type="email" name="email" value="{{ $invite->email }}"></div>
                                <div class="champ"><label>Groupe</label><input type="text" name="groupe" value="{{ $invite->groupe }}" list="groupes"></div>
                                <div class="champ"><label>Réponse reçue de vive voix</label>
                                    <select name="reponse"><option value="">Ne pas changer</option>@foreach (Invite::REPONSES as $c => $l)<option value="{{ $c }}">{{ $l }}</option>@endforeach</select></div>
                            </div>
                            <div class="actions" style="justify-content:space-between">
                                <button class="btn btn-primary">Enregistrer</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('espace.invites.supprimer', $invite->id) }}" onsubmit="return confirm('Retirer {{ addslashes($invite->nom) }} de la liste ?')">
                            @csrf @method('DELETE')
                            <button class="mini mini--danger">Retirer de la liste</button>
                        </form>
                    </div>
                </details>
            @endforeach
        </div>
    @endif
</div>

@if ($regimes->isNotEmpty())
    <div class="carte">
        <h2>Allergies et régimes, pour le traiteur</h2>
        <div class="liste">
            @foreach ($regimes as $r)
                <div class="ligne"><div class="corps"><strong>{{ $r->nom }}</strong><span style="white-space:normal">{{ $r->regimes }}</span></div></div>
            @endforeach
        </div>
    </div>
@endif

<script>
    document.querySelectorAll('[data-copier]').forEach((b) => b.addEventListener('click', async () => {
        await navigator.clipboard.writeText(document.getElementById(b.dataset.copier).textContent.trim());
        const t = b.textContent; b.textContent = 'Copié'; setTimeout(() => (b.textContent = t), 1600);
    }));
    document.querySelectorAll('[data-copier-texte]').forEach((b) => b.addEventListener('click', async (e) => {
        e.preventDefault();
        await navigator.clipboard.writeText(b.dataset.copierTexte);
        const t = b.textContent; b.textContent = 'Copié'; setTimeout(() => (b.textContent = t), 1600);
    }));
</script>

@endsection
