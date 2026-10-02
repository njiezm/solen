@extends('espace.layout')

@section('titre', 'Ma formule')
@section('chapeau', 'Passez à la formule supérieure quand vous le souhaitez : vous ne payez que la différence, et rien de ce que vous avez préparé n’est perdu.')

@section('contenu')

<div class="carte" style="border-color:var(--accent)">
    <p class="carte-aide" style="margin:0 0 .3rem; text-transform:uppercase; letter-spacing:.12em; font-size:.72rem">Votre formule</p>
    <h2 style="font-size:1.5rem; margin-bottom:.3rem">{{ $actuelle?->nom ?? $event->plan }}</h2>
    @if ($actuelle)
        <p class="carte-aide" style="margin:0">{{ $details[$actuelle->cle]['desc'] ?? '' }}</p>
    @endif
</div>

@if ($superieures->isEmpty())
    <div class="vide-illustre">
        <i class="fa-solid fa-crown" aria-hidden="true"></i>
        <p>Vous avez déjà la formule la plus complète. Rien ne vous manque !</p>
    </div>
@else
    <div class="tuiles" style="grid-template-columns:repeat(auto-fit, minmax(280px, 1fr))">
        @foreach ($superieures as $offre)
            <div class="carte" style="margin:0; display:flex; flex-direction:column">
                <h2 style="margin-bottom:.2rem">{{ $offre['plan']->nom }}</h2>
                <p class="carte-aide">{{ $details[$offre['plan']->cle]['desc'] ?? '' }}</p>

                @if ($offre['nouveautes']->isNotEmpty())
                    <p style="margin:.4rem 0 .4rem; font-weight:600; font-size:.88rem">En plus de ce que vous avez :</p>
                    <ul style="margin:0 0 1rem; padding-left:1.1rem; font-size:.9rem; line-height:1.7">
                        @foreach ($offre['nouveautes'] as $module)
                            <li>{{ $module->nom }}</li>
                        @endforeach
                    </ul>
                @endif

                <div style="margin-top:auto">
                    <p style="margin:0 0 .8rem">
                        <span style="font-size:1.8rem; font-family:var(--display, serif)">+ {{ $offre['difference'] }} €</span>
                        <span class="carte-aide"> au lieu de {{ $offre['plan']->prix }} €</span>
                    </p>
                    @if ($paiement)
                        <form method="POST" action="{{ route('espace.formule.payer', $offre['plan']->cle) }}">
                            @csrf
                            <button class="btn btn-primary" style="width:100%"><i class="fa-solid fa-lock" aria-hidden="true"></i> Passer à {{ $offre['plan']->nom }}</button>
                        </form>
                    @else
                        <a href="mailto:{{ config('solen.entreprise.email') }}?subject={{ rawurlencode('Passage à la formule ' . $offre['plan']->nom . ' — ' . $event->nom) }}" class="btn" style="width:100%">Nous écrire pour changer de formule</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    <p class="carte-aide" style="margin-top:1rem">Paiement sécurisé par Stripe. La facture vous est envoyée par e-mail, et les nouveaux modules s’activent aussitôt.</p>
@endif

@endsection
