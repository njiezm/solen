@php
    $avancement = app(\App\Solen\AvancementMariage::class)->pour($event);
    $qrSite = 'data:image/svg+xml;base64,' . base64_encode(\App\Solen\QrVisuel::svg(route('landing', $event->slug), $event, true, 'theme', 400));
    $proprio = $event->users()->wherePivot('role', 'proprietaire')->first();
    $accent = $event->jetons()['--c-accent-texte'] ?? '#A87C46';
    $fort = $event->jetons()['--c-fort'] ?? '#1B1B2F';
    $surFort = $event->jetons()['--c-sur-fort'] ?? '#FCFAF7';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Kit de bienvenue — {{ $event->nom }}</title>
    @include('pdf.partials.papier')
    <style>
        .couv { background: {{ $fort }}; color: {{ $surFort }}; padding: 16mm 12mm; text-align: center; border-radius: 3mm; margin-bottom: 9mm; }
        .couv h1 { color: {{ $surFort }}; font-family: 'DejaVu Serif', serif; font-size: 30pt; line-height: 1.1; }
        .couv .sur { font-size: 8pt; letter-spacing: 3pt; text-transform: uppercase; opacity: .8; margin-bottom: 4mm; }
        .couv .date { font-size: 11pt; margin-top: 4mm; letter-spacing: 1pt; }
        .etape { padding: 2.5mm 0; border-bottom: .3mm solid #E7E1D7; }
        .case { display: inline-block; width: 3.5mm; height: 3.5mm; border: .4mm solid {{ $accent }}; margin-right: 2mm; vertical-align: -.5mm; }
        .case.faite { background: {{ $accent }}; }
        .chip { display: inline-block; padding: .6mm 2mm; border-radius: 3mm; background: #E4F1EA; color: #2F7D5D; font-size: 7pt; margin-left: 2mm; }
    </style>
</head>
<body>

@include('pdf.partials.entete', ['titreDoc' => 'Kit de bienvenue', 'sousTitreDoc' => $event->nom])

<div class="couv">
    <div class="sur">Bienvenue chez Solen</div>
    <h1>{{ $event->nom }}</h1>
    @if ($event->dateLocale())<div class="date">{{ $event->dateLocale()->translatedFormat('l j F Y') }}@if ($event->lieu_ville) · {{ $event->lieu_ville }}@endif</div>@endif
</div>

<table class="parties">
    <tr>
        <td style="padding-right:4mm">
            <div class="encart">
                <div class="encart-titre">Votre site</div>
                <strong>{{ route('landing', $event->slug) }}</strong>
                <p class="doux" style="margin-top:2mm">Le lien à partager à vos invités, sur le faire-part, par message ou en QR code.</p>
                <div class="encart-titre" style="margin-top:4mm">Votre espace</div>
                <strong>{{ route('auth.login') }}</strong>
                @if ($proprio)<p class="doux" style="margin-top:2mm">Identifiant : {{ $proprio->email }}. Mot de passe oublié ? Le lien « Mot de passe oublié » vous en envoie un nouveau.</p>@endif
            </div>
        </td>
        <td style="width:46mm; text-align:center">
            <img src="{{ $qrSite }}" style="width:44mm; height:44mm" alt="">
            <p class="doux" style="font-size:7pt; margin-top:1mm">Scannez pour ouvrir votre site</p>
        </td>
    </tr>
</table>

<div class="corps">
    <h2>Vos premiers pas</h2>
    <p>Votre mariage est prêt à {{ $avancement['pourcentage'] }} %. Voici ce qu’il reste à faire, dans l’ordre conseillé.</p>
    @foreach ($avancement['etapes'] as $etape)
        <div class="etape">
            <span class="case {{ $etape['fait'] ? 'faite' : '' }}"></span>
            <strong>{{ $etape['titre'] }}</strong>
            @if ($etape['solen'] && ! $etape['fait'])<span class="chip">Solen s’en occupe</span>@endif
            <br><span class="doux" style="margin-left:5.5mm">{{ $etape['aide'] }}</span>
        </div>
    @endforeach

    @if ($event->accompagnement)
        <h2>Ce que Solen prépare pour vous</h2>
        <ul>
            @foreach ($event->accompagnement as $cle)
                <li>{{ \App\Models\Event::ACCOMPAGNEMENTS[$cle] ?? $cle }}</li>
            @endforeach
        </ul>
        <p>Vous pourrez tout relire et ajuster ensuite depuis votre espace.</p>
    @endif

    <h2>Le jour J, en trois gestes</h2>
    <ul>
        <li><strong>Posez un QR code par table</strong> : vos invités y trouvent le programme, les jeux et la galerie. Les cartes et chevalets sont prêts à imprimer dans « QR et impressions ».</li>
        <li><strong>Affichez le photobooth</strong> à l’entrée : une affiche A4 suffit, les photos arrivent seules dans votre galerie.</li>
        <li><strong>Ouvrez le livret à l’entrée de la cérémonie</strong> : une fois chargé, il se feuillette même si le réseau décroche.</li>
    </ul>

    <h2>Une question ?</h2>
    <p>
        Écrivez-nous à {{ config('solen.entreprise.email') }}@if (config('solen.entreprise.telephone')) ou appelez le {{ config('solen.entreprise.telephone') }}@endif.
        Nous répondons sous un jour ouvré, et en priorité la semaine du mariage.
    </p>
</div>

</body>
</html>
