@php
    $requis = [
        'espace.jeux.index'   => 'jeux',
        'espace.invites'      => 'rsvp',
        'espace.livre'        => 'pdf',
        'espace.paiements'    => 'cagnotte',
        'admin.qrcodes.index' => 'qr',
        'maries.livreOr'      => 'livredor',
        'maries.galerie'      => 'mur',
    ];

    $sections = [
        'Mon mariage' => [
            ['espace.index',        'Tableau de bord',  'fa-gauge-high'],
            ['espace.informations', 'Informations',     'fa-circle-info'],
            ['espace.invites',      'Invités et RSVP',  'fa-envelope-open-text'],
            ['espace.pages',        'Pages et contenu', 'fa-file-lines'],
            ['espace.modules',      'Modules',          'fa-puzzle-piece'],
            ['espace.paiements',    'Cagnotte',         'fa-gift'],
            ['espace.equipe',       'Équipe',           'fa-user-group'],
            ['espace.formule',      'Ma formule',       'fa-crown'],
        ],
        'Le jour J' => [
            ['espace.programme',      'Programme', 'fa-timeline'],
            ['espace.jeux.index',     'Jeux',      'fa-dice'],
            ['admin.qrcodes.index',   'QR et impressions', 'fa-qrcode'],
        ],
        'Vos souvenirs' => [
            ['maries.livreOr',      'Livre d’or',     'fa-feather-pointed'],
            ['maries.galerie',      'Photos',         'fa-images'],
            ['maries.statistiques', 'Statistiques',   'fa-chart-simple'],
            ['espace.livre',        'Livre souvenir', 'fa-book'],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1B1B2F">
    <title>@yield('titre', 'Espace organisateur') — Solen</title>
    <meta name="robots" content="noindex, nofollow">

    <link rel="icon" href="{{ asset('images/solen/mark.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/solen.css') }}">
    <link rel="stylesheet" href="{{ asset('css/espace.css') }}">
    <link rel="stylesheet" href="{{ asset('css/apercu-theme.css') }}">
    {{-- Passerelle pour les écrans encore écrits en classes Bootstrap. --}}
    <link rel="stylesheet" href="{{ asset('css/espace-compat.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="espace">

{{-- Barre supérieure : mobile uniquement, elle porte le menu escamotable. --}}
<header class="espace-mobile">
    <button type="button" class="espace-burger" id="burger"
            aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="menu-lateral">
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
    </button>
    <span class="espace-mobile-titre">@yield('titre', 'Espace')</span>
    <a href="{{ route('landing') }}" target="_blank" rel="noopener" aria-label="Voir mon site">
        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
    </a>
</header>

<div class="espace-shell">

    <div class="espace-voile" id="voile" hidden></div>

    <aside class="espace-nav" id="menu-lateral">
        <a href="{{ route('espace.index') }}" class="logo">
            @include('solen.partials.mark')
            <span class="logo-word">solen</span>
        </a>

        <div class="espace-event">
            <div class="nom">{{ $event->nom }}</div>
            <div class="meta">
                {{ $event->dateLocale()?->translatedFormat('j F Y') ?? 'Date à définir' }}
                @if ($event->lieu_ville) · {{ $event->lieu_ville }} @endif
            </div>
            <span class="formule">{{ $event->formule()?->nom ?? $event->plan }}</span>

            @unless ($event->estPublie())
                <span class="formule" style="background:var(--build-wash); color:var(--build); margin-left:.3rem">
                    brouillon
                </span>
            @endunless
        </div>

        <nav class="espace-menu">
            @foreach ($sections as $titre => $liens)
                <span class="titre">{{ $titre }}</span>
                @foreach ($liens as [$route, $libelle, $icone])
                    @continue(! Route::has($route))
                    {{-- Pas de lien vers un module absent de la formule : il mènerait à une 404. --}}
                    @continue(isset($requis[$route]) && ! $event->aModule($requis[$route]))
                    <a href="{{ route($route) }}" class="{{ request()->routeIs($route) || ($route !== 'espace.index' && str_ends_with($route, '.index') && request()->routeIs(substr($route, 0, -6) . '.*')) ? 'actif' : '' }}">
                        <i class="fa-solid {{ $icone }}" aria-hidden="true"></i> {{ $libelle }}
                    </a>
                @endforeach
            @endforeach
        </nav>

        <div class="espace-pied">
            @if (auth()->user()?->estSuperAdmin())
                <a href="{{ route('console.index') }}">
                    <i class="fa-solid fa-sliders" aria-hidden="true"></i> Console Solen
                </a>
            @endif
            <a href="{{ route('landing') }}" target="_blank" rel="noopener">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Voir mon site
            </a>
            <form method="POST" action="{{ route('auth.logout') }}">
                @csrf
                <button type="submit">
                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Se déconnecter
                </button>
            </form>
        </div>
    </aside>

    <main class="espace-main">
        {{-- L'équipe Solen travaille ici pour le compte du couple : on le
             rappelle, pour ne jamais confondre avec son propre compte. --}}
        @if (auth()->user()?->estSuperAdmin())
            <div class="bandeau-solen">
                <span><i class="fa-solid fa-life-ring" aria-hidden="true"></i>
                    Vous gérez <strong>{{ $event->nom }}</strong> en tant qu’équipe Solen.
                    @if ($event->accompagnement)
                        Pris en charge : {{ collect($event->accompagnement)->map(fn ($c) => \App\Models\Event::ACCOMPAGNEMENTS[$c] ?? $c)->implode(' · ') }}.
                    @endif
                </span>
                <a href="{{ route('console.mariage', $event->slug) }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Fiche console</a>
            </div>
        @endif

        <header class="espace-entete">
            {{-- Lien de retour : une page de réglage ne doit jamais être un
                 cul-de-sac où seul le bouton du navigateur fait sortir. --}}
            @hasSection('retour')
                <a href="@yield('retour')" class="lien-retour">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    @yield('retour-libelle', 'Retour')
                </a>
            @endif

            <h1>@yield('titre', 'Tableau de bord')</h1>

            @hasSection('chapeau')
                <p>@yield('chapeau')</p>
            @endif
        </header>

        @if (session('ok'))     <p class="alerte alerte--ok">{{ session('ok') }}</p> @endif
        @if (session('erreur')) <p class="alerte alerte--erreur">{{ session('erreur') }}</p> @endif
        @if ($errors->any())
            <p class="alerte alerte--erreur">Certains champs n’ont pas pu être enregistrés, voyez ci-dessous.</p>
        @endif

        @yield('contenu')
    </main>

</div>

<script>
    // Menu escamotable sur petit écran.
    (() => {
        const burger = document.getElementById('burger');
        const menu   = document.getElementById('menu-lateral');
        const voile  = document.getElementById('voile');
        if (!burger) return;

        const basculer = (ouvert) => {
            document.body.classList.toggle('menu-ouvert', ouvert);
            burger.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
            voile.hidden = !ouvert;
        };

        burger.addEventListener('click', () => basculer(!document.body.classList.contains('menu-ouvert')));
        voile.addEventListener('click', () => basculer(false));

        // Échap referme, comme partout ailleurs.
        addEventListener('keydown', (e) => { if (e.key === 'Escape') basculer(false); });

        // Naviguer referme : sinon le menu masque la page d'arrivée.
        menu.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => basculer(false)));
    })();
</script>

</body>
</html>
