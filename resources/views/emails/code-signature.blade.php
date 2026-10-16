<x-courriel::layout
    titre="Votre code de signature"
    preheader="Code : {{ $code }} — valable {{ $minutes }} minutes.">

<p style="margin:0 0 18px;">
    Bonjour,<br><br>
    Voici le code qui vous permet de signer le {{ mb_strtolower($devis->libelle()) }}
    @if ($devis->objet) « {{ $devis->objet }} » @endif
    d’un montant de {{ \App\Models\DocumentCommercial::euros($devis->total_centimes) }} :
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#FCFAF7;border:1px solid #E7E1D7;border-radius:12px;margin:0 0 18px;">
    <tr>
        <td align="center" style="padding:22px;">
            <p style="margin:0;font-size:34px;font-weight:700;letter-spacing:10px;color:#1B1B2F;font-family:Menlo,Consolas,monospace;">{{ $code }}</p>
            <p style="margin:8px 0 0;font-size:13px;color:#6E6E85;">Valable {{ $minutes }} minutes</p>
        </td>
    </tr>
</table>

<p style="margin:0;color:#6E6E85;font-size:13px;">
    Si vous n’êtes pas à l’origine de cette demande, ignorez ce message : sans ce code, personne ne peut signer à votre place.<br>
    {{ config('solen.entreprise.nom_commercial') }}
</p>
</x-courriel::layout>
