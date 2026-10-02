{{-- Page d'accès minimale : aux couleurs du mariage, sans menu ni contenu. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $event->nom }}</title>
    <link rel="icon" href="{{ asset('images/solen/mark.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="{{ asset('css/invite.css') }}?v={{ filemtime(public_path('css/invite.css')) }}" rel="stylesheet">
</head>
<body class="ornement-{{ $event->theme?->ornement() ?? 'filet' }}"
      @if ($style = $event->styleInline()) style="{{ $style }}" @endif>
    <main class="couverture">
        <div class="couverture-fond" aria-hidden="true"></div>
        <div class="couverture-contenu" style="padding-top:3rem">
            <p class="couverture-sur">{{ $event->dateLocale()?->translatedFormat('j F Y') ?? 'Notre mariage' }}</p>
            <h1 class="couverture-titre" style="font-size:clamp(2.2rem, 9vw, 4rem)">{{ $event->nom }}</h1>
            @yield('contenu')
        </div>
    </main>
</body>
</html>
