@extends('espace.layout')

@section('titre', $qrCode->name)
@section('chapeau', 'Combien de fois ce code a été scanné, et quand. Statistiques anonymes, conservées 90 jours.')
@section('retour', route('admin.qrcodes.index'))
@section('retour-libelle', 'Tous les QR codes')

@section('contenu')

@php
    $parJour = $scans->groupBy(fn ($s) => \Illuminate\Support\Carbon::parse($s->scanned_at)->format('Y-m-d'));
@endphp

<div class="tuiles">
    <div class="tuile"><div class="nombre">{{ $scans->count() }}</div><div class="quoi">scans</div></div>
    <div class="tuile"><div class="nombre">{{ $parJour->count() }}</div><div class="quoi">jours d’activité</div></div>
    <div class="tuile">
        <div class="nombre">{{ $scans->count() ? round($scans->where('appareil', 'mobile')->count() / $scans->count() * 100) : 0 }} %</div>
        <div class="quoi">depuis un téléphone</div>
    </div>
</div>

<div class="carte">
    <h2>Destination</h2>
    <p class="carte-aide" style="margin:0; word-break:break-all">{{ $qrCode->destination_url }}</p>
</div>

@if ($scans->isEmpty())
    <div class="vide-illustre">
        <i class="fa-solid fa-chart-simple" aria-hidden="true"></i>
        <p>Ce code n’a pas encore été scanné.</p>
    </div>
@else
    <div class="carte">
        <h2>Les derniers scans</h2>
        <div class="liste">
            @foreach ($scans->sortByDesc('scanned_at')->take(30) as $scan)
                <div class="ligne">
                    <span class="icone"><i class="fa-solid {{ ['mobile' => 'fa-mobile-screen', 'tablette' => 'fa-tablet-screen-button', 'ordinateur' => 'fa-desktop'][$scan->appareil] ?? 'fa-circle-question' }}" aria-hidden="true"></i></span>
                    <div class="corps">
                        <strong>{{ \Illuminate\Support\Carbon::parse($scan->scanned_at)->translatedFormat('j F à H\hi') }}</strong>
                        <span>{{ ucfirst($scan->appareil ?? 'appareil inconnu') }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

@endsection
