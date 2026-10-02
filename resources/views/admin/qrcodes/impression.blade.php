{{--
    Planche prête à imprimer. Les dimensions sont en millimètres et la page
    est découpée en feuilles A4 : « Imprimer » ou « Enregistrer en PDF »
    depuis le navigateur donne un fichier exact, sans marge parasite.
--}}
@php
    $jetons = $event->jetons();
    $encre  = $couleur === 'noir' ? '#111111' : ($jetons['--c-fort'] ?? '#1B1B2F');
    $accent = $couleur === 'noir' ? '#111111' : ($jetons['--c-accent-texte'] ?? $encre);
    $feuilles = $qrs->chunk($gabarit['par_page']);
    $date = $event->dateLocale()?->translatedFormat('j F Y');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $gabarit['nom'] }} — {{ $event->nom }}</title>
    <meta name="robots" content="noindex">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@400;600&family=Montserrat:wght@400;600&family=Fraunces:opsz,wght@9..144,400;9..144,600&family=Inter:wght@400;600&family=Cormorant+Garamond:wght@400;600&family=Lora:wght@400;600&display=swap');

        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; background: #E9E7E3; }
        body {
            font-family: {!! $event->theme?->font_body ?? "'Inter', sans-serif" !!};
            color: {{ $encre }};
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .titre-display { font-family: {!! $event->policeTitre() ?? "'Fraunces', Georgia, serif" !!}; font-weight: 400; }

        .outils {
            position: sticky; top: 0; z-index: 2;
            display: flex; gap: .6rem; justify-content: center; align-items: center;
            padding: .8rem; background: #1B1B2F; color: #fff; font: 600 14px/1.2 system-ui, sans-serif;
        }
        .outils button, .outils a { padding: .55rem 1rem; border: 0; border-radius: 8px; background: #fff; color: #1B1B2F; font: inherit; text-decoration: none; cursor: pointer; }

        .feuille {
            width: 210mm; height: 297mm; margin: 8mm auto; background: #fff;
            display: grid; overflow: hidden; page-break-after: always; break-after: page;
            box-shadow: 0 4px 24px rgb(0 0 0 / .12);
        }
        .feuille:last-child { page-break-after: auto; break-after: auto; }

        .case { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; position: relative; }
        .case svg { display: block; width: 100%; height: auto; }
        .noms { margin: 0; line-height: 1.05; }
        .phrase { margin: 0; letter-spacing: .04em; }
        .petit { font-size: 9pt; letter-spacing: .18em; text-transform: uppercase; opacity: .75; margin: 0; }
        .logo { display: block; object-fit: contain; }
        .scan { font-size: 8.5pt; opacity: .7; margin: 0; }

        /* --- Affiche A4 --- */
        .feuille--affiche { grid-template: 1fr / 1fr; }
        .feuille--affiche .case { padding: 22mm 20mm; gap: 8mm; border: 3mm solid {{ $accent }}; margin: 10mm; }
        .feuille--affiche .noms { font-size: 44pt; }
        .feuille--affiche .phrase { font-size: 20pt; color: {{ $accent }}; }
        .feuille--affiche .qr { width: 118mm; }
        .feuille--affiche .logo { height: 22mm; }

        /* --- Cartes A6 (4 par feuille) --- */
        .feuille--cartes { grid-template: repeat(2, 1fr) / repeat(2, 1fr); }
        .feuille--cartes .case { padding: 10mm; gap: 4mm; border: .2mm dashed #C9C9C9; }
        .feuille--cartes .noms { font-size: 18pt; }
        .feuille--cartes .table-num { font-size: 26pt; margin: 0; color: {{ $accent }}; }
        .feuille--cartes .phrase { font-size: 10pt; }
        .feuille--cartes .qr { width: 58mm; }
        .feuille--cartes .logo { height: 12mm; }

        /* --- Chevalets (2 par feuille, pliés en deux) --- */
        .feuille--chevalets { grid-template: repeat(2, 1fr) / 1fr; }
        .chevalet { display: grid; grid-template: 1fr 1fr / 1fr; border-bottom: .2mm dashed #C9C9C9; }
        .chevalet .case { padding: 6mm 14mm; gap: 3mm; flex-direction: row; justify-content: space-between; text-align: left; }
        .chevalet .case + .case { border-top: .2mm dotted #9A9A9A; }
        .chevalet .case.retourne { transform: rotate(180deg); }
        .chevalet .texte { display: grid; gap: 2mm; }
        .chevalet .noms { font-size: 20pt; }
        .chevalet .table-num { font-size: 24pt; margin: 0; color: {{ $accent }}; }
        .chevalet .phrase { font-size: 10pt; }
        .chevalet .qr { width: 50mm; flex: none; }
        .chevalet .logo { height: 10mm; }

        /* --- Stickers (12 par feuille) --- */
        .feuille--stickers { grid-template: repeat(4, 1fr) / repeat(3, 1fr); padding: 8mm; gap: 4mm; }
        .feuille--stickers .case { border: .2mm solid #E3E3E3; border-radius: 4mm; padding: 4mm; gap: 1.5mm; }
        .feuille--stickers .qr { width: 42mm; }
        .feuille--stickers .phrase { font-size: 7.5pt; }

        @media print {
            html, body { background: #fff; }
            .outils { display: none; }
            .feuille { margin: 0; box-shadow: none; }
        }
    </style>
</head>
<body>

<div class="outils">
    <span>{{ $gabarit['nom'] }} · {{ $qrs->count() }} QR · {{ $feuilles->count() }} feuille{{ $feuilles->count() > 1 ? 's' : '' }} A4</span>
    <button onclick="window.print()">Imprimer ou enregistrer en PDF</button>
    <a href="{{ route('admin.qrcodes.index') }}">Retour</a>
</div>

@foreach ($feuilles as $feuille)
    <section class="feuille feuille--{{ $modele }}">
        @foreach ($feuille as $qr)
            @php $numero = preg_match('/^Table (\d+)$/', $qr['nom'], $m) ? $m[1] : null; @endphp

            @if ($modele === 'chevalets')
                <div class="chevalet">
                    @foreach ([true, false] as $retourne)
                        <div class="case {{ $retourne ? 'retourne' : '' }}">
                            <div class="texte">
                                @if ($logo)<img class="logo" src="{{ $logo }}" alt="">@endif
                                @if ($numero)<p class="table-num titre-display">Table {{ $numero }}</p>@endif
                                <p class="noms titre-display">{{ $event->nom }}</p>
                                <p class="phrase">{{ $qr['phrase'] }}</p>
                            </div>
                            <div class="qr">{!! $qr['svg'] !!}</div>
                        </div>
                    @endforeach
                </div>
            @elseif ($modele === 'stickers')
                <div class="case">
                    <div class="qr">{!! $qr['svg'] !!}</div>
                    <p class="phrase">{{ $qr['phrase'] }}</p>
                </div>
            @else
                <div class="case">
                    @if ($logo)<img class="logo" src="{{ $logo }}" alt="">@endif
                    @if ($modele === 'affiche' && $date)<p class="petit">{{ $date }}</p>@endif
                    @if ($numero && $modele === 'cartes')<p class="table-num titre-display">Table {{ $numero }}</p>@endif
                    <p class="noms titre-display">{{ $event->nom }}</p>
                    <div class="qr">{!! $qr['svg'] !!}</div>
                    <p class="phrase">{{ $qr['phrase'] }}</p>
                    <p class="scan">Scannez avec l’appareil photo de votre téléphone</p>
                </div>
            @endif
        @endforeach
    </section>
@endforeach

</body>
</html>
