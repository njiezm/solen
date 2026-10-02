@props(['url', 'libelle'])
{{-- Bouton en tableau : les <a> stylés ne sont pas cliquables sur toute leur
     surface dans Outlook. --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:26px 0;">
    <tr>
        <td align="center" style="background:#1B1B2F;border-radius:999px;">
            <a href="{{ $url }}"
               style="display:inline-block;padding:13px 30px;font-size:15px;font-weight:600;
                      color:#FCFAF7;text-decoration:none;">
                {{ $libelle }}
            </a>
        </td>
    </tr>
</table>
