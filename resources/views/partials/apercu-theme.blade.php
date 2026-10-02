{{--
    Aperçu réel d'un thème : les vraies paires de couleurs, la vraie police
    de titre, la vraie forme. Ce que le couple voit ici est ce que verront
    ses invités.

    Paramètres : $theme, $titre (les prénoms), $surcharges (facultatif).
--}}
@php
    $j = $theme->jetons($surcharges ?? []);
    $ornement = $theme->ornement();
@endphp
<span class="apercu-theme" style="
    --a-page: {{ $j['--c-page'] }}; --a-sur-page: {{ $j['--c-sur-page'] }}; --a-doux: {{ $j['--c-sur-page-doux'] }};
    --a-carte: {{ $j['--c-carte'] }}; --a-sur-carte: {{ $j['--c-sur-carte'] }}; --a-bord: {{ $j['--c-bord'] }};
    --a-fort: {{ $j['--c-fort'] }}; --a-sur-fort: {{ $j['--c-sur-fort'] }};
    --a-accent: {{ $j['--c-accent'] }}; --a-sur-accent: {{ $j['--c-sur-accent'] }}; --a-accent-texte: {{ $j['--c-accent-texte'] }};
    --a-titre: {{ $j['--theme-display'] }}; --a-texte: {{ $j['--theme-body'] }};
    --a-r-carte: {{ $j['--theme-r-carte'] }}; --a-r-bouton: {{ $j['--theme-r-bouton'] }};
    --a-lettrage: {{ $j['--theme-lettrage'] }}; --a-capitales: {{ $j['--theme-capitales'] }};">
    <span class="apercu-bandeau">
        <span class="apercu-noms">{{ $titre }}</span>
        @if ($ornement === 'filet')<span class="apercu-filet"></span>@elseif ($ornement === 'double')<span class="apercu-fleuron">❦</span>@endif
        <span class="apercu-date">Le grand jour</span>
    </span>
    <span class="apercu-corps">
        <span class="apercu-tuile"><i class="fa-solid fa-camera" aria-hidden="true"></i><span>Photobooth</span></span>
        <span class="apercu-tuile"><i class="fa-solid fa-book-bible" aria-hidden="true"></i><span>Livret</span></span>
        <span class="apercu-bouton">Entrer</span>
    </span>
</span>
