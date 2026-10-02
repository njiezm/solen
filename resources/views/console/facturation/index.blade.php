@extends('console.layout')

@section('titre', 'Facturation')
@section('chapeau', 'Devis, factures et avoirs de ' . config('solen.entreprise.nom_commercial') . '.')

@section('contenu')

@php use App\Models\DocumentCommercial as D; @endphp

@include('console.facturation._mentions')

<div class="tuiles">
    @foreach ($chiffres as $chiffre)
        <div class="tuile"><div class="nombre">{{ $chiffre['nombre'] }}</div><div class="quoi">{{ $chiffre['quoi'] }}</div></div>
    @endforeach
</div>

<div class="carte">
    <div class="actions" style="margin:0 0 1rem; justify-content:space-between; flex-wrap:wrap">
        <span style="display:flex; gap:.4rem; flex-wrap:wrap">
            <a href="{{ route('console.facturation') }}" class="mini {{ ! $type ? 'choisi' : '' }}">Tout</a>
            @foreach (D::TYPES as $cle => $t)
                <a href="{{ route('console.facturation', ['type' => $cle]) }}" class="mini {{ $type === $cle ? 'choisi' : '' }}">{{ $t['nom'] }}s</a>
            @endforeach
            <a href="{{ route('console.facturation', ['type' => 'facture', 'statut' => 'emis']) }}" class="mini {{ $statut === 'emis' ? 'choisi' : '' }}">Impayées</a>
        </span>
        <span style="display:flex; gap:.4rem">
            <a href="{{ route('console.facturation.codes') }}" class="btn"><i class="fa-solid fa-ticket" aria-hidden="true"></i> Codes promo</a>
            <a href="{{ route('console.facturation.creer', ['type' => 'devis']) }}" class="btn">Nouveau devis</a>
            <a href="{{ route('console.facturation.creer', ['type' => 'facture']) }}" class="btn btn-primary"><i class="fa-solid fa-plus" aria-hidden="true"></i> Nouvelle facture</a>
        </span>
    </div>

    @if ($docs->isEmpty())
        <div class="vide-illustre"><i class="fa-solid fa-file-invoice" aria-hidden="true"></i><p>Aucun document pour l’instant.</p></div>
    @else
        <div class="liste">
            @foreach ($docs as $doc)
                @php
                    $retard = $doc->type === D::FACTURE && $doc->statut === 'emis' && $doc->echeance_le?->isPast();
                    $chip = match (true) {
                        $retard => ['En retard', 'chip-build'],
                        $doc->statut === 'paye', $doc->statut === 'accepte', $doc->statut === 'converti' => [D::STATUTS[$doc->statut], 'chip-live'],
                        $doc->statut === 'brouillon' => ['Brouillon', 'chip-next'],
                        default => [D::STATUTS[$doc->statut] ?? $doc->statut, 'chip-next'],
                    };
                @endphp
                <a href="{{ route('console.facturation.montrer', $doc) }}" class="ligne" style="text-decoration:none; color:inherit">
                    <span class="icone"><i class="fa-solid {{ ['devis' => 'fa-file-signature', 'facture' => 'fa-file-invoice', 'avoir' => 'fa-file-circle-minus'][$doc->type] }}" aria-hidden="true"></i></span>
                    <div class="corps">
                        <strong>{{ $doc->libelle() }} · {{ $doc->client_nom }}</strong>
                        <span>{{ $doc->emis_le?->translatedFormat('j M Y') ?? 'Non émis' }}@if ($doc->event) · {{ $doc->event->nom }}@endif @if ($doc->objet) · {{ $doc->objet }}@endif</span>
                    </div>
                    <div class="outils">
                        <span class="chip {{ $chip[1] }}" style="position:static">{{ $chip[0] }}</span>
                        <strong style="min-width:6.5rem; text-align:right">{{ D::euros($doc->total_centimes) }}</strong>
                    </div>
                </a>
            @endforeach
        </div>
        <div style="margin-top:1rem">{{ $docs->links() }}</div>
    @endif
</div>

@endsection
