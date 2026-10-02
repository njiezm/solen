<x-courriel::layout :titre="$echec ? 'Paiement abandonné' : 'Nouvelle vente'">

@if ($echec)
    <p style="margin:0 0 18px;font-size:20px;color:#B3261E;font-weight:600;">
        Paiement abandonné
    </p>
    <p style="margin:0 0 20px;">
        Cette commande n’a pas abouti. Un message dans l’heure en récupère
        souvent une bonne partie.
    </p>
@else
    <p style="margin:0 0 18px;font-size:20px;color:#1F5A43;font-weight:600;">
        Vente conclue — {{ number_format($commande->montant_centimes / 100, 0, ',', ' ') }} €
    </p>
@endif

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#FCFAF7;border:1px solid #E7E1D7;border-radius:12px;margin:0 0 22px;">
    <tr><td style="padding:18px 20px;font-size:14px;line-height:1.9;">
        <strong style="color:#1B1B2F;">{{ $commande->nom }}</strong><br>
        Formule : {{ $commande->formule()?->nom ?? $commande->plan }}<br>
        Contact : <a href="mailto:{{ $commande->email }}" style="color:#A87C46;">{{ $commande->email }}</a><br>
        @if ($commande->date_principale)
            Mariage le {{ $commande->date_principale->translatedFormat('j F Y') }}<br>
        @endif
        @if ($commande->lieu_ville)
            Lieu : {{ $commande->lieu_ville }}<br>
        @endif
        Référence : {{ $commande->uuid }}
    </td></tr>
</table>

<x-courriel::partials.bouton :url="$urlFiche" :libelle="$echec ? 'Voir la console' : 'Ouvrir la fiche'" />

</x-courriel::layout>
