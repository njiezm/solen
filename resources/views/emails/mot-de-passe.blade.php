<x-courriel::layout
    titre="Votre nouveau mot de passe"
    preheader="Un lien pour choisir un nouveau mot de passe Solen.">

<p style="margin:0 0 18px;font-size:20px;color:#1B1B2F;font-weight:600;">
    Choisissez un nouveau mot de passe
</p>

<p style="margin:0 0 8px;">
    Vous avez demandé à réinitialiser le mot de passe du compte
    <strong>{{ $user->email }}</strong>.
</p>

<p style="margin:0 0 4px;">
    Ce lien est valable <strong>{{ $minutes }} minutes</strong>, puis il expire.
</p>

<x-courriel::partials.bouton :url="$url" libelle="Choisir un mot de passe" />

<p style="margin:0 0 18px;font-size:13px;color:#6E6E85;">
    Si le bouton ne fonctionne pas, copiez cette adresse dans votre navigateur :<br>
    <span style="word-break:break-all;color:#A87C46;">{{ $url }}</span>
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="border-top:1px solid #E7E1D7;">
    <tr>
        <td style="padding-top:18px;font-size:13px;color:#6E6E85;">
            Vous n’êtes pas à l’origine de cette demande ? Ignorez cet e-mail :
            votre mot de passe actuel reste valable et personne n’a accès à
            votre espace.
        </td>
    </tr>
</table>

</x-courriel::layout>
