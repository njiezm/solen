@extends('espace.layout')

@section('titre', 'Cagnotte')
@section('retour', route('espace.index'))
@section('retour-libelle', 'Tableau de bord')
@section('chapeau', 'L’argent de votre cagnotte arrive directement chez vous : sur votre compte bancaire, ou sur la cagnotte en ligne que vous avez déjà ouverte.')

@section('contenu')

{{-- ─────────────────────── Cagnotte tenue ailleurs (Leetchi, Lydia…) ── --}}
<div class="carte" id="cagnotte-en-ligne" @if ($lienExterne) style="border-color:var(--live)" @endif>
    <h2>
        @if ($lienExterne)
            <i class="fa-solid fa-circle-check" style="color:var(--live)" aria-hidden="true"></i> Votre cagnotte en ligne est reliée
        @else
            Vous avez déjà une cagnotte en ligne ?
        @endif
    </h2>
    <p class="carte-aide">
        Leetchi, Lydia, Paypal… Collez son adresse : vos invités y seront envoyés d’un bouton,
        et le paiement par Solen est masqué. Les participations se suivent alors sur ce site-là.
    </p>

    <form method="POST" action="{{ route('espace.paiements.lien') }}">
        @csrf
        <div class="champ">
            <label for="lien_externe">Adresse de la cagnotte</label>
            <input type="url" id="lien_externe" name="lien_externe" inputmode="url"
                   value="{{ old('lien_externe', $lienExterne) }}" placeholder="https://www.leetchi.com/c/…">
            @error('lien_externe') <p class="champ-erreur">{{ $message }}</p> @enderror
        </div>
        <div class="champ">
            <label for="titre">Nom affiché aux invités</label>
            <input type="text" id="titre" name="titre" maxlength="80"
                   value="{{ old('titre', $titre) }}" placeholder="Urne, Notre voyage de noces…">
        </div>
        <div class="champ">
            <label for="texte_intro">Un mot pour vos invités <small>(facultatif)</small></label>
            <textarea id="texte_intro" name="texte_intro" rows="3" maxlength="1500"
                      placeholder="Votre présence est notre plus beau cadeau. Pour ceux qui le souhaitent, une urne est à votre disposition.">{{ old('texte_intro', $intro) }}</textarea>
        </div>
        <div class="actions">
            <button class="btn btn-primary">Enregistrer</button>
            @if ($lienExterne)
                <a href="{{ $lienExterne }}" target="_blank" rel="noopener" class="mini">Ouvrir la cagnotte</a>
            @endif
        </div>
    </form>
</div>

@unless ($lienExterne)
@if (! $configure)
    <p class="alerte alerte--erreur">
        Stripe n’est pas configuré sur cette installation. Contactez Solen.
    </p>
@elseif ($pret)
    <div class="carte" style="border-color:var(--live)">
        <h2><i class="fa-solid fa-circle-check" style="color:var(--live)" aria-hidden="true"></i> Votre cagnotte est ouverte</h2>
        <p class="carte-aide">
            Vos invités peuvent participer. Les virements arrivent sur votre compte
            selon le calendrier de Stripe, généralement sous deux jours ouvrés.
        </p>

        <div class="tuiles" style="margin-bottom:0">
            <div class="tuile">
                <div class="nombre">{{ number_format((float) $total, 0, ',', ' ') }} €</div>
                <div class="quoi">reçus</div>
            </div>
            <div class="tuile">
                <div class="nombre">{{ $dons->where('statut', 'payé')->count() }}</div>
                <div class="quoi">participations</div>
            </div>
            <div class="tuile">
                <div class="nombre">{{ $enAttente }}</div>
                <div class="quoi">en attente</div>
            </div>
            <div class="tuile">
                <div class="nombre">{{ $event->commission_bps === 0 ? '0 %' : number_format($event->commission_bps / 100, 2, ',', '') . ' %' }}</div>
                <div class="quoi">de commission Solen</div>
            </div>
        </div>
    </div>
@else
    <div class="carte" style="border-color:var(--accent)">
        <h2>Raccordez votre compte bancaire</h2>
        <p class="carte-aide">
            Stripe, notre prestataire de paiement, va vous demander une pièce
            d’identité et un IBAN. Cela prend cinq minutes et se fait une seule
            fois. Tant que ce n’est pas terminé, vos invités voient la cagnotte
            comme « bientôt disponible ».
        </p>

        <div class="actions" style="margin-top:0; border:0; padding-top:0">
            <a href="{{ route('espace.paiements.inscrire') }}" class="btn btn-primary">
                @if ($event->stripe_account_id)
                    Reprendre la vérification
                @else
                    Commencer <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                @endif
            </a>
        </div>

        @if ($event->stripe_account_id)
            <p class="carte-aide" style="margin:1rem 0 0">
                Compte créé
                @if ($event->stripe_valide_at) · informations transmises, en cours de vérification @endif
                @if ($event->stripe_virements_actifs) · virements activés @endif
            </p>
        @endif
    </div>
@endif

@if ($event->stripe_account_id)
    <div class="carte">
        <h2>Votre tableau de bord Stripe</h2>
        <p class="carte-aide">Virements, justificatifs, coordonnées bancaires.</p>
        <a href="{{ route('espace.paiements.stripe') }}" class="mini">Ouvrir Stripe</a>
    </div>
@endif

<div class="carte">
    <h2>Participations</h2>

    @if ($dons->isEmpty())
        <p class="vide">Aucune participation pour l’instant.</p>
    @else
        <div class="liste">
            @foreach ($dons as $don)
                <div class="ligne @if($don->statut !== 'payé') inactive @endif">
                    <span class="icone"><i class="fa-solid fa-gift" aria-hidden="true"></i></span>

                    <div class="corps">
                        <strong>
                            {{ $don->participant?->prenom }} {{ $don->participant?->nom }}
                            — {{ number_format((float) $don->montant, 0, ',', ' ') }} €
                        </strong>
                        <span>
                            {{ $don->created_at?->translatedFormat('j F Y à H\hi') }}
                            @if ($don->message) · « {{ Str::limit($don->message, 70) }} » @endif
                        </span>
                    </div>

                    <div class="outils">
                        <span class="chip {{ $don->statut === 'payé' ? 'chip-live' : 'chip-build' }}" style="position:static">
                            {{ $don->statut === 'payé' ? 'reçu' : 'en attente' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@endunless

@endsection
