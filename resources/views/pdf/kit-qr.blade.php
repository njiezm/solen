<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>QR codes — {{ $event->nom }}</title>
    @include('pdf.partials.papier')
    <style>
        table.qrs { width: 100%; border-collapse: separate; border-spacing: 4mm; }
        table.qrs td { width: 33%; text-align: center; vertical-align: top; border: .3mm solid #E7E1D7; border-radius: 2mm; padding: 4mm 2mm; }
        table.qrs img { width: 40mm; height: 40mm; }
        table.qrs .nom { font-family: 'DejaVu Serif', serif; font-size: 11pt; color: #1B1B2F; margin-top: 2mm; }
    </style>
</head>
<body>

@include('pdf.partials.entete', ['titreDoc' => 'Vos QR codes', 'sousTitreDoc' => $event->nom])

@if ($qrs->isEmpty())
    <p>Aucun QR code n’a encore été créé pour ce mariage. Le kit se crée en un clic dans « QR et impressions ».</p>
@else
    <p class="doux" style="margin-bottom:2mm">
        {{ $qrs->count() }} QR codes. Pour des affiches, cartes de table et chevalets prêts à découper, utilisez
        « QR et impressions » dans votre espace : chaque support y est mis en page à la bonne taille.
    </p>
    <table class="qrs">
        @foreach ($qrs->chunk(3) as $rangee)
            <tr>
                @foreach ($rangee as $qr)
                    <td>
                        <img src="{{ $qr['svg'] }}" alt="">
                        <div class="nom">{{ $qr['nom'] }}</div>
                    </td>
                @endforeach
                @for ($i = $rangee->count(); $i < 3; $i++)<td style="border:0"></td>@endfor
            </tr>
        @endforeach
    </table>
@endif

</body>
</html>
