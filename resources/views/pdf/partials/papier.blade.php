{{--
    Papier à en-tête Solen, commun à tous les documents transmis : factures,
    devis, avoirs, kit de bienvenue, documents légaux.

    Écrit pour DomPDF : tableaux plutôt que flexbox, aucune variable CSS.
--}}
<style>
    @page { margin: 16mm 16mm 22mm; }
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; line-height: 1.5; color: #2A2A3A; margin: 0; }
    .serif { font-family: 'DejaVu Serif', Georgia, serif; }
    h1, h2, h3 { color: #1B1B2F; font-weight: normal; margin: 0; }
    p { margin: 0 0 6pt; }
    a { color: #8D6D45; }
    .doux { color: #6E6E85; }
    .or { color: #A87C46; }

    /* En-tête */
    table.entete { width: 100%; border-collapse: collapse; margin-bottom: 9mm; }
    table.entete td { vertical-align: top; padding: 0; }
    .marque { font-family: 'DejaVu Serif', serif; font-size: 20pt; color: #1B1B2F; letter-spacing: .5pt; }
    .marque img { width: 13mm; height: 13mm; vertical-align: middle; margin-right: 2mm; }
    .marque-sous { font-size: 7.5pt; letter-spacing: 2pt; text-transform: uppercase; color: #A87C46; margin-top: 1mm; }
    .bandeau-doc { text-align: right; }
    .type-doc { font-family: 'DejaVu Serif', serif; font-size: 22pt; color: #1B1B2F; line-height: 1; }
    .numero-doc { font-size: 9pt; letter-spacing: 1pt; color: #A87C46; margin-top: 2mm; }

    .filet { height: 1.2mm; background: #1B1B2F; margin: 0 0 1px; }
    .filet-or { height: .5mm; background: #C99B63; margin: 0 0 8mm; }

    /* Encarts */
    table.parties { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 8mm; }
    table.parties td { width: 50%; vertical-align: top; padding: 0; }
    .encart { border: .3mm solid #E7E1D7; background: #FCFAF7; border-radius: 2mm; padding: 4mm 5mm; }
    .encart-titre { font-size: 7pt; letter-spacing: 1.6pt; text-transform: uppercase; color: #A87C46; margin-bottom: 2mm; }
    .encart strong { color: #1B1B2F; font-size: 10.5pt; }

    /* Lignes */
    table.lignes { width: 100%; border-collapse: collapse; margin-bottom: 6mm; }
    table.lignes th { background: #1B1B2F; color: #FCFAF7; font-weight: normal; font-size: 7.5pt; letter-spacing: 1pt;
                      text-transform: uppercase; padding: 2.5mm 3mm; text-align: left; }
    table.lignes td { padding: 3mm; border-bottom: .3mm solid #E7E1D7; vertical-align: top; }
    table.lignes .num { text-align: right; white-space: nowrap; }
    table.lignes .detail { display: block; font-size: 8pt; color: #6E6E85; margin-top: 1mm; }

    table.totaux { width: 60%; margin-left: 40%; border-collapse: collapse; }
    table.totaux td { padding: 2mm 3mm; }
    table.totaux .num { text-align: right; white-space: nowrap; width: 34mm; }
    table.totaux .total td { background: #1B1B2F; color: #FCFAF7; font-size: 11pt; }
    table.totaux .total .num { font-family: 'DejaVu Serif', serif; font-size: 13pt; }

    .mentions { margin-top: 8mm; font-size: 8pt; color: #4A4A5E; }
    .mentions p { margin-bottom: 3pt; }
    .tampon { display: inline-block; padding: 1.5mm 4mm; border: .5mm solid #2F7D5D; color: #2F7D5D; border-radius: 1.5mm;
              font-size: 8pt; letter-spacing: 1.5pt; text-transform: uppercase; }
    .tampon--annule { border-color: #B3261E; color: #B3261E; }

    /* Pied de page, sur chaque feuille */
    .pied { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 6.8pt; color: #8A8A9C; text-align: center;
            border-top: .3mm solid #E7E1D7; padding-top: 2mm; }

    /* Documents longs (légaux, kit) */
    .corps h2 { font-family: 'DejaVu Serif', serif; font-size: 13pt; margin: 6mm 0 2mm; padding-bottom: 1mm; border-bottom: .3mm solid #E7E1D7; }
    .corps h3 { font-size: 10.5pt; margin: 4mm 0 1.5mm; }
    .corps ul { margin: 0 0 6pt 4mm; padding: 0; }
    .corps table { width: 100%; border-collapse: collapse; margin: 3mm 0; font-size: 8pt; }
    .corps th, .corps td { border-bottom: .3mm solid #E7E1D7; padding: 1.5mm 2mm; text-align: left; vertical-align: top; }
    .corps .chapo { font-size: 10.5pt; color: #1B1B2F; }
    .a-completer { background: #FFF1C2; color: #6B4E00; }
    .saut { page-break-after: always; }
</style>
