@extends('layout')
@section('titre', 'Répondre à l’invitation')
@section('largeur', 'cadre--etroit')
@section('content')

<header class="page-tete">
    <p class="page-tete-sur">Votre réponse</p>
    <h1>Serez-vous des nôtres ?</h1>
    @if ($dateLimite && $dateLimite->isFuture())
        <p>Merci de répondre avant le <strong>{{ $dateLimite->translatedFormat('j F Y') }}</strong>.</p>
    @endif
</header>

@if (session('merci') && $invite)
    <div class="message message--succes" role="status">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        <span>
            {{ $merciTexte }}
            @if ($invite->reponse === 'oui') Nous avons hâte de vous retrouver{{ $invite->presents > 1 ? ', tous les ' . $invite->presents : '' }} ! @endif
            Vous pouvez modifier votre réponse à tout moment depuis ce lien.
        </span>
    </div>
@endif

@if (! $invite)
    {{-- Sans lien personnel : le code de l'invitation, ou une réponse libre. --}}
    <form method="POST" action="{{ route('rsvp.code') }}" class="bloc">
        @csrf
        <h2 class="bloc-titre"><span class="pastille"><i class="fa-solid fa-key" aria-hidden="true"></i></span> Votre code d’invitation</h2>
        <p class="doux" style="margin-top:-.4rem">Il figure sur votre faire-part ou dans le message que vous avez reçu.</p>
        <div class="saisie-groupe">
            <input name="code" class="saisie" value="{{ old('code') }}" autocapitalize="characters" autocomplete="off" required placeholder="Ex. 7KQ4MTZA" style="text-transform:uppercase; letter-spacing:.12em">
            <button class="bouton bouton--plein" style="border-radius:0 min(var(--r-bouton),12px) min(var(--r-bouton),12px) 0">Continuer</button>
        </div>
        @error('code') <span class="champ-erreur">{{ $message }}</span> @enderror
    </form>

    @if ($ouvert)
        <p class="doux" style="text-align:center; margin:1.4rem 0">Pas de code ? Répondez directement :</p>
    @endif
@endif

@if ($invite || $ouvert)
    @php
        $reponse = old('reponse', $invite?->aRepondu() ? $invite->reponse : null);
        $places = $invite?->places ?? 10;
    @endphp

    <form method="POST" action="{{ $invite ? route('rsvp.repondre', $invite->code) : route('rsvp.libre') }}" class="bloc" id="rsvp">
        @csrf

        @if ($invite)
            <h2 class="bloc-titre" style="margin-bottom:.4rem">{{ $invite->nom }}</h2>
            <p class="doux" style="margin:0 0 1.2rem">
                Votre invitation compte {{ $places }} place{{ $places > 1 ? 's' : '' }}.
                @if ($invite->aRepondu()) Réponse enregistrée le {{ $invite->repondu_le?->translatedFormat('j F à H\hi') }}. @endif
            </p>
        @else
            <div class="grille-2">
                <label class="champ"><span>Vos nom et prénom</span><input name="nom" class="saisie" value="{{ old('nom') }}" required></label>
                <label class="champ"><span>Téléphone <small>(facultatif)</small></span><input name="telephone" type="tel" class="saisie" value="{{ old('telephone') }}"></label>
            </div>
            <label class="champ"><span>E-mail <small>(facultatif)</small></span><input name="email" type="email" class="saisie" value="{{ old('email') }}"></label>
        @endif

        <div class="quiz-choix" style="margin-bottom:1.2rem">
            <label><input type="radio" name="reponse" value="oui" @checked($reponse === 'oui') required><span>Oui, avec joie !</span></label>
            <label><input type="radio" name="reponse" value="non" @checked($reponse === 'non')><span>Non, hélas</span></label>
        </div>
        @error('reponse') <span class="champ-erreur">{{ $message }}</span> @enderror

        <div id="si-oui" @if ($reponse !== 'oui') hidden @endif>
            <label class="champ">
                <span>Combien serez-vous ?</span>
                <select name="presents" class="saisie" id="presents">
                    @for ($n = 1; $n <= min($places, 10); $n++)
                        <option value="{{ $n }}" @selected((int) old('presents', $invite?->presents ?: $places) === $n)>{{ $n }} personne{{ $n > 1 ? 's' : '' }}</option>
                    @endfor
                </select>
                @error('presents') <span class="champ-erreur">{{ $message }}</span> @enderror
            </label>

            <div class="champ" id="noms">
                <span>Prénoms <small>(pour vos marque-places)</small></span>
                @for ($n = 0; $n < min($places, 10); $n++)
                    <input name="noms_presents[]" class="saisie" style="margin-bottom:.4rem" data-rang="{{ $n }}"
                           value="{{ old("noms_presents.{$n}", $invite?->noms_presents[$n] ?? '') }}" placeholder="Personne {{ $n + 1 }}">
                @endfor
            </div>

            @if ($avecMoments && $moments->count() > 1)
                <div class="champ">
                    <span>Vous serez là pour…</span>
                    <div class="puces">
                        @foreach ($moments as $moment)
                            @php $coche = in_array($moment->cle, old('moments', $invite?->moments ?? $moments->pluck('cle')->all()), true); @endphp
                            <label class="puce {{ $coche ? 'actif' : '' }}" style="display:inline-flex; align-items:center; gap:.4rem">
                                <input type="checkbox" name="moments[]" value="{{ $moment->cle }}" @checked($coche) style="accent-color:var(--c-accent)">
                                {{ $moment->nom }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($avecRegimes)
                <label class="champ">
                    <span>Allergies, régimes particuliers <small>(facultatif)</small></span>
                    <textarea name="regimes" class="saisie" rows="2" placeholder="Végétarien, sans gluten, allergie aux fruits à coque…">{{ old('regimes', $invite?->regimes) }}</textarea>
                </label>
            @endif
        </div>

        <label class="champ">
            <span>Un mot pour les mariés <small>(facultatif)</small></span>
            <textarea name="message" class="saisie" rows="2">{{ old('message', $invite?->message) }}</textarea>
        </label>

        <button class="bouton bouton--plein bouton--large">
            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> {{ $invite?->aRepondu() ? 'Mettre à jour ma réponse' : 'Envoyer ma réponse' }}
        </button>
    </form>
@endif

@push('scripts')
<script>
(() => {
    const form = document.getElementById('rsvp');
    if (!form) return;
    const siOui = document.getElementById('si-oui');
    const presents = document.getElementById('presents');
    const majNoms = () => form.querySelectorAll('[data-rang]').forEach((i) => { i.hidden = +i.dataset.rang >= +(presents?.value || 0); });
    form.querySelectorAll('[name="reponse"]').forEach((r) => r.addEventListener('change', () => { siOui.hidden = r.value !== 'oui' || !r.checked; }));
    presents?.addEventListener('change', majNoms);
    form.querySelectorAll('.puce input').forEach((c) => c.addEventListener('change', () => c.closest('.puce').classList.toggle('actif', c.checked)));
    majNoms();
})();
</script>
@endpush

@endsection
