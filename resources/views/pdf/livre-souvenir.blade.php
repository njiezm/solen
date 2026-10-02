<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $titre }} — {{ $event->nom }}</title>
    <style>
        /*
            Feuille de style pensée pour DomPDF, pas pour un navigateur :
            pas de flexbox, pas de grid, pas de variables CSS. Les mises en
            page reposent sur des tableaux et des marges, comme en 2005.
        */
        @page { margin: 22mm 18mm; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5pt;
            line-height: 1.55;
            color: #26262e;
        }

        h1, h2, h3 { color: #1b1b2f; margin: 0 0 .4em; font-weight: normal; }
        h1 { font-size: 26pt; }
        h2 { font-size: 16pt; border-bottom: 1px solid #ddd6c8; padding-bottom: .3em; margin-top: 0; }
        h3 { font-size: 11.5pt; }

        .saut { page-break-after: always; }
        .eviter-coupure { page-break-inside: avoid; }

        /* Couverture */
        .couverture { text-align: center; padding-top: 55mm; }
        .couverture .titre { font-size: 30pt; color: #1b1b2f; }
        .couverture .noms  { font-size: 17pt; color: #a87c46; margin-top: 6mm; }
        .couverture .date  {
            font-size: 10pt; letter-spacing: 3px; text-transform: uppercase;
            color: #6e6e85; margin-top: 4mm;
        }
        .couverture img { max-width: 90mm; max-height: 70mm; margin-bottom: 10mm; }
        .filet { width: 28mm; height: 2px; background: #c99b63; margin: 8mm auto; }

        /* Dédicace */
        .dedicace {
            font-size: 12pt; font-style: italic; text-align: center;
            color: #3a3a52; padding: 0 12mm;
        }

        /* Messages */
        .message { margin-bottom: 7mm; page-break-inside: avoid; }
        .message .texte { margin: 0 0 1.5mm; }
        .message .signature { font-size: 9pt; color: #a87c46; }

        /* Photos : un tableau, seule mise en page fiable sous DomPDF */
        table.photos { width: 100%; border-collapse: collapse; }
        table.photos td { width: 50%; padding: 2mm; vertical-align: top; }
        table.photos img { width: 100%; }
        .legende { font-size: 8pt; color: #6e6e85; margin-top: 1mm; }

        /* Déroulé */
        .etape { margin-bottom: 2.5mm; }
        .etape .heure { color: #a87c46; }

        .pied {
            text-align: center; font-size: 8.5pt; color: #6e6e85;
            margin-top: 12mm; padding-top: 4mm; border-top: 1px solid #e7e1d7;
        }

        @if ($apercu ?? false)
            /* À l'écran seulement : simuler la page pour se relire. */
            body { max-width: 170mm; margin: 0 auto; padding: 20mm 0; background: #fff; }
        @endif
    </style>
</head>
<body>

{{-- ─────────────────────────────────────────────── COUVERTURE ── --}}
<div class="couverture saut">
    @if ($couverture)
        <img src="{{ $couverture }}" alt="">
    @endif

    <div class="titre">{{ $titre }}</div>
    <div class="filet"></div>
    <div class="noms">{{ $event->nom }}</div>

    @if ($event->dateLocale())
        <div class="date">{{ $event->dateLocale()->translatedFormat('j F Y') }}</div>
    @endif

    @if ($event->lieu_ville)
        <div class="date">{{ $event->lieu_ville }}</div>
    @endif
</div>

{{-- ───────────────────────────────────────────────── DÉDICACE ── --}}
@if ($dedicace)
    <div class="saut">
        <div style="padding-top: 40mm">
            <div class="dedicace">{!! nl2br(e($dedicace)) !!}</div>
        </div>
    </div>
@endif

{{-- ────────────────────────────────────────────────── DÉROULÉ ── --}}
@if ($etapes->isNotEmpty())
    <h2>Le déroulé de la journée</h2>

    @foreach ($etapes as $moment => $liste)
        <h3 style="margin-top:5mm">{{ $moment }}</h3>
        @foreach ($liste as $etape)
            <div class="etape">
                <span class="heure">{{ $loop->iteration }}.</span> {{ $etape->titre }}
                @if ($etape->description)
                    <div style="font-size:9pt; color:#6e6e85">{{ $etape->description }}</div>
                @endif
            </div>
        @endforeach
    @endforeach

    <div class="saut"></div>
@endif

{{-- ────────────────────────────────────────────── LIVRE D'OR ── --}}
<h2>Vos mots</h2>

@if ($messages->isEmpty())
    <p style="color:#6e6e85">Aucun message n’a encore été laissé.</p>
@else
    @foreach ($messages as $message)
        <div class="message">
            <p class="texte">« {{ $message->message }} »</p>
            <p class="signature">
                — {{ $message->participant?->prenom }} {{ $message->participant?->nom }}
                @if ($message->created_at), {{ $message->created_at->translatedFormat('j F Y') }}@endif
            </p>
        </div>
    @endforeach
@endif

{{-- ─────────────────────────────────────────────────── PHOTOS ── --}}
@if ($photos->isNotEmpty())
    <div class="saut"></div>
    <h2>Vos photos</h2>

    <table class="photos">
        @foreach ($photos->chunk(2) as $paire)
            <tr class="eviter-coupure">
                @foreach ($paire as $photo)
                    <td>
                        @if ($photo->source)
                            <img src="{{ $photo->source }}" alt="">
                            @if ($photo->participant)
                                <div class="legende">{{ $photo->participant->prenom }} {{ $photo->participant->nom }}</div>
                            @endif
                        @endif
                    </td>
                @endforeach

                @if ($paire->count() === 1)
                    <td></td>
                @endif
            </tr>
        @endforeach
    </table>
@endif

<div class="pied">
    {{ $event->nom }} · Livre souvenir composé avec Solen
</div>

</body>
</html>
