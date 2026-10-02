@php $brand = config('solen.brand'); @endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', $brand['name'] . ' — ' . $brand['baseline'])</title>
    <meta name="description" content="@yield('description', $brand['pitch'])">

    <link rel="icon" href="{{ asset('images/solen/mark.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('images/solen/mark.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/solen.css') }}">
    <link rel="stylesheet" href="{{ asset('css/apercu-theme.css') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@400;600&family=Montserrat:wght@400;600&family=Fraunces:opsz,wght@9..144,400;9..144,600&family=Inter:wght@400;600&family=Cormorant+Garamond:wght@400;600&family=Lora:wght@400;600&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $brand['name'] }}">
    <meta property="og:title" content="@yield('title', $brand['name'] . ' — ' . $brand['baseline'])">
    <meta property="og:description" content="@yield('description', $brand['pitch'])">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
</head>
<body>

<header class="nav" id="nav">
    <div class="wrap nav-inner">
        <a href="{{ route('solen.landing') }}" class="logo" aria-label="{{ $brand['name'] }}, accueil">
            @include('solen.partials.mark')
            <span class="logo-word">{{ strtolower($brand['name']) }}</span>
        </a>

        {{--
            Ancres absolues : ces liens apparaissent aussi sur la page de
            commande et sur les pages de confirmation, où les sections
            correspondantes n'existent pas. Un simple « #tarifs » n'y
            mènerait nulle part.
        --}}
        @php $vitrine = route('solen.landing'); @endphp

        <nav class="nav-links">
            <a href="{{ $vitrine }}#difference">La différence</a>
            <a href="{{ $vitrine }}#modules">Modules</a>
            <a href="{{ $vitrine }}#themes">Thèmes</a>
            <a href="{{ $vitrine }}#tarifs">Tarifs</a>
            <a href="{{ $vitrine }}#faq">Questions</a>
        </nav>

        <a href="{{ $vitrine }}#tarifs" class="btn btn-primary btn-sm">Créer mon mariage</a>
    </div>
</header>

<main>
    @yield('content')
</main>

<footer class="footer">
    <div class="wrap footer-inner">
        <div class="logo">
            @include('solen.partials.mark')
            <span class="logo-word">{{ strtolower($brand['name']) }}</span>
        </div>
        <nav>
            <a href="{{ route('solen.landing') }}#modules">Modules</a>
            <a href="{{ route('solen.landing') }}#tarifs">Tarifs</a>
            <a href="{{ route('solen.landing') }}#faq">Questions</a>
            <a href="{{ route('auth.login') }}">Connexion</a>
            <a href="mailto:{{ $brand['email'] }}">{{ $brand['email'] }}</a>
            <a href="{{ route('legal.mentions') }}">Mentions légales</a>
            <a href="{{ route('legal.cgv') }}">CGV</a>
            <a href="{{ route('legal.confidentialite') }}">Confidentialité</a>
        </nav>
        <span class="footer-editeur">© {{ date('Y') }} {{ $brand['name'] }} by
            <img src="{{ asset('images/njiezm/logo.svg') }}" alt="NJIEZM.FR" height="18"></span>
    </div>
</footer>

<script>
    // Le liseré du header n'apparaît qu'une fois la page défilée.
    const nav = document.getElementById('nav');
    const onScroll = () => nav.classList.toggle('is-stuck', window.scrollY > 8);
    onScroll();
    addEventListener('scroll', onScroll, { passive: true });
</script>

</body>
</html>
