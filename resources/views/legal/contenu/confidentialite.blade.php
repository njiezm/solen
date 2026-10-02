@php $c = fn ($cle, $libelle = null) => view('legal.contenu._champ', ['cle' => $cle, 'libelle' => $libelle])->render(); @endphp

<p class="chapo">
    Nous collectons le strict nécessaire pour faire vivre votre mariage, rien de plus. Aucune donnée n’est vendue,
    aucune publicité n’est affichée, aucun traceur publicitaire n’est déposé.
</p>

<h2>Responsable du traitement</h2>
<p>{!! $c('dirigeant', 'nom et prénom') !!}, {!! $c('raison_sociale') !!} (EI), {!! $c('adresse', 'adresse') !!} — {!! $c('email') !!}.</p>

<h2>Ce que nous traitons, et pourquoi</h2>
<table>
    <thead><tr><th>Données</th><th>Finalité</th><th>Base légale</th><th>Conservation</th></tr></thead>
    <tbody>
        <tr><td>Mariés : nom, e-mail, téléphone, informations du mariage</td><td>Créer et faire fonctionner le site, facturer</td><td>Exécution du contrat</td><td>Durée d’archive de la formule ; factures 10 ans</td></tr>
        <tr><td>Invités : prénom, nom, messages, photos, réponses aux jeux et au RSVP</td><td>Fonctionnalités du site du mariage</td><td>Intérêt légitime des mariés</td><td>Durée d’archive de la formule</td></tr>
        <tr><td>Scans de QR codes : date, type d’appareil, adresse IP tronquée</td><td>Statistiques anonymes</td><td>Intérêt légitime</td><td>90 jours</td></tr>
        <tr><td>Paiements</td><td>Encaisser la formule et la cagnotte</td><td>Exécution du contrat</td><td>Traités par Stripe, jamais stockés chez nous</td></tr>
    </tbody>
</table>

<h2>Destinataires</h2>
<p>
    Les données ne sont accessibles qu’aux mariés du mariage concerné, aux personnes qu’ils invitent à les aider,
    et à l’équipe {{ config('solen.brand.name') }}. Nos sous-traitants : l’hébergeur du site
    ({!! $c('hebergeur', 'hébergeur') !!}), Stripe pour les paiements, et notre prestataire d’envoi d’e-mails.
</p>

<h2>Cookies</h2>
<p>
    Le site n’utilise qu’un cookie de session, indispensable à son fonctionnement (connexion, panier, code d’accès).
    Il n’est pas soumis à consentement. Aucun cookie publicitaire ou de mesure d’audience tierce n’est déposé.
</p>

<h2>Vos droits</h2>
<p>
    Vous pouvez accéder à vos données, les rectifier, les effacer, vous opposer à leur traitement ou en demander la
    portabilité en écrivant à {!! $c('email') !!}. Un invité peut demander aux mariés, ou directement à nous, le
    retrait d’un message ou d’une photo. Vous pouvez aussi saisir la CNIL (cnil.fr).
</p>
