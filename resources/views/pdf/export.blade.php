@php
    /*
     * Les exports d'un mariage, aux couleurs de son thème. Écrit pour DomPDF :
     * tableaux plutôt que flexbox, aucune variable CSS, images déjà
     * réduites et calées par App\Solen\Exports.
     */
    $t = $theme;
    $titreFont = $t['police'] ? "'Display'" : "'DejaVu Serif'";
    $date = $event->dateLocale()?->translatedFormat('j F Y');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>{{ $titre }} — {{ $event->nom }}</title>
<style>
    @if ($t['policeFichier'])
        @font-face { font-family: 'Display'; src: url("{{ str_replace('\\', '/', $t['policeFichier']) }}") format('truetype'); }
    @endif
    @page { margin: 16mm 15mm 18mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.5; color: {{ $t['encre'] }}; }
    .titre { font-family: {!! $titreFont !!}; font-weight: normal; color: {{ $t['encre'] }}; }
    .accent { color: {{ $t['accent'] }}; }
    .doux { color: {{ $t['doux'] }}; }
    .saut { page-break-after: always; }
    .entier { page-break-inside: avoid; }

    /* Pied, sur chaque page */
    .pied { position: fixed; bottom: -11mm; left: 0; right: 0; text-align: center; font-size: 7.5pt; color: {{ $t['doux'] }}; }

    /* Couverture */
    .couverture { text-align: center; padding-top: 40mm; position: relative; }
    .couverture .fleur { position: absolute; top: -16mm; left: -15mm; width: 52mm; }
    .couverture .prenoms { width: 150mm; }
    .couverture .noms { font-size: 44pt; }
    .couverture .filet { width: 30mm; height: .6mm; background: {{ $t['accent'] }}; margin: 10mm auto; }
    .couverture .export { font-size: 30pt; }
    .couverture .quand { margin-top: 6mm; font-size: 9pt; letter-spacing: 2.5pt; text-transform: uppercase; color: {{ $t['doux'] }}; }

    h2 { font-size: 26pt; margin: 0 0 6mm; }
    .bandeau { height: .6mm; width: 22mm; background: {{ $t['accent'] }}; margin: -3mm 0 7mm; }

    /* Photos : grille 2 × 2 */
    table.grille { width: 100%; border-collapse: separate; border-spacing: 4mm 5mm; margin: -5mm -4mm 0; }
    table.grille td { width: 50%; vertical-align: top; }
    table.grille img { width: 100%; border: .3mm solid {{ $t['bord'] }}; }
    .legende { font-size: 7.5pt; color: {{ $t['doux'] }}; margin-top: 1mm; }

    /* Livre d'or */
    .message { background: {{ $t['pale'] }}; border-left: 1.2mm solid {{ $t['accent'] }}; padding: 4mm 5mm; margin: 0 0 4mm; page-break-inside: avoid; }
    .message .texte { font-size: 11pt; margin: 0 0 2mm; }
    .message .qui { font-size: 8.5pt; color: {{ $t['accent'] }}; }

    /* Histoire */
    .etape { margin: 0 0 7mm; page-break-inside: avoid; }
    .etape .date { font-size: 8pt; letter-spacing: 2pt; text-transform: uppercase; color: {{ $t['accent'] }}; }
    .etape .nom { font-size: 20pt; margin: 1mm 0 2mm; }
    .etape img { width: 120mm; margin-top: 3mm; border: .3mm solid {{ $t['bord'] }}; }

    /* Jeux */
    table.classement { width: 100%; border-collapse: collapse; }
    table.classement th { background: {{ $t['encre'] }}; color: {{ $t['page'] }}; font-weight: normal; font-size: 8pt; letter-spacing: 1pt; text-transform: uppercase; padding: 2mm 3mm; text-align: left; }
    table.classement td { padding: 2mm 3mm; border-bottom: .3mm solid {{ $t['bord'] }}; }
    table.classement .num { text-align: right; }
    .podium td { background: {{ $t['pale'] }}; font-weight: bold; }
</style>
</head>
<body>

<div class="pied">{{ $event->nom }}@if ($date) · {{ $date }}@endif · {{ $titre }}</div>

{{-- ───────────────────────────────────────────────── Couverture ── --}}
<div class="couverture saut">
    @if ($t['fleur'])<img class="fleur" src="{{ $t['fleur'] }}" alt="">@endif
    @if ($t['prenoms'])
        <img class="prenoms" src="{{ $t['prenoms'] }}" alt="{{ $event->nom }}">
    @else
        <div class="titre noms">{{ $event->nom }}</div>
    @endif
    <div class="filet"></div>
    <div class="titre export">{{ $titre }}</div>
    <div class="quand">{{ collect([$date, $event->lieu_ville])->filter()->implode(' · ') }}</div>
</div>

{{-- ─────────────────────────────────────────────────── Histoire ── --}}
@if (! empty($histoire) && count($histoire))
    <h2 class="titre">Notre histoire</h2>
    <div class="bandeau"></div>
    @foreach ($histoire as $e)
        <div class="etape">
            @if ($e['date'])<div class="date">{{ $e['date'] }}</div>@endif
            <div class="titre nom">{{ $e['titre'] }}</div>
            <div>{!! nl2br(e($e['texte'])) !!}</div>
            @if ($e['image'])<img src="{{ $e['image'] }}" alt="">@endif
        </div>
    @endforeach
    <div class="saut"></div>
@endif

{{-- ─────────────────────────────────────────────────── Livre d'or ── --}}
@isset($messages)
    <h2 class="titre">Le livre d’or</h2>
    <div class="bandeau"></div>
    @forelse ($messages as $m)
        <div class="message">
            <p class="texte">« {{ $m->message }} »</p>
            <div class="qui">— {{ trim($m->participant?->prenom . ' ' . $m->participant?->nom) }}@if ($m->created_at), {{ $m->created_at->translatedFormat('j F Y') }}@endif</div>
        </div>
    @empty
        <p class="doux">Aucun message pour l’instant.</p>
    @endforelse
    @if (isset($photos) && count($photos))<div class="saut"></div>@endif
@endisset

{{-- ──────────────────────────────────────────────────── Photos ── --}}
@isset($photos)
    @if ($type !== 'souvenir' || count($photos))
        <h2 class="titre">{{ $type === 'photobooth' ? 'Le photobooth' : 'Les photos' }}</h2>
        <div class="bandeau"></div>
    @endif
    @if (count($photos))
        {{-- Quatre photos par page : deux lignes de deux. --}}
        @foreach ($photos->chunk(4) as $page)
            <table class="grille entier">
                @foreach ($page->chunk(2) as $ligne)
                    <tr>
                        @foreach ($ligne as $p)
                            <td>
                                <img src="{{ $p->image }}" alt="">
                                @if ($p->auteur)<div class="legende">{{ $p->auteur }}</div>@endif
                            </td>
                        @endforeach
                        @if ($ligne->count() === 1)<td></td>@endif
                    </tr>
                @endforeach
            </table>
            @unless ($loop->last)<div class="saut"></div>@endunless
        @endforeach
    @elseif ($type !== 'souvenir')
        <p class="doux">Aucune photo pour l’instant.</p>
    @endif
@endisset

{{-- ─────────────────────────────────────────────────────── Jeux ── --}}
@isset($classement)
    <h2 class="titre">Les jeux</h2>
    <div class="bandeau"></div>
    @if ($parJeu)
        <p class="doux">Participation :
            @foreach ($parJeu as $jeu => $n){{ $jeu }} ({{ $n }} joueur{{ $n > 1 ? 's' : '' }})@if (! $loop->last), @endif @endforeach
        </p>
    @endif
    @if (count($classement))
        <table class="classement">
            <tr><th style="width:12mm">Rang</th><th>Joueur</th><th class="num">Jeux</th><th class="num">Points</th></tr>
            @foreach ($classement as $l)
                <tr @class(['podium' => $l['rang'] <= 3])>
                    <td>{{ $l['rang'] }}</td>
                    <td>{{ trim($l['prenom'] . ' ' . $l['nom']) }}</td>
                    <td class="num">{{ $l['jeux'] }}</td>
                    <td class="num">{{ $l['points'] }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <p class="doux">Personne n’a encore joué.</p>
    @endif
@endisset

</body>
</html>
