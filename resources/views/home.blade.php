@extends('layout')
@section('content')

@php
    /**
     * L'accueil, une fois entré. Trois étages : qui, quand et où (avec le
     * compte à rebours), le programme de la journée, puis le reste du site
     * rangé par usage. Rien n'est écrit en dur : tout vient des modules
     * actifs et des moments déclarés.
     */
    $nav     = app(App\Solen\NavigationInvite::class);
    $groupes = $nav->groupes($event);

    $moments = $event->parts()->actives()->orderBy('ordre')->get();

    $jalons = $moments
        ->whereNotNull('debut_at')
        ->sortBy('debut_at')
        ->map(fn ($part) => ['nom' => $part->nom, 'debut' => $part->debut_at->toIso8601String()])
        ->values();

    $avecRebours = $event->aModule('compte')
        && $event->reglage('compte', 'afficher', true)
        && $jalons->isNotEmpty();

    $couverture = $event->reglage('site', 'photo_couverture');
    $dressCode  = $event->reglage('site', 'dress_code');
    $accueil    = $event->reglage('site', 'message_accueil');
    $lieu       = $moments->firstWhere('lieu_nom', '!=', null);

    // Les tuiles : tout sauf les moments, déjà présentés dans le programme.
    $tuiles = $groupes->except('jour')->map(fn ($g) => $g['entrees'])->flatten(1)
        // « Une pensée pour » peut rester discrète : dans le menu, pas sur l'accueil.
        ->reject(fn ($t) => $t['route'] === 'pensee.pour' && $event->reglage('hommage', 'discret'));
    $livret = $groupes->get('jour')['entrees'] ?? collect();
    $livret = $livret->firstWhere('route', 'livret');
@endphp

<section class="accueil-tete {{ $couverture ? 'accueil-tete--photo' : '' }}">
    @if ($couverture)
        <img class="accueil-tete-photo" aria-hidden="true" alt=""
             src="{{ Str::startsWith($couverture, ['http', '/']) ? $couverture : Storage::url($couverture) }}">
    @endif

    <h1>{{ $event->nom }}</h1>

    @if ($accueil)
        <p class="accueil-tete-mot">{{ $accueil }}</p>
    @endif

    <div class="accueil-infos">
        @if ($event->dateLocale())
            <span><i class="fa-regular fa-calendar" aria-hidden="true"></i>{{ $event->dateLocale()->translatedFormat('l j F Y') }}</span>
        @endif
        @if ($lieu || $event->lieu_ville)
            <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ collect([$lieu?->lieu_nom, $event->lieu_ville])->filter()->unique()->implode(', ') }}</span>
        @endif
    </div>

    @if ($avecRebours)
        <div class="rebours" id="rebours" aria-live="polite"
             data-fin="{{ $event->reglage('compte', 'message_apres', 'La fête est lancée ! Profitez de ce moment.') }}">
            <p class="rebours-libelle" id="rebours-libelle">&nbsp;</p>
            <div class="rebours-cases">
                <div class="rebours-case"><strong data-u="j">–</strong><span>jours</span></div>
                <div class="rebours-case"><strong data-u="h">–</strong><span>heures</span></div>
                <div class="rebours-case"><strong data-u="m">–</strong><span>min</span></div>
                <div class="rebours-case"><strong data-u="s">–</strong><span>s</span></div>
            </div>
        </div>
    @endif
</section>

@php
    $limiteRsvp = $event->reglage('rsvp', 'date_limite') ? \Illuminate\Support\Carbon::parse($event->reglage('rsvp', 'date_limite')) : null;
    $rsvpOuvert = $event->aModule('rsvp') && (! $limiteRsvp || $limiteRsvp->isFuture()) && (! $event->date_principale || $event->date_principale->isFuture());
@endphp
@if ($rsvpOuvert)
    <a href="{{ route('rsvp') }}" class="tuile tuile--vedette" style="flex-direction:row; align-items:center; margin-bottom:.5rem">
        <span class="pastille"><i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i></span>
        <span style="flex:1">
            <strong style="display:block">Répondez à l’invitation</strong>
            <span class="tuile-desc">{{ $limiteRsvp ? 'Avant le ' . $limiteRsvp->translatedFormat('j F Y') : 'Dites-nous si vous serez là' }}</span>
        </span>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    </a>
@endif

@if ($moments->isNotEmpty())
    <section class="section">
        <div class="section-tete">
            <h2>Le programme</h2>
        </div>

        <div class="bloc bloc--liste">
            <ul class="programme">
                @foreach ($moments as $moment)
                    <li>
                        <a href="{{ route('partie', $moment->cle) }}">
                            <span class="programme-heure">
                                {{ $moment->debut_at ? $event->enHeureLocale($moment->debut_at)->format('H\hi') : '—' }}
                            </span>
                            <span>
                                <span class="programme-nom">{{ $moment->nom }}</span>
                                @if ($moment->lieu_nom)
                                    <span class="programme-lieu">{{ $moment->lieu_nom }}</span>
                                @endif
                            </span>
                            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        @if ($livret)
            <a href="{{ route('livret') }}" class="tuile tuile--vedette" style="margin-top:.75rem; flex-direction:row; align-items:center">
                <span class="pastille"><i class="fa-solid fa-book-bible" aria-hidden="true"></i></span>
                <span style="flex:1">
                    <strong style="display:block">Le livret de cérémonie</strong>
                    <span class="tuile-desc">À feuilleter sur votre téléphone</span>
                </span>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </a>
        @endif
    </section>
@endif

@if ($tuiles->isNotEmpty())
    <section class="section">
        <div class="section-tete">
            <h2>Pour vous</h2>
        </div>

        <div class="tuiles">
            @foreach ($tuiles as $tuile)
                <a href="{{ route($tuile['route'], $tuile['params'] ?? []) }}" class="tuile">
                    <span class="pastille"><i class="fa-solid {{ $tuile['icone'] }}" aria-hidden="true"></i></span>
                    <strong>{{ $tuile['nom'] }}</strong>
                    <span class="tuile-desc">{{ $tuile['desc'] }}</span>
                </a>
            @endforeach
        </div>
    </section>
@endif

@if ($dressCode)
    <div class="bandeau-info">
        <i class="fa-solid fa-shirt" aria-hidden="true"></i>
        <span><strong>Code vestimentaire :</strong> {{ $dressCode }}</span>
    </div>
@endif

@if ($avecRebours)
    @push('scripts')
    <script>
    (() => {
        // Les jalons viennent des moments de la journée : on compte jusqu'au
        // prochain, puis jusqu'au suivant, jusqu'au dernier.
        const jalons  = @json($jalons);
        const bloc    = document.getElementById('rebours');
        const libelle = document.getElementById('rebours-libelle');
        const cases   = Object.fromEntries([...bloc.querySelectorAll('[data-u]')].map((c) => [c.dataset.u, c]));

        const tick = () => {
            const maintenant = Date.now();
            const suivant = jalons.find((j) => new Date(j.debut).getTime() > maintenant);

            if (!suivant) {
                bloc.innerHTML = `<p class="rebours-fin"></p>`;
                bloc.firstChild.textContent = bloc.dataset.fin;
                clearInterval(minuteur);
                return;
            }

            const ms = new Date(suivant.debut).getTime() - maintenant;
            libelle.textContent = `${suivant.nom} dans`;
            cases.j.textContent = Math.floor(ms / 86400000);
            cases.h.textContent = String(Math.floor(ms / 3600000) % 24).padStart(2, '0');
            cases.m.textContent = String(Math.floor(ms / 60000) % 60).padStart(2, '0');
            cases.s.textContent = String(Math.floor(ms / 1000) % 60).padStart(2, '0');
        };

        const minuteur = setInterval(tick, 1000);
        tick();
    })();
    </script>
    @endpush
@endif

@endsection
