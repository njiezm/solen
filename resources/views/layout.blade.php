@php
    /**
     * Gabarit du site invité. Aucun nom, aucune date, aucune couleur en dur :
     * tout vient du mariage courant et de son thème.
     *
     * Une seule navigation par écran : une barre fine en haut (les initiales
     * et un bouton « Menu »), et sur téléphone une barre basse au pouce. Le
     * menu complet s'ouvre dans un panneau, au lieu d'une piste horizontale
     * qu'il fallait faire défiler pour découvrir la moitié des pages.
     */
    $initiales = collect([$event->partenaire_1, $event->partenaire_2])
        ->filter()
        ->map(fn ($p) => mb_substr($p, 0, 1))
        ->implode(' & ') ?: mb_substr($event->nom, 0, 2);

    $partage = $event->reglage('site', 'photo_couverture');
    $partage = $partage
        ? (Str::startsWith($partage, ['http', '/']) ? $partage : Storage::url($partage))
        : asset('images/preview-mariage.png');

    $surCouverture = request()->routeIs('landing');
    $nav       = app(App\Solen\NavigationInvite::class);
    $groupes   = $nav->groupes($event);
    $estActive = fn (array $e) => request()->url() === route($e['route'], $e['params'] ?: []);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@hasSection('titre')@yield('titre') — @endif{{ $event->nom }}</title>
    <meta name="theme-color" content="{{ $event->jetons()['--c-page'] ?? '#FCFAF7' }}">

    <link rel="icon" href="{{ asset('images/solen/mark.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/solen/mark.svg') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="{{ asset('css/invite.css') }}?v={{ filemtime(public_path('css/invite.css')) }}" rel="stylesheet">

    <meta name="description" content="{{ $event->reglage('site', 'message_accueil') ?: 'Toutes les infos, les jeux et les souvenirs de notre mariage.' }}">

    {{-- Aperçu des liens partagés sur WhatsApp, Messenger, Facebook --}}
    <meta property="og:title" content="{{ $event->nom }} — Notre mariage">
    <meta property="og:description" content="{{ $event->reglage('site', 'message_accueil') ?: 'Le grand jour approche ! Retrouvez toutes les infos ici.' }}">
    <meta property="og:url" content="{{ route('landing') }}">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ $partage }}">
    <meta name="twitter:card" content="summary_large_image">
    @stack('tete')
</head>
{{-- Les jetons du thème, injectés en variables CSS. La classe d'ornement
     pilote ce que le CSS seul ne peut pas déduire : filet, fleuron ou rien. --}}
<body class="ornement-{{ $event->theme?->ornement() ?? 'filet' }}"
      @if ($style = $event->styleInline()) style="{{ $style }}" @endif>

<a class="visuellement-cache" href="#contenu">Aller au contenu</a>

<header class="entete {{ $surCouverture ? 'entete--sur-photo' : '' }}" id="entete">
    <div class="cadre entete-piste">
        <a class="entete-marque" href="{{ route('home') }}" aria-label="Accueil — {{ $event->nom }}">
            <span class="entete-initiales">{{ $initiales }}</span>
            @if ($event->dateLocale())
                <span class="entete-nom">{{ $event->dateLocale()->translatedFormat('j F Y') }}</span>
            @endif
        </a>

        @if ($groupes->isNotEmpty())
            <button type="button" class="entete-menu" data-ouvrir-menu aria-haspopup="dialog" aria-controls="panneau">
                <i class="fa-solid fa-bars" aria-hidden="true"></i> Menu
            </button>
        @endif
    </div>
</header>

@if ($groupes->isNotEmpty())
    <dialog class="panneau" id="panneau" aria-label="Menu du mariage">
        <div class="panneau-tete">
            <strong>{{ $event->nom }}</strong>
            <button type="button" class="panneau-fermer" data-fermer-menu aria-label="Fermer le menu">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <nav class="panneau-corps" aria-label="Pages du mariage">
            <a href="{{ route('home') }}" class="panneau-lien {{ request()->routeIs('home') ? 'actif' : '' }}">
                <i class="fa-solid fa-house" aria-hidden="true"></i>
                <span>Accueil</span>
            </a>

            @foreach ($groupes as $groupe)
                <div class="panneau-groupe">
                    <h2>{{ $groupe['nom'] }}</h2>
                    @foreach ($groupe['entrees'] as $entree)
                        <a href="{{ route($entree['route'], $entree['params'] ?: []) }}"
                           class="panneau-lien {{ $estActive($entree) ? 'actif' : '' }}">
                            <i class="fa-solid {{ $entree['icone'] }}" aria-hidden="true"></i>
                            <span>
                                {{ $entree['nom'] }}
                                @if (! empty($entree['desc']))<small>{{ $entree['desc'] }}</small>@endif
                            </span>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>
    </dialog>
@endif

@if (! $surCouverture)
    @include('partials.barre-basse')
@endif

<main id="contenu" class="{{ $surCouverture ? '' : 'contenu' }}">
    @if ($surCouverture)
        @yield('content')
    @else
        <div class="cadre @yield('largeur')">
            @yield('content')
        </div>
    @endif
</main>

@unless ($surCouverture)
    <footer class="pied">
        © {{ $event->dateLocale()?->year ?? date('Y') }} {{ $event->nom }} ·
        <a href="{{ route('solen.landing') }}">Propulsé par Solen</a>
    </footer>
@endunless

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
(() => {
    const panneau = document.getElementById('panneau');

    document.querySelectorAll('[data-ouvrir-menu]').forEach((b) =>
        b.addEventListener('click', () => panneau?.showModal()));
    document.querySelectorAll('[data-fermer-menu]').forEach((b) =>
        b.addEventListener('click', () => panneau?.close()));

    // Un clic sur le voile, hors du panneau, le referme.
    panneau?.addEventListener('click', (e) => { if (e.target === panneau) panneau.close(); });

    // Sur la couverture, la barre reprend un fond dès qu'on défile.
    const entete = document.getElementById('entete');
    if (entete?.classList.contains('entete--sur-photo')) {
        const suivre = () => entete.classList.toggle('defile', scrollY > 40);
        suivre();
        addEventListener('scroll', suivre, { passive: true });
    }
})();
</script>
@stack('scripts')
</body>
</html>
