@php
    $c = fn ($cle, $libelle = null) => view('legal.contenu._champ', ['cle' => $cle, 'libelle' => $libelle])->render();
    $plans = collect(config('solen.plans'));
@endphp

<p class="chapo">
    Les présentes conditions générales de vente (CGV) s’appliquent à toute commande d’une formule
    {{ config('solen.brand.name') }} passée par un particulier. Passer commande vaut acceptation des CGV
    en vigueur au jour de la commande.
</p>

<h2>1. Le prestataire</h2>
<p>
    {!! $c('nom_commercial') !!} est le nom commercial de {!! $c('dirigeant', 'nom et prénom') !!},
    entrepreneur individuel ({!! $c('raison_sociale') !!}), SIRET {!! $c('siret', 'numéro SIRET') !!},
    {!! $c('adresse', 'adresse') !!}. Contact : {!! $c('email') !!}.
</p>

<h2>2. Le service</h2>
<p>
    {{ config('solen.brand.name') }} fournit une application de mariage accessible en ligne : un site pour les
    invités et un espace de gestion pour les mariés. Le contenu exact de chaque formule est décrit sur la page
    tarifs au moment de la commande. L’accompagnement par l’équipe {{ config('solen.brand.name') }}, lorsqu’il est
    prévu, porte uniquement sur les éléments listés dans la commande ou le devis.
</p>

<h2>3. Prix</h2>
<p>Les prix sont indiqués en euros, toutes taxes comprises. {{ \App\Solen\Entreprise::get('mention_tva') }}.</p>
<ul>
    @foreach ($plans as $plan)
        <li>{{ $plan['nom'] }} : {{ $plan['prix'] }} €, paiement unique, sans abonnement.</li>
    @endforeach
</ul>
<p>Les options et prestations sur mesure font l’objet d’un devis préalable.</p>

<h2>4. Commande et paiement</h2>
<p>
    La commande est passée en ligne. Le paiement s’effectue par carte bancaire via Stripe, prestataire de
    paiement sécurisé : {{ config('solen.brand.name') }} n’a jamais accès aux numéros de carte. Pour une
    prestation sur devis, le paiement peut aussi se faire par virement, dans un délai de
    {{ \App\Solen\Entreprise::get('delai_paiement_jours', 15) }} jours à compter de la facture.
    Une facture est remise pour chaque paiement.
</p>

<h2>5. Mise à disposition</h2>
<p>
    Le site du mariage est créé dès la confirmation du paiement et les accès sont envoyés par e-mail. Il reste
    accessible pendant la durée d’archive prévue par la formule, à compter de la date du mariage.
</p>

<h2>6. Droit de rétractation</h2>
<p>
    Vous disposez d’un délai de quatorze jours à compter de la commande pour vous rétracter, sans avoir à vous
    justifier, en écrivant à {!! $c('email') !!}. Le service étant mis à disposition immédiatement à votre
    demande, vous restez redevable d’un montant proportionnel au service fourni jusqu’à votre rétractation
    (article L221-25 du Code de la consommation). Le remboursement intervient dans les quatorze jours, par le
    même moyen de paiement.
</p>
<p>
    Le droit de rétractation ne peut plus être exercé une fois la prestation pleinement exécutée, notamment
    après la date du mariage, si vous avez donné votre accord exprès à son exécution immédiate et reconnu
    perdre ce droit (article L221-28 1°).
</p>

<h2>7. Cagnotte</h2>
<p>
    Les participations des invités sont encaissées par Stripe et versées directement sur le compte bancaire des
    mariés. {{ config('solen.brand.name') }} ne détient jamais ces fonds et ne prélève aucune commission ; seuls
    les frais de Stripe s’appliquent. Les mariés sont seuls responsables de l’usage de la cagnotte.
</p>

<h2>8. Contenus publiés</h2>
<p>
    Les mariés sont responsables des contenus qu’ils publient et de ceux qu’ils laissent publier par leurs
    invités. Ils disposent pour cela d’outils de modération. Tout contenu illicite peut être retiré.
</p>

<h2>9. Responsabilité</h2>
<p>
    {{ config('solen.brand.name') }} met tout en œuvre pour assurer la disponibilité du service, sans pouvoir
    garantir l’absence d’interruption, notamment du fait du réseau mobile sur le lieu de réception. Sa
    responsabilité est limitée au montant payé pour la formule.
</p>

<h2>10. Données personnelles</h2>
<p>Voir la <a href="{{ route('legal.confidentialite') }}">politique de confidentialité</a>.</p>

<h2>11. Médiation et litiges</h2>
<p>
    En cas de difficulté, écrivez-nous d’abord : nous répondons sous sept jours ouvrés. À défaut d’accord, vous
    pouvez recourir gratuitement au médiateur de la consommation : {!! $c('mediateur', 'médiateur de la consommation : nom et site') !!}.
    Les présentes CGV sont soumises au droit français.
</p>

<p class="mise-a-jour">Version du {{ \Illuminate\Support\Carbon::parse(config('solen.legal.version', '2026-10-02'))->translatedFormat('j F Y') }}.</p>
