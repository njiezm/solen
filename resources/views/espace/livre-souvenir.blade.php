@extends('espace.layout')

@section('titre', 'Livre souvenir')
@section('retour', route('espace.index'))
@section('retour-libelle', 'Tableau de bord')
@section('chapeau', 'Vos messages, vos photos et votre déroulé réunis en un PDF prêt à faire relier.')

@section('contenu')

<div class="tuiles">
    <div class="tuile"><div class="nombre">{{ $messages }}</div><div class="quoi">messages</div></div>
    <div class="tuile"><div class="nombre">{{ $photos }}</div><div class="quoi">photos</div></div>
    <div class="tuile"><div class="nombre">{{ $etapes }}</div><div class="quoi">étapes du déroulé</div></div>
</div>

<div class="carte">
    <h2>Composer le livre</h2>
    <p class="carte-aide">
        Le fichier est généré à la demande, à partir de ce qui existe au moment
        du clic. Vous pouvez le régénérer autant de fois que vous voulez, par
        exemple une fois que tous les invités ont écrit.
    </p>

    <div class="actions" style="margin-top:0; border:0; padding-top:0">
        <a href="{{ route('espace.livre.apercu') }}" target="_blank" rel="noopener" class="btn btn-ghost">
            <i class="fa-solid fa-eye" aria-hidden="true"></i> Aperçu
        </a>
        <a href="{{ route('espace.livre.telecharger') }}" class="btn btn-primary">
            <i class="fa-solid fa-download" aria-hidden="true"></i> Télécharger le PDF
        </a>
        <a href="{{ route('espace.modules.editer', 'pdf') }}" class="mini pousse">Réglages du livre</a>
    </div>
</div>

<div class="carte">
    <h2>Le faire imprimer</h2>
    <p class="carte-aide">
        Solen ne fabrique pas le livre : le PDF vous appartient et n’importe
        quel imprimeur peut le relier. Quelques repères pour choisir.
    </p>

    <div class="liste">
        <div class="ligne">
            <span class="icone"><i class="fa-solid fa-shop" aria-hidden="true"></i></span>
            <div class="corps">
                <strong>Un imprimeur près de chez vous</strong>
                <span>Le plus sûr pour vérifier le papier et la reliure avant de payer.</span>
            </div>
        </div>
        <div class="ligne">
            <span class="icone"><i class="fa-solid fa-globe" aria-hidden="true"></i></span>
            <div class="corps">
                <strong>Un service en ligne</strong>
                <span>Comptez de 25 à 60 € selon le nombre de pages et la couverture.</span>
            </div>
        </div>
        <div class="ligne">
            <span class="icone"><i class="fa-solid fa-circle-info" aria-hidden="true"></i></span>
            <div class="corps">
                <strong>Bon à savoir</strong>
                <span>Le format est réglable en A4 ou A5. Au-delà de 100 photos, seules les 100 premières sont incluses pour que le fichier reste imprimable.</span>
            </div>
        </div>
    </div>
</div>

@endsection
