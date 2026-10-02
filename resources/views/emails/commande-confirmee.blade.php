<x-courriel::layout
    titre="Votre mariage est en ligne"
    preheader="Vos identifiants et l'adresse de votre site, à conserver.">

<p style="margin:0 0 18px;font-size:20px;color:#1B1B2F;font-weight:600;">
    {{ $commande->nom }}, c’est prêt.
</p>

<p style="margin:0 0 18px;">
    Bonjour{{ $commande->partenaire_1 ? ' ' . $commande->partenaire_1 : '' }},<br>
    votre espace de mariage vient d’être créé avec la formule
    <strong>{{ $commande->formule()?->nom }}</strong>.
    Nous avons déjà préparé le déroulé de votre journée et activé vos modules :
    il ne vous reste qu’à le personnaliser.
</p>

{{-- Les identifiants : la raison d'être de cet e-mail. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#FCFAF7;border:1px solid #E7E1D7;border-radius:12px;margin:0 0 8px;">
    <tr>
        <td style="padding:20px 22px;">
            <p style="margin:0 0 14px;font-size:12px;font-weight:600;letter-spacing:1.4px;
                      text-transform:uppercase;color:#A87C46;">
                Vos identifiants
            </p>

            <p style="margin:0 0 4px;font-size:12.5px;color:#6E6E85;">Adresse de connexion</p>
            <p style="margin:0 0 14px;font-size:15px;color:#1B1B2F;font-weight:600;">
                {{ $commande->email }}
            </p>

            @if ($motDePasse)
                <p style="margin:0 0 4px;font-size:12.5px;color:#6E6E85;">Mot de passe provisoire</p>
                <p style="margin:0;font-size:19px;color:#1B1B2F;font-weight:700;
                          font-family:Consolas,Menlo,monospace;letter-spacing:1px;">
                    {{ $motDePasse }}
                </p>
            @else
                <p style="margin:0;font-size:14px;color:#3A3A52;">
                    Vous aviez déjà un compte : utilisez votre mot de passe habituel.
                </p>
            @endif
        </td>
    </tr>
</table>

@if ($motDePasse)
    <p style="margin:0 0 20px;font-size:12.5px;color:#6E6E85;">
        Changez-le dès votre première connexion. Si vous le perdez,
        <a href="{{ $urlOubli }}" style="color:#A87C46;">demandez-en un nouveau</a>.
    </p>
@endif

<x-courriel::partials.bouton :url="$urlEspace" libelle="Ouvrir mon espace" />

<p style="margin:0 0 6px;font-size:13px;color:#6E6E85;">L’adresse de votre site, à partager à vos invités :</p>
<p style="margin:0 0 26px;">
    <a href="{{ $urlSite }}" style="color:#A87C46;word-break:break-all;">{{ $urlSite }}</a>
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="border-top:1px solid #E7E1D7;">
    <tr>
        <td style="padding-top:22px;">
            <p style="margin:0 0 10px;font-size:14px;font-weight:600;color:#1B1B2F;">
                Vos trois prochaines étapes
            </p>
            <p style="margin:0 0 6px;font-size:14px;">1. Renseigner les lieux et les horaires de la journée.</p>
            <p style="margin:0 0 6px;font-size:14px;">2. Raconter votre histoire et remplir vos pages.</p>
            <p style="margin:0;font-size:14px;">3. Publier votre site, puis partager le lien.</p>
        </td>
    </tr>
</table>

<x-slot:apresPied>
    Vous recevez cet e-mail parce que vous venez de créer un mariage sur Solen.
    Conservez-le : il contient vos identifiants.
</x-slot:apresPied>

</x-courriel::layout>
