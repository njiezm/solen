@extends('espace.layout')

@section('titre', 'Cagnotte')
@section('retour', route('espace.index'))
@section('retour-libelle', 'Tableau de bord')
@section('chapeau', 'L’argent de votre cagnotte arrive directement sur votre compte bancaire, jamais sur celui de Solen.')

@section('contenu')

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

@endsection
