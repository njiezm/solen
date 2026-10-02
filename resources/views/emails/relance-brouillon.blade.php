<x-courriel::layout
    titre="Votre site de mariage vous attend"
    preheader="Il ne reste que quelques éléments à renseigner.">

<p style="margin:0 0 18px;font-size:20px;color:#1B1B2F;font-weight:600;">
    {{ $event->nom }} est prêt à {{ $avancement['pourcentage'] }} %
</p>

<p style="margin:0 0 20px;">
    Votre site est encore en brouillon : vos invités n’y ont pas accès.
    Il ne manque pas grand-chose.
</p>

{{-- Barre de progression, en tableau : les <div> à largeur variable ne
     tiennent pas dans Outlook. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#F0EBE3;border-radius:999px;height:10px;margin:0 0 24px;">
    <tr>
        <td width="{{ $avancement['pourcentage'] }}%"
            style="background:#C99B63;border-radius:999px;height:10px;line-height:10px;font-size:0;">&nbsp;</td>
        <td style="font-size:0;line-height:10px;">&nbsp;</td>
    </tr>
</table>

@if ($restantes->isNotEmpty())
    <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#1B1B2F;">
        Ce qu’il reste à faire
    </p>

    @foreach ($restantes as $etape)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
               style="background:#FCFAF7;border:1px solid #E7E1D7;border-radius:10px;margin:0 0 8px;">
            <tr><td style="padding:12px 16px;">
                <strong style="color:#1B1B2F;font-size:14.5px;">{{ $etape['titre'] }}</strong><br>
                <span style="font-size:13px;color:#6E6E85;">{{ $etape['aide'] }}</span>
            </td></tr>
        </table>
    @endforeach
@endif

<x-courriel::partials.bouton :url="$urlEspace" libelle="Reprendre où j’en étais" />

<p style="margin:0;font-size:12.5px;color:#6E6E85;">
    C’est notre seul rappel : nous ne vous écrirons plus à ce sujet.
    Une question, un blocage ? Répondez simplement à cet e-mail.
</p>

</x-courriel::layout>
