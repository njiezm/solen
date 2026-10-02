@props(['titre' => null, 'preheader' => null])
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titre ?? config('solen.brand.name') }}</title>
    <!--[if mso]><style>body,table,td{font-family:Arial,sans-serif !important}</style><![endif]-->
</head>
{{--
    Mise en page en tableaux et styles en ligne : ni flexbox, ni grid, ni
    feuille externe. Outlook utilise le moteur de rendu de Word, et Gmail
    supprime les balises <style> dans certains contextes.
--}}
<body style="margin:0;padding:0;background:#F5F0E8;font-family:-apple-system,'Segoe UI',Helvetica,Arial,sans-serif;color:#3A3A52;">

@if ($preheader)
    {{-- Aperçu affiché dans la liste des messages, invisible à l'ouverture. --}}
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        {{ $preheader }}
        {{-- Empêche le client mail d'y accoler le début du corps. --}}
        &#8199;&#65279;&#847;&#8199;&#65279;&#847;&#8199;&#65279;&#847;&#8199;&#65279;&#847;&#8199;&#65279;&#847;
    </div>
@endif

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F5F0E8;">
    <tr>
        <td align="center" style="padding:28px 12px;">

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                   style="max-width:560px;background:#FFFFFF;border-radius:16px;overflow:hidden;">

                {{-- Bandeau --}}
                <tr>
                    <td style="background:#1B1B2F;padding:22px 28px;" align="center">
                        <span style="font-size:21px;font-weight:600;color:#FCFAF7;letter-spacing:-0.4px;">
                            solen
                        </span>
                        <span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:#C99B63;margin-left:5px;"></span>
                    </td>
                </tr>

                {{-- Corps --}}
                <tr>
                    <td style="padding:32px 28px;font-size:15px;line-height:1.62;">
                        {{ $slot }}
                    </td>
                </tr>

                {{-- Pied --}}
                <tr>
                    <td style="background:#FCFAF7;border-top:1px solid #E7E1D7;padding:20px 28px;
                               font-size:12px;line-height:1.6;color:#6E6E85;" align="center">
                        {{ config('solen.brand.name') }} — {{ config('solen.brand.baseline') }}<br>
                        <a href="mailto:{{ config('solen.brand.email') }}" style="color:#A87C46;text-decoration:none;">
                            {{ config('solen.brand.email') }}
                        </a>
                    </td>
                </tr>
            </table>

            @isset($apresPied)
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;">
                    <tr>
                        <td align="center" style="padding:14px 8px;font-size:11.5px;color:#8A8AA0;line-height:1.55;">
                            {{ $apresPied }}
                        </td>
                    </tr>
                </table>
            @endisset

        </td>
    </tr>
</table>

</body>
</html>
