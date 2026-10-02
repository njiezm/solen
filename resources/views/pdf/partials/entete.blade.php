{{-- En-tête : la marque à gauche, le type et le numéro du document à droite. --}}
@php
    $vendeur = $vendeur ?? \App\Solen\Entreprise::tout();
    $marque  = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents(public_path('images/solen/mark.svg')));
    // Le logo de l'entreprise qui édite Solen, discret : PNG, car le SVG
    // charge sa police en ligne, ce que DomPDF ne fait pas.
    $njiezm  = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/njiezm/logo.png')));
@endphp
<table class="entete">
    <tr>
        <td>
            <div class="marque"><img src="{{ $marque }}" alt="">{{ config('solen.brand.name') }}</div>
            <div class="marque-sous">by <img src="{{ $njiezm }}" alt="{{ $vendeur['raison_sociale'] ?? 'NJIEZM.FR' }}" style="height:5mm; vertical-align:middle; margin-left:1mm"></div>
        </td>
        <td class="bandeau-doc">
            <div class="type-doc">{{ $titreDoc }}</div>
            @isset($sousTitreDoc)<div class="numero-doc">{{ $sousTitreDoc }}</div>@endisset
        </td>
    </tr>
</table>
<div class="filet"></div>
<div class="filet-or"></div>

<div class="pied">
    <img src="{{ $njiezm }}" alt="" style="height:3.6mm; vertical-align:middle; margin-right:2mm; opacity:.85">
    {{ $vendeur['nom_commercial'] ?? '' }} —@if (! empty($vendeur['dirigeant'])) {{ $vendeur['dirigeant'] }},@endif entrepreneur individuel ({{ $vendeur['raison_sociale'] ?? '' }})
    @if (! empty($vendeur['siret'])) · SIRET {{ $vendeur['siret'] }} @endif
    @if (! empty($vendeur['adresse'])) · {{ $vendeur['adresse'] }} @endif
    · {{ $vendeur['email'] ?? '' }}
    @if (! empty($vendeur['tva'])) · TVA {{ $vendeur['tva'] }} @else · {{ $vendeur['mention_tva'] ?? '' }} @endif
</div>
