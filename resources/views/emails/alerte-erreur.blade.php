<x-courriel::layout titre="Erreur en production">

<p style="margin:0 0 18px;font-size:20px;color:#B3261E;font-weight:600;">
    Une page a planté
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#FBEAE8;border-radius:10px;margin:0 0 18px;">
    <tr><td style="padding:16px 18px;">
        <strong style="color:#B3261E;font-size:14.5px;">{{ class_basename($erreur) }}</strong>
        <p style="margin:8px 0 0;font-size:14px;color:#3A3A52;">{{ $erreur->getMessage() }}</p>
    </td></tr>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#FCFAF7;border:1px solid #E7E1D7;border-radius:10px;margin:0 0 18px;">
    <tr><td style="padding:14px 18px;font-size:13.5px;line-height:1.85;color:#3A3A52;">
        <strong>Fichier</strong> {{ str_replace(base_path() . DIRECTORY_SEPARATOR, '', $erreur->getFile()) }}:{{ $erreur->getLine() }}<br>
        @if ($url) <strong>Adresse</strong> {{ $methode }} {{ $url }}<br> @endif
        @if ($user) <strong>Compte</strong> {{ $user }}<br> @endif
        <strong>Heure</strong> {{ now()->translatedFormat('j F Y à H\hi\ms') }}
    </td></tr>
</table>

<p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#1B1B2F;">Pile d’appels</p>
<pre style="margin:0 0 18px;padding:14px;background:#1B1B2F;color:#D8D3C8;border-radius:10px;
            font-size:11px;line-height:1.6;overflow-x:auto;white-space:pre-wrap;
            word-break:break-all;font-family:Consolas,Menlo,monospace;">{{ $trace }}</pre>

<p style="margin:0;font-size:12.5px;color:#6E6E85;">
    Un même incident ne déclenche qu’une alerte par heure, pour éviter la
    cascade d’e-mails si la page est très visitée.
</p>

</x-courriel::layout>
