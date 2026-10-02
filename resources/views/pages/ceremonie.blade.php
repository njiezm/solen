@extends('layout')
@section('content')

@php
    // Récupération de toutes les données nécessaires en une seule fois pour de meilleures performances.
    // 1. L'étape actuellement en cours pour la bannière de statut.
    $etapeEnCours = App\Models\EtapeCeremonie::where('en_cours', true)->first();
    
    // 2. Le message de remerciements général.
    $remerciements = App\Models\Remerciement::first();
    
    // 3. Toutes les étapes avec leur contenu associé (lectures, chants, prières).
    // La méthode 'with' est appelée "eager loading" et est très efficace pour éviter les requêtes multiples dans une boucle.
    $etapes = App\Models\EtapeCeremonie::with(['lectures', 'chants', 'prieres'])->orderBy('ordre')->get();
@endphp

<div class="container">
    <div class="row">
        <div class="col-12">
            <h2 class="text-center ceremony-heading">
                Cérémonie Religieuse 💍
            </h2>
        </div>
    </div>

    <!-- Bannière de statut en direct -->
    <div class="row">
        <div class="col-12">
            <div class="status-banner-live text-center">
                <i class="fa-solid fa-signal me-2"></i> 
                <span class="fw-bold">STATUT EN DIRECT :</span> Nous sommes actuellement à l'étape : 
                <span class="current-step">
                    {{ $etapeEnCours ? $etapeEnCours->titre : 'En attente de début' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Section des remerciements (si elle existe) -->
    @if($remerciements)
    <div class="row mt-4">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-body text-center">
                    <h3>{{ $remerciements->titre }}</h3>
                    <p>{!! nl2br($remerciements->contenu) !!}</p>
                    <p class="fw-bold">{{ $remerciements->signatures }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Carte d'informations sur le lieu -->
    <div class="row mt-4">
        <div class="col-lg-8 mx-auto">
            <div class="location-card text-center mb-5">
                <h4 class="fw-bold" style="color:var(--vert-sapin);">Église Saint-Laurent du Lamentin</h4>
                <p class="mb-1"><i class="fa-solid fa-clock me-2 text-dore-accent"></i> <strong>Heure d'arrivée des invités :</strong> 13h30</p>
                <p><i class="fa-solid fa-bell me-2 text-dore-accent"></i> <strong>Début Précis de la Cérémonie :</strong> 14h00</p>
                <p class="mt-3">
                    <i class="fa-solid fa-map-pin me-2 text-dore-accent"></i> Adresse : 36 Rue Schoelcher, Le Lamentin 97232, Martinique.
<a 
  href="geo:0,0?q=36 Rue Schoelcher, Le Lamentin 97232"
  class="btn btn-sm ms-3"
  style="background-color: var(--vert-sapin); color: var(--champagne-clair);"
>
    <i class="fa-solid fa-map-location-dot me-1"></i>
    Voir sur la carte
</a>
<br>
    <a href="https://online.fliphtml5.com/IMPAKT-STUDIO/clff/" class="btn btn-sm ms-3" style="background-color: var(--vert-sapin); color: var(--champagne-clair);">
        <i class="fa-solid fa-file-pdf me-1"></i>
        Ouvrir le livret PDF
    </a>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Livret de Cérémonie Digital -->
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h3 class="fw-bold text-center mb-4" style="color:#e8e8e8; font-family:var(--font-pro);">
                Le Livret de Cérémonie Digital
            </h3>

            <div class="accordion ceremony-accordion" id="ceremonyAccordion">
                @foreach($etapes as $etape)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading{{ $etape->id }}">
                        <button class="accordion-button {{ !$etape->en_cours ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $etape->id }}" aria-expanded="{{ $etape->en_cours ? 'true' : 'false' }}" aria-controls="collapse{{ $etape->id }}">
                            @if($etape->icone)
                                <i class="{{ $etape->icone }} me-2"></i>
                            @endif
                            {{ $etape->titre }}
                            
                            <!-- Badges de statut -->
                            <div class="ms-auto">
                                @if($etape->termine)
                                    <span class="badge bg-success me-2"><i class="fa-solid fa-check me-1"></i>Terminée</span>
                                @elseif($etape->en_cours)
                                    <span class="badge bg-warning text-dark me-2"><i class="fa-solid fa-play me-1"></i>En cours</span>
                                @endif
                            </div>
                        </button>
                    </h2>
                    <div id="collapse{{ $etape->id }}" class="accordion-collapse collapse {{ $etape->en_cours ? 'show' : '' }}" aria-labelledby="heading{{ $etape->id }}" data-bs-parent="#ceremonyAccordion">
                        <div class="accordion-body">
                            
                            <!-- Description de l'étape -->
                            @if($etape->description)
                                <p>{!! nl2br($etape->description) !!}</p>
                            @endif

                            <!-- Afficher les lectures liées à cette étape -->
                            @if($etape->lectures->count() > 0)
                                <h4 class="mt-4">Lectures</h4>
                                @foreach($etape->lectures as $lecture)
                                    <div class="card mt-3">
                                        <div class="card-body">
                                            <h5 class="card-title">{{ $lecture->titre }}</h5>
                                            <h6 class="card-subtitle mb-2 text-muted">{{ $lecture->reference }}</h6>
                                            <p class="card-text">{!! nl2br($lecture->contenu) !!}</p>
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                            <!-- Afficher les chants liés à cette étape -->
                            @if($etape->chants->count() > 0)
                                <h4 class="mt-4">Chants</h4>
                                @foreach($etape->chants as $chant)
                                    <div class="card mt-3">
                                        <div class="card-body">
                                            <h5 class="card-title">{{ $chant->titre }}</h5>
                                            @if($chant->auteur)<p class="card-subtitle mb-2 text-muted">{{ $chant->auteur }}</p>@endif
                                            <pre class="card-text">{{ $chant->paroles }}</pre>
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                            <!-- Afficher les prières liées à cette étape -->
                            @if($etape->prieres->count() > 0)
                                <h4 class="mt-4">Prières</h4>
                                @foreach($etape->prieres as $priere)
                                    <div class="card mt-3">
                                        <div class="card-body">
                                            <h5 class="card-title">{{ $priere->titre }}</h5>
                                            <p class="card-text">{!! nl2br($priere->contenu) !!}</p>
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection