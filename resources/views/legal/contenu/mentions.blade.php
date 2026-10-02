@php $c = fn ($cle, $libelle = null) => view('legal.contenu._champ', ['cle' => $cle, 'libelle' => $libelle])->render(); @endphp

<h2>Éditeur du site</h2>
<p>
    Le site et l’application {{ config('solen.brand.name') }} sont édités par
    <strong>{!! $c('dirigeant', 'nom et prénom') !!}</strong>, entrepreneur individuel, exerçant sous le nom
    <strong>{!! $c('raison_sociale') !!}</strong> (nom commercial : {!! $c('nom_commercial') !!}).
</p>
<ul>
    <li>Adresse : {!! $c('adresse', 'adresse de l’établissement') !!}</li>
    <li>SIRET : {!! $c('siret', 'numéro SIRET') !!}</li>
    <li>TVA : @if (\App\Solen\Entreprise::get('tva')) {{ \App\Solen\Entreprise::get('tva') }} @else {{ \App\Solen\Entreprise::get('mention_tva') }} @endif</li>
    <li>Contact : {!! $c('email') !!}@if (\App\Solen\Entreprise::get('telephone')) · {{ \App\Solen\Entreprise::get('telephone') }}@endif</li>
</ul>
<p>Directeur de la publication : {!! $c('dirigeant', 'nom et prénom') !!}.</p>

<h2>Hébergement</h2>
<p>{!! $c('hebergeur', 'hébergeur : nom, adresse, téléphone') !!}</p>

<h2>Propriété intellectuelle</h2>
<p>
    La marque {{ config('solen.brand.name') }}, l’application, ses thèmes, ses textes et ses visuels sont la
    propriété de l’éditeur. Toute reproduction sans autorisation est interdite.
</p>
<p>
    Les contenus publiés par les mariés et leurs invités (textes, photos, messages) restent la propriété de
    leurs auteurs. Ils ne sont utilisés que pour faire fonctionner le site du mariage concerné.
</p>

<h2>Données personnelles</h2>
<p>
    Le traitement des données est décrit dans notre
    <a href="{{ route('legal.confidentialite') }}">politique de confidentialité</a>.
</p>
