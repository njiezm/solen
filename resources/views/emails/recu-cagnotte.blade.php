<x-courriel::layout
    :titre="'Merci — ' . $event->nom"
    preheader="Votre participation est bien arrivée.">

<p style="margin:0 0 18px;font-size:20px;color:#1B1B2F;font-weight:600;">
    Merci {{ $don->participant?->prenom }}.
</p>

<p style="margin:0 0 20px;">
    Votre participation de
    <strong>{{ number_format((float) $don->montant, 0, ',', ' ') }} €</strong>
    à la cagnotte de <strong>{{ $event->nom }}</strong> est bien arrivée.
    Elle leur sera versée directement.
</p>

@if ($don->message)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="background:#FCFAF7;border-left:3px solid #C99B63;border-radius:0 10px 10px 0;margin:0 0 22px;">
        <tr><td style="padding:16px 18px;font-style:italic;color:#3A3A52;">
            « {{ $don->message }} »
        </td></tr>
    </table>
    <p style="margin:0 0 22px;font-size:13.5px;color:#6E6E85;">
        Votre mot leur a été transmis.
    </p>
@endif

<x-courriel::partials.bouton :url="$urlSite" libelle="Revoir leur site" />

<p style="margin:0;font-size:12.5px;color:#6E6E85;">
    Ce message vient des mariés. Votre reçu de paiement vous est envoyé
    séparément par Stripe, notre prestataire.
</p>

</x-courriel::layout>
