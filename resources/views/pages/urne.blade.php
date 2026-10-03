@extends('layout')
@section('titre', $titre)
@section('largeur', 'cadre--etroit')
@section('content')

<header class="page-tete">
    {{-- Sous un titre choisi par le couple (« Urne »), ce surtitre le contredirait. --}}
    @unless (app(App\Solen\NavigationInvite::class)->titreCagnotte($event))
        <p class="page-tete-sur">Liste de mariage</p>
    @endunless
    <h1>{{ $titre }}</h1>
    @if ($introduction)
        <p>{!! nl2br(e($introduction)) !!}</p>
    @endif
</header>

@if ($objectif && ! $lienExterne)
    @php $part = min(100, (int) round($collecte / max((float) $objectif, 1) * 100)); @endphp
    <section class="bloc" style="margin-bottom:1.25rem">
        <div class="jauge-chiffres">
            <span><strong>{{ number_format((float) $collecte, 0, ',', ' ') }} €</strong> réunis</span>
            <span class="doux">objectif {{ number_format((float) $objectif, 0, ',', ' ') }} €</span>
        </div>
        <div class="jauge" role="progressbar" aria-valuenow="{{ $part }}" aria-valuemin="0" aria-valuemax="100">
            <div style="width: {{ $part }}%"></div>
        </div>
    </section>
@endif

@if (session('error'))
    <div class="message message--alerte" role="alert">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

@if ($lienExterne)
    <section class="bloc" style="text-align:center">
        <p>Notre cagnotte est en ligne : un clic, et vous y êtes.</p>
        <a href="{{ $lienExterne }}" class="bouton bouton--plein" target="_blank" rel="noopener">
            <i class="fa-solid fa-gift" aria-hidden="true"></i> Participer à la cagnotte
        </a>
    </section>
@elseif (! $ouvert)
    <div class="vide">
        <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
        La cagnotte ouvrira très bientôt. Merci de votre patience.
    </div>
@else
    <form method="POST" action="{{ route('urne.payer') }}" class="bloc">
        @csrf

        <div class="grille-2">
            <label class="champ">
                <span>Prénom</span>
                <input name="prenom" class="saisie" value="{{ old('prenom') }}" autocomplete="given-name" required>
                @error('prenom') <span class="champ-erreur">{{ $message }}</span> @enderror
            </label>
            <label class="champ">
                <span>Nom</span>
                <input name="nom" class="saisie" value="{{ old('nom') }}" autocomplete="family-name" required>
                @error('nom') <span class="champ-erreur">{{ $message }}</span> @enderror
            </label>
        </div>

        <label class="champ">
            <span>E-mail <small>(pour recevoir votre reçu)</small></span>
            <input name="email" type="email" class="saisie" value="{{ old('email') }}" autocomplete="email">
            @error('email') <span class="champ-erreur">{{ $message }}</span> @enderror
        </label>

        <div class="champ">
            <span>Montant</span>
            @if ($montants)
                <div class="puces">
                    @foreach ($montants as $suggestion)
                        <button type="button" class="puce" data-montant="{{ $suggestion }}">{{ $suggestion }} €</button>
                    @endforeach
                </div>
            @endif
            <div class="saisie-groupe">
                <input id="montant" name="montant" type="number" min="1" step="1" inputmode="numeric"
                       class="saisie" value="{{ old('montant') }}" required aria-label="Montant en euros">
                <span>€</span>
            </div>
            @error('montant') <span class="champ-erreur">{{ $message }}</span> @enderror
        </div>

        <label class="champ">
            <span>Un mot pour les mariés <small>(facultatif)</small></span>
            <textarea name="message" rows="3" class="saisie">{{ old('message') }}</textarea>
            @error('message') <span class="champ-erreur">{{ $message }}</span> @enderror
        </label>

        <button type="submit" class="bouton bouton--plein bouton--large">
            <i class="fa-solid fa-lock" aria-hidden="true"></i> Participer
        </button>

        <p class="doux" style="margin:.9rem 0 0; text-align:center; font-size:.84rem">
            Paiement sécurisé par Stripe. Vos coordonnées bancaires ne transitent jamais par ce site.
        </p>
    </form>
@endif

@if ($participants->isNotEmpty())
    <section class="section">
        <div class="section-tete"><h2>Merci à</h2></div>
        <div class="bloc">
            <ul class="fiches">
                @foreach ($participants as $don)
                    <li class="fiche">
                        <span class="fiche-icone"><i class="fa-solid fa-heart" aria-hidden="true"></i></span>
                        <div class="fiche-titre">
                            {{ $don->participant?->prenom }} {{ $don->participant?->nom }}
                            @if ($afficherMontants) <small>· {{ number_format((float) $don->montant, 0, ',', ' ') }} €</small> @endif
                        </div>
                        @if ($don->message)
                            <div class="fiche-meta"><em>« {{ $don->message }} »</em></div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif

@push('scripts')
<script>
    document.querySelectorAll('[data-montant]').forEach((b) => b.addEventListener('click', () => {
        document.getElementById('montant').value = b.dataset.montant;
        document.querySelectorAll('[data-montant]').forEach((x) => x.classList.toggle('actif', x === b));
    }));
</script>
@endpush

@endsection
