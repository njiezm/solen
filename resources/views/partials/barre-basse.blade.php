@php
    /**
     * La navigation du jour J sur téléphone : une barre basse, comme dans une
     * application. Accueil, trois raccourcis utiles, et le menu complet.
     */
    $nav        = app(App\Solen\NavigationInvite::class);
    $raccourcis = $nav->raccourcis($event, 4);

    $estActive = fn (array $e) => request()->url() === route($e['route'], $e['params'] ?: []);
@endphp

<nav class="barre-basse" aria-label="Accès rapide">
    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'actif' : '' }}">
        <i class="fa-solid fa-house" aria-hidden="true"></i>
        <span>Accueil</span>
    </a>

    @foreach ($raccourcis as $entree)
        <a href="{{ route($entree['route'], $entree['params'] ?: []) }}"
           class="{{ $estActive($entree) ? 'actif' : '' }}">
            <i class="fa-solid {{ $entree['icone'] }}" aria-hidden="true"></i>
            <span>{{ $entree['nom'] }}</span>
        </a>
    @endforeach

    <button type="button" data-ouvrir-menu aria-haspopup="dialog" aria-controls="panneau">
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
        <span>Menu</span>
    </button>
</nav>
