<x-courriel::layout titre="Le point du jour">

<p style="margin:0 0 22px;font-size:20px;color:#1B1B2F;font-weight:600;">
    Le point du {{ now()->translatedFormat('l j F') }}
</p>

@php
    $blocs = [
        ['Ventes',        $chiffres['ventes'],                                        $chiffres['ventes'] > 0],
        ['Recettes',      number_format($chiffres['recettes'], 0, ',', ' ') . ' €',   $chiffres['recettes'] > 0],
        ['Abandons',      $chiffres['abandons'],                                      false],
        ['Nouveaux sites',$chiffres['mariages'],                                      $chiffres['mariages'] > 0],
        ['Mis en ligne',  $chiffres['publies'],                                       $chiffres['publies'] > 0],
        ['En brouillon',  $chiffres['brouillons'],                                    false],
        ['Photos',        $chiffres['photos'],                                        false],
        ['Messages',      $chiffres['messages'],                                      false],
        ['Cagnottes',     number_format((float) $chiffres['cagnottes'], 0, ',', ' ') . ' €', false],
    ];
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 26px;">
    @foreach (collect($blocs)->chunk(3) as $ligne)
        <tr>
            @foreach ($ligne as [$libelle, $valeur, $bon])
                <td width="33%" style="padding:5px;" valign="top">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                           style="background:{{ $bon ? '#E4F1EA' : '#FCFAF7' }};border-radius:10px;">
                        <tr><td style="padding:14px 12px;text-align:center;">
                            <div style="font-size:22px;font-weight:700;color:{{ $bon ? '#1F5A43' : '#1B1B2F' }};">
                                {{ $valeur }}
                            </div>
                            <div style="font-size:11.5px;color:#6E6E85;margin-top:3px;">{{ $libelle }}</div>
                        </td></tr>
                    </table>
                </td>
            @endforeach
        </tr>
    @endforeach
</table>

@if ($prochains->isNotEmpty())
    <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#1B1B2F;">
        Mariages dans les quinze jours
    </p>

    @foreach ($prochains as $m)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
               style="background:{{ $m->statut === 'publie' ? '#FCFAF7' : '#FBEAE8' }};
                      border-radius:10px;margin:0 0 6px;">
            <tr><td style="padding:11px 15px;font-size:14px;">
                <strong style="color:#1B1B2F;">{{ $m->nom }}</strong>
                <span style="color:#6E6E85;"> — {{ $m->date_principale->translatedFormat('j F') }}</span>
                @if ($m->statut !== 'publie')
                    <span style="color:#B3261E;font-weight:600;"> · encore en brouillon</span>
                @endif
            </td></tr>
        </table>
    @endforeach

    <p style="margin:12px 0 0;font-size:12.5px;color:#6E6E85;">
        Un mariage encore en brouillon à moins de quinze jours mérite un appel.
    </p>
@endif

</x-courriel::layout>
