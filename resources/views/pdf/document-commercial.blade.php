@php
    use App\Models\DocumentCommercial as D;
    $v = $doc->vendeur ?: \App\Solen\Entreprise::tout();
    $euros = fn ($c) => D::euros((int) $c);
    $sousTitre = $doc->numero
        ? 'N° ' . $doc->numero . ' · ' . $doc->emis_le?->translatedFormat('j F Y')
        : 'Brouillon — non émis';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $doc->libelle() }}</title>
    @include('pdf.partials.papier')
    {{-- Un devis ou une facture tient sur une page : on resserre ce que le
         papier commun aère pour les documents longs. --}}
    <style>
        @page { margin: 12mm 14mm 18mm; }
        body { font-size: 9pt; line-height: 1.35; }
        table.entete { margin-bottom: 5mm; }
        .marque { font-size: 16pt; }
        .marque img { width: 10mm; height: 10mm; }
        .type-doc { font-size: 18pt; }
        .filet-or { margin-bottom: 5mm; }
        table.parties { margin-bottom: 5mm; }
        .encart { padding: 2.5mm 4mm; }
        .encart-titre { margin-bottom: 1mm; }
        .encart strong { font-size: 10pt; }
        table.lignes { margin-bottom: 4mm; }
        table.lignes th { padding: 2mm 2.5mm; }
        table.lignes td { padding: 1.4mm 2.5mm; }
        table.lignes .detail { font-size: 7.5pt; margin-top: .5mm; }
        table.totaux { width: 66%; margin-left: 34%; }
        table.totaux td { padding: 1.2mm 2.5mm; }
        .mentions { margin-top: 3mm; }
        .offert { color: #2F7D5D; }
        .pied { bottom: -11mm; }
    </style>
</head>
<body>

@include('pdf.partials.entete', ['vendeur' => $v, 'titreDoc' => $doc->nomDuType(), 'sousTitreDoc' => $sousTitre])

<table class="parties">
    <tr>
        <td style="padding-right:3mm">
            <div class="encart">
                <div class="encart-titre">Émis par</div>
                {{-- Adresse et SIRET figurent déjà dans le pied de page, sur
                     chaque feuille : l'encart reste court. --}}
                <strong>{{ $v['nom_commercial'] ?? '' }}</strong><br>
                {{ $v['dirigeant'] ?? '' }} — {{ $v['raison_sociale'] ?? '' }} (EI)@if (! empty($v['siret'])) · SIRET {{ $v['siret'] }}@endif<br>
                {{ $v['email'] ?? '' }}@if (! empty($v['telephone'])) · {{ $v['telephone'] }}@endif
            </div>
        </td>
        <td style="padding-left:3mm">
            <div class="encart">
                <div class="encart-titre">{{ $doc->type === D::DEVIS ? 'Pour' : 'Facturé à' }}</div>
                <strong>{{ $doc->client_nom }}</strong><br>
                @if ($doc->client_adresse){!! nl2br(e($doc->client_adresse)) !!}<br>@endif
                @if ($doc->client_email){{ $doc->client_email }}<br>@endif
                @if ($doc->client_telephone){{ $doc->client_telephone }}@endif
            </div>
        </td>
    </tr>
</table>

@if ($doc->objet)
    <p class="serif" style="font-size:11pt; color:#1B1B2F; margin-bottom:2mm">{{ $doc->objet }}</p>
@endif
{{-- Le mariage, sauf si l'objet le nomme déjà. --}}
@if ($doc->event && ! str_contains((string) $doc->objet, $doc->event->nom))
    <p class="doux" style="margin-bottom:3mm">Mariage : {{ $doc->event->nom }}@if ($doc->event->dateLocale()) — {{ $doc->event->dateLocale()->translatedFormat('j F Y') }}@endif</p>
@endif

<table class="lignes">
    <thead>
        <tr>
            <th>Désignation</th>
            <th class="num" style="width:16mm">Qté</th>
            <th class="num" style="width:30mm">Prix unitaire</th>
            <th class="num" style="width:30mm">Montant</th>
        </tr>
    </thead>
    <tbody>
        @foreach (collect($doc->lignes)->reject(fn ($l) => ! empty($l['deduction'])) as $ligne)
            @php $montant = (int) round(((float) ($ligne['quantite'] ?? 1)) * (int) $ligne['prix_unitaire_centimes']); @endphp
            <tr>
                <td>
                    {{ $ligne['designation'] }}
                    @if (! empty($ligne['detail']))<span class="detail">{{ $ligne['detail'] }}</span>@endif
                </td>
                <td class="num">{{ rtrim(rtrim(number_format((float) ($ligne['quantite'] ?? 1), 2, ',', ''), '0'), ',') }}</td>
                {{-- Une prestation à 0 € est un geste : elle se lit « Offert ». --}}
                @if ((int) $ligne['prix_unitaire_centimes'] === 0)
                    <td class="num offert">Offert</td>
                    <td class="num offert">Offert</td>
                @else
                    <td class="num">{{ $euros($ligne['prix_unitaire_centimes']) }}</td>
                    <td class="num" @if (! empty($ligne['deduction'])) style="color:#2F7D5D" @endif>{{ $euros($montant) }}</td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>

@php
    $deductions = collect($doc->lignes)->filter(fn ($l) => ! empty($l['deduction']));
    $prestations = $doc->total_centimes + $doc->remise_centimes - (int) $deductions->sum('prix_unitaire_centimes');
@endphp
{{-- Ordre de lecture : prestations, remise, total, puis acomptes déduits et reste à payer. --}}
<table class="totaux">
    @if ($doc->remise_centimes)
        <tr><td>Sous-total</td><td class="num">{{ $euros($prestations) }}</td></tr>
        <tr><td class="or">{{ $doc->libelleRemise() }}</td><td class="num or">− {{ $euros($doc->remise_centimes) }}</td></tr>
    @endif
    <tr><td>Total HT</td><td class="num">{{ $euros($prestations - $doc->remise_centimes) }}</td></tr>
    <tr><td class="doux">TVA</td><td class="num doux">{{ empty($v['tva']) ? '0,00 €' : '—' }}</td></tr>
    @foreach ($deductions as $d)
        <tr><td style="color:#2F7D5D">{{ str_replace("Acompte versé — facture ", "Acompte déjà versé · ", $d["designation"]) }}</td><td class="num" style="color:#2F7D5D">{{ $euros($d['prix_unitaire_centimes']) }}</td></tr>
    @endforeach
    <tr class="total"><td>{{ $doc->type === D::AVOIR ? 'Montant de l’avoir' : ($deductions->isNotEmpty() ? 'Reste à payer' : 'Total à payer') }}</td><td class="num">{{ $euros($doc->total_centimes) }}</td></tr>
</table>

<div class="mentions">
    @if ($doc->statut === 'paye')
        <p><span class="tampon">Payé{{ $doc->paye_le ? ' le ' . $doc->paye_le->translatedFormat('j F Y') : '' }}{{ $doc->mode_paiement ? ' · ' . (D::MODES_PAIEMENT[$doc->mode_paiement] ?? $doc->mode_paiement) : '' }}</span></p>
    @elseif ($doc->statut === 'annule')
        <p><span class="tampon tampon--annule">Annulée par avoir</span></p>
    @endif

    @if ($v['mention_tva'] ?? false)<p>{{ $v['mention_tva'] }}.</p>@endif

    @if ($doc->type === D::FACTURE && $doc->statut !== 'paye')
        <p>
            Échéance : <strong>{{ $doc->echeance_le?->translatedFormat('j F Y') }}</strong>.
            @if (! empty($v['iban'])) Règlement par virement : IBAN {{ $v['iban'] }}@if (! empty($v['bic'])) · BIC {{ $v['bic'] }}@endif, en indiquant le numéro {{ $doc->numero }}. @endif
        </p>
        <p>En cas de retard de paiement, des pénalités au taux d’intérêt légal sont exigibles. Pas d’escompte pour paiement anticipé.</p>
    @endif

    @if ($doc->type === D::DEVIS)
        {{-- Validité et signature côte à côte : une page, pas deux. --}}
        <table style="width:100%; margin-top:2mm"><tr>
            <td style="width:50%; vertical-align:top; padding-right:4mm">
                Devis valable jusqu’au <strong>{{ $doc->valide_jusqu_au?->translatedFormat('j F Y') ?? '—' }}</strong>.
                Bon pour accord : date et signature précédées de « lu et approuvé ».
            </td>
            <td style="width:50%; height:17mm; border:.3mm dashed #C9C2B4; padding:3mm; vertical-align:top; font-size:7.5pt" class="doux">Signature du client</td>
        </tr></table>
    @endif

    @if ($doc->type === D::AVOIR && $doc->origine)
        <p>Avoir émis en annulation de la facture {{ $doc->origine->numero }} du {{ $doc->origine->emis_le?->translatedFormat('j F Y') }}.</p>
    @endif

    @if ($doc->notes)<p>{!! nl2br(e($doc->notes)) !!}</p>@endif

    <p>Conditions générales de vente : {{ route('legal.cgv') }}</p>
</div>

</body>
</html>
