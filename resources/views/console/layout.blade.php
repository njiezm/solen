<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titre', 'Console') — Solen</title>
    <meta name="robots" content="noindex, nofollow">

    <link rel="icon" href="{{ asset('images/solen/mark.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/solen.css') }}">
    <link rel="stylesheet" href="{{ asset('css/espace.css') }}">
    <link rel="stylesheet" href="{{ asset('css/espace-compat.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="espace">

<div class="espace-shell">

    <aside class="espace-nav">
        <a href="{{ route('console.index') }}" class="logo">
            @include('solen.partials.mark')
            <span class="logo-word">solen</span>
        </a>

        <div class="espace-event">
            <div class="nom">Console</div>
            <div class="meta">{{ auth()->user()->email }}</div>
            <span class="formule">Équipe Solen</span>
        </div>

        <nav class="espace-menu">
            <span class="titre">Plateforme</span>
            <a href="{{ route('console.index') }}" class="{{ request()->routeIs('console.index') || request()->routeIs('console.mariage*') ? 'actif' : '' }}">
                <i class="fa-solid fa-heart" aria-hidden="true"></i> Mariages
            </a>
            <a href="{{ route('console.themes') }}" class="{{ request()->routeIs('console.theme*') ? 'actif' : '' }}">
                <i class="fa-solid fa-palette" aria-hidden="true"></i> Thèmes
            </a>

            <a href="{{ route('console.facturation') }}" class="{{ request()->routeIs('console.facturation*') ? 'actif' : '' }}">
                <i class="fa-solid fa-file-invoice" aria-hidden="true"></i> Facturation
            </a>

            <span class="titre">Raccourcis</span>
            <a href="{{ route('console.mariage.creer') }}">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Nouveau mariage
            </a>
            <a href="{{ route('solen.landing') }}" target="_blank" rel="noopener">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> La vitrine
            </a>
        </nav>

        <div class="espace-pied">
            <form method="POST" action="{{ route('auth.logout') }}">
                @csrf
                <button type="submit">
                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Se déconnecter
                </button>
            </form>
        </div>
    </aside>

    <main class="espace-main">
        <header class="espace-entete">
            <h1>@yield('titre', 'Console')</h1>
            @hasSection('chapeau')
                <p>@yield('chapeau')</p>
            @endif
        </header>

        @if (session('ok'))      <p class="alerte alerte--ok">{{ session('ok') }}</p> @endif
        @if (session('erreur'))  <p class="alerte alerte--erreur">{{ session('erreur') }}</p> @endif
        @if ($errors->any())     <p class="alerte alerte--erreur">Le formulaire comporte des erreurs, voyez ci-dessous.</p> @endif

        @yield('contenu')
    </main>

</div>

</body>
</html>
