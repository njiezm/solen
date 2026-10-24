@extends('console.layout')

@section('titre', 'Exports — ' . $mariage->nom)
@section('chapeau', 'Des PDF aux couleurs du mariage, prêts à imprimer ou à offrir, et toutes les photos d’origine.')

@section('contenu')

<p style="margin:0 0 1rem"><a href="{{ route('console.mariage', $mariage->slug) }}">← {{ $mariage->nom }}</a></p>

<div class="carte">
    <h2>PDF aux couleurs du mariage</h2>
    <p class="carte-aide">
        Couleurs, police des titres, prénoms et fleurs du thème. Les photos sont mises en page
        quatre par page ; un gros album peut prendre une minute à générer.
    </p>
    <div class="liste">
        @foreach ($types as $type => [$nom, $detail, $icone])
            <div class="ligne @if (! $compte[$type]) inactive @endif">
                <span class="icone"><i class="fa-solid {{ $icone }}" aria-hidden="true"></i></span>
                <div class="corps">
                    <strong>{{ $nom }}</strong>
                    <span>{{ $detail }} · {{ $compte[$type] }} élément{{ $compte[$type] > 1 ? 's' : '' }}</span>
                </div>
                <div class="outils">
                    <a href="{{ route('console.mariage.exports.pdf', [$mariage->slug, $type, 'voir' => 1]) }}" target="_blank" rel="noopener" class="mini">Aperçu</a>
                    <a href="{{ route('console.mariage.exports.pdf', [$mariage->slug, $type]) }}" class="mini">Télécharger</a>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="carte">
    <h2>Photos d’origine</h2>
    <p class="carte-aide">
        Toutes les photos publiées, en pleine qualité, rangées en deux dossiers (galerie, photobooth) :
        celles à confier à un imprimeur ou à offrir aux mariés.
    </p>
    <a href="{{ route('console.mariage.exports.zip', $mariage->slug) }}" class="btn btn-primary">
        <i class="fa-solid fa-file-zipper" aria-hidden="true"></i> Télécharger le ZIP ({{ $compte['album'] + $compte['photobooth'] }} photos)
    </a>
</div>

@endsection
