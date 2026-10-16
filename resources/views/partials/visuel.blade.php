@php
    /*
     * Un visuel de titre (prénoms, date). Un SVG est intégré au document
     * plutôt qu'en <img> : ses textes peuvent alors utiliser la police du
     * faire-part, chargée par la page, et changer de couleur sur une photo.
     * Classes et identifiants sont préfixés pour ne pas déborder ailleurs.
     */
    $fichier = public_path($chemin);
    $svg = null;

    if (Str::endsWith($chemin, '.svg') && is_file($fichier)) {
        $prefixe = 'v' . substr(md5($chemin), 0, 6) . '-';
        $svg = preg_replace('/<\?xml[^>]*\?>/', '', (string) file_get_contents($fichier));
        $svg = preg_replace(['/cls-/', '/clippath/'], [$prefixe . 'c-', $prefixe . 'clip'], $svg);
        $svg = preg_replace('/<svg/', '<svg role="img" aria-label="' . e($alt) . '"', $svg, 1);
    }
@endphp

@if ($svg)
    @once
        @push('tete')
            <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fahkwang:wght@400;700&display=swap">
        @endpush
    @endonce
    <span class="visuel {{ $classe ?? '' }}">{!! $svg !!}</span>
@else
    <img class="visuel {{ $classe ?? '' }}" src="{{ asset($chemin) }}" alt="{{ $alt }}">
@endif
