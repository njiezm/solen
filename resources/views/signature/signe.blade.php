@extends('solen.layout')

@section('title', $devis->libelle() . ' signé')

@section('content')
@php $s = $devis->signature ?? []; @endphp
<section class="signature">
    <div class="wrap signature-wrap" style="text-align:center">
        <p class="eyebrow">{{ config('solen.entreprise.nom_commercial') }}</p>
        <div class="signe-icone" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></div>
        <h1>Devis signé, merci !</h1>
        <p class="signature-objet">
            {{ $devis->libelle() }}@if ($devis->objet) · {{ $devis->objet }}@endif<br>
            Signé par <strong>{{ $s['nom'] ?? '' }}</strong> le {{ $devis->signe_le->translatedFormat('j F Y à H\hi') }}.
        </p>

        <a href="{{ route('signature.pdf', $devis->signature_token) }}" class="btn btn-primary">
            <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> Télécharger le devis signé (PDF)
        </a>

        <p class="signature-notes" style="margin-top:1.4rem">
            Gardez ce PDF : il porte votre signature, sa date et l’empreinte du contenu signé.
            Une question ? <a href="mailto:{{ config('solen.entreprise.email') }}">{{ config('solen.entreprise.email') }}</a>
        </p>
    </div>
</section>
<style>
    .signature { padding: 7rem 0 4rem; }
    .signature-wrap { max-width: 560px; }
    .signe-icone { font-size: 3.4rem; color: #2F7D5D; margin: .6rem 0 .4rem; }
    .signature h1 { margin: 0 0 .6rem; font-size: clamp(1.8rem, 6vw, 2.4rem); }
    .signature-objet { margin: 0 0 1.6rem; color: var(--ink-mute); line-height: 1.6; }
    .signature-notes { font-size: .88rem; color: var(--ink-mute); }
</style>
@endsection
