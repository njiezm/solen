@extends('solen.layout')

@php
    $brand    = config('solen.brand');
    $showcase = config('solen.showcase');
    $modules  = collect(config('solen.modules'))->groupBy('phase');
    $phases   = config('solen.phases');
    $plans    = config('solen.plans');
    $options  = config('solen.options');
    // Les univers viennent de la base : un thème créé depuis la console
    // apparaît ici sans toucher au code.
    $themes   = App\Models\Theme::publics()->get();
    $faq      = config('solen.faq');

    $chips = [
        'live'  => ['Disponible',  'chip-live'],
        'build' => ['En cours',    'chip-build'],
        'next'  => ['Bientôt',     'chip-next'],
    ];

    // Le mariage vitrine, s'il est publié : c'est le meilleur argument de
    // vente, un vrai site qu'on peut ouvrir et parcourir.
    $demo = App\Http\Controllers\CommandeController::demo();
@endphp

@section('content')

{{-- ══════════════════════════════════════════════════════════ HERO ══ --}}
<section class="hero">
    <div class="wrap hero-grid">
        <div>
            <p class="eyebrow">Application de mariage</p>

            <h1>Vos invités ne regardent pas votre mariage.<br>Ils le <em>vivent</em>.</h1>

            <p class="lead hero-lead">
                Les autres vous vendent un site qui annonce la date. {{ $brand['name'] }} accompagne
                chaque moment du jour J — le programme, le livret de cérémonie, les photos,
                les jeux — dans la poche de chacun de vos invités.
            </p>

            <div class="hero-cta">
                <a href="{{ route('commander', 'celebration') }}" class="btn btn-primary">
                    Créer mon mariage <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>

                @if ($demo)
                    <a href="{{ route('landing', $demo->slug) }}" class="btn btn-ghost" target="_blank" rel="noopener">
                        <i class="fa-solid fa-play" aria-hidden="true"></i> Visiter un mariage
                    </a>
                @else
                    <a href="#difference" class="btn btn-ghost">Voir ce que ça change</a>
                @endif
            </div>

            <p class="hero-note">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.58 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/>
                </svg>
                Paiement unique, sans abonnement · Aucune application à installer
            </p>
        </div>

        {{--
            Maquette calquée sur le vrai site invité : mêmes tuiles, mêmes
            libellés, même bandeau de statut. Quand un mariage de démonstration
            est publié, elle reprend ses vraies données.
        --}}
        @php
            $mockCouple = $demo?->nom ?? 'Clara & Maël';
            $mockDate   = $demo?->dateLocale()?->translatedFormat('j F Y') ?? '12 septembre 2026';
            $mockLieu   = $demo?->lieu_ville ?? 'Arcachon';
            $mockEtape  = 'Échange des consentements';
        @endphp

        <div class="phone-scene">
            <div class="phone" role="img" aria-label="Le site vu par un invité pendant la cérémonie">
                <div class="phone-screen">
                    <div class="phone-barre">
                        <span>20:41</span>
                        <span><i class="fa-solid fa-wifi"></i> <i class="fa-solid fa-battery-three-quarters"></i></span>
                    </div>

                    <div class="phone-top">
                        <div class="couple">{{ $mockCouple }}</div>
                        <div class="meta">{{ $mockDate }} · {{ $mockLieu }}</div>
                    </div>

                    <div class="phone-body">
                        <div class="live-strip">
                            <span class="pulse" aria-hidden="true"></span>
                            <span>
                                En ce moment
                                <strong>{{ $mockEtape }}</strong>
                            </span>
                        </div>

                        <div class="tile-grid">
                            <div class="tile"><i class="fa-solid fa-book-bible" aria-hidden="true"></i>Livret</div>
                            <div class="tile"><i class="fa-solid fa-camera-retro" aria-hidden="true"></i>Photobooth</div>
                            <div class="tile"><i class="fa-solid fa-images" aria-hidden="true"></i>Galerie</div>
                            <div class="tile"><i class="fa-solid fa-feather-pointed" aria-hidden="true"></i>Livre d’or</div>
                            <div class="tile"><i class="fa-solid fa-dice" aria-hidden="true"></i>Jeux</div>
                            <div class="tile"><i class="fa-solid fa-gift" aria-hidden="true"></i>Cagnotte</div>
                            <div class="tile"><i class="fa-solid fa-utensils" aria-hidden="true"></i>Menu</div>
                            <div class="tile"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>Pratique</div>
                            <div class="tile"><i class="fa-solid fa-dove" aria-hidden="true"></i>Hommage</div>
                        </div>

                        <div class="phone-bandeau">
                            <i class="fa-solid fa-shirt" aria-hidden="true"></i>
                            Tenue de cocktail
                        </div>
                    </div>
                </div>
            </div>

            @if ($demo)
                <a href="{{ route('landing', $demo->slug) }}" class="phone-lien" target="_blank" rel="noopener">
                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                    Ce n’est pas une image : ouvrez le vrai site
                </a>
            @endif
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════ PREUVE ══ --}}
<div class="proof">
    <div class="wrap proof-inner">
        <div class="proof-label">
            <p class="eyebrow">Éprouvé en conditions réelles</p>
            <p>{{ $showcase['couple'] }} — {{ $showcase['lieu'] }}, {{ $showcase['date'] }}.</p>
        </div>
        <div class="proof-stats">
            @foreach ($showcase['stats'] as $stat)
                <div class="proof-stat">
                    <div class="num">{{ $stat['value'] }}</div>
                    <div class="cap">{{ $stat['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════ DIFFÉRENCE ══ --}}
<section id="difference">
    <div class="wrap">
        <div class="section-head">
            <p class="eyebrow">La différence</p>
            <h2>Tout le monde soigne l’avant. Personne ne s’occupe du jour même.</h2>
            <p class="lead">
                Le jour J, votre beau site de mariage ne sert plus à rien. C’est précisément
                le moment où vos invités ont le plus besoin de savoir où aller, quoi faire
                et ce qui se passe.
            </p>
        </div>

        <div class="split">
            <div class="split-card them">
                <h3>Une plateforme de mariage classique</h3>
                <ul class="split-list">
                    <li><i class="fa-solid fa-minus" aria-hidden="true"></i> Un joli site, consulté deux fois avant le mariage</li>
                    <li><i class="fa-solid fa-minus" aria-hidden="true"></i> Un formulaire de réponse, et c’est tout</li>
                    <li><i class="fa-solid fa-minus" aria-hidden="true"></i> Le jour J, plus rien : tout se passe hors ligne</li>
                    <li><i class="fa-solid fa-minus" aria-hidden="true"></i> Les photos des invités éparpillées dans dix groupes WhatsApp</li>
                    <li><i class="fa-solid fa-minus" aria-hidden="true"></i> Un livret de messe imprimé en 120 exemplaires, jeté le soir même</li>
                    <li><i class="fa-solid fa-minus" aria-hidden="true"></i> Une commission prélevée sur votre cagnotte</li>
                </ul>
            </div>

            <div class="split-card us">
                <h3>{{ $brand['name'] }}</h3>
                <ul class="split-list">
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Une application vivante, ouverte toute la journée</li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Le programme de la journée : chaque lieu, chaque horaire, l’itinéraire en un geste</li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Le livret de cérémonie sur le téléphone, à jour, sans rien imprimer</li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Toutes les photos des invités au même endroit</li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Photobooth, jeux et classement pour animer la soirée</li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Cagnotte sans commission, versée sur votre compte</li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════ SPOTLIGHTS ══ --}}
<section class="dark">
    <div class="wrap">
        <div class="section-head">
            <p class="eyebrow">Le jour J</p>
            <h2>Trois choses que vous ne trouverez nulle part ailleurs.</h2>
        </div>

        <div class="spot-grid">
            <article class="spot">
                <div class="spot-num">01</div>
                <h3>Le programme de la journée</h3>
                <p>
                    Chaque moment a sa page : le lieu, l’heure d’arrivée, l’adresse et
                    l’itinéraire. Plus personne ne demande « c’est où, le vin d’honneur ? ».
                </p>
                <ul>
                    <li>Mairie, cérémonie, vin d’honneur, dîner, soirée, brunch</li>
                    <li>Compte à rebours jusqu’au prochain moment</li>
                    <li>Rien à piloter le jour J : tout est prêt d’avance</li>
                </ul>
            </article>

            <article class="spot">
                <div class="spot-num">02</div>
                <h3>Le livret de cérémonie</h3>
                <p>
                    Déposez votre livret en PDF, tel que vous l’auriez imprimé. Vos invités
                    le feuillettent sur leur téléphone, page par page, d’un simple geste
                    du doigt.
                </p>
                <ul>
                    <li>Civil, laïque, catholique, évangélique</li>
                    <li>Une liseuse plein écran, lisible même dans une église sombre</li>
                    <li>Aucune impression, aucun exemplaire perdu</li>
                </ul>
            </article>

            <article class="spot">
                <div class="spot-num">03</div>
                <h3>Le photobooth sans borne</h3>
                <p>
                    Vos invités scannent un QR code, la caméra s’ouvre, les cadres sont
                    à vos couleurs. Les photos arrivent aussitôt dans la galerie
                    du mariage.
                </p>
                <ul>
                    <li>Aucune application, aucun matériel à louer</li>
                    <li>Cadres, filtres et stickers à votre thème</li>
                    <li>Mode borne sur tablette si vous préférez</li>
                </ul>
            </article>
        </div>
    </div>
</section>

{{-- ═════════════════════════════════════════════════════ MODULES ══ --}}
<section id="modules">
    <div class="wrap">
        <div class="section-head">
            <p class="eyebrow">Le catalogue</p>
            <h2>Vous composez votre mariage, module par module.</h2>
            <p class="lead">
                Un mariage civil de trente personnes n’a pas les mêmes besoins qu’une
                célébration de deux jours. Activez ce qui vous sert, ignorez le reste.
            </p>
        </div>

        @foreach ($phases as $key => $phase)
            @continue(! isset($modules[$key]))
            <div class="phase-block">
                <div class="phase-head">
                    <h3>{{ $phase['nom'] }}</h3>
                    <p>{{ $phase['desc'] }}</p>
                </div>

                <div class="mod-grid">
                    @foreach ($modules[$key] as $mod)
                        @php [$chipLabel, $chipClass] = $chips[$mod['statut']]; @endphp
                        <article class="mod @if(! empty($mod['star'])) is-star @endif">
                            <span class="chip {{ $chipClass }}">{{ $chipLabel }}</span>
                            <div class="mod-icon"><i class="fa-solid {{ $mod['icon'] }}" aria-hidden="true"></i></div>
                            <h4>{{ $mod['nom'] }}</h4>
                            <p>{{ $mod['desc'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Ce qui viendra plus tard est annoncé, jamais vendu. --}}
        <div class="legend" style="flex-direction:column; align-items:flex-start; gap:.9rem">
            <span style="font-weight:600; color:var(--ink)">Déjà prévu pour la suite</span>
            <div style="display:flex; flex-wrap:wrap; gap:.6rem">
                @foreach (config('solen.a_venir') as $futur)
                    <span class="chip chip-next" style="position:static; padding:.35rem .8rem">
                        <i class="fa-solid {{ $futur['icon'] }}" aria-hidden="true"></i> {{ $futur['nom'] }}
                    </span>
                @endforeach
            </div>
            <span style="font-size:.8rem">
                Ces modules ne sont pas facturés tant qu’ils ne sont pas livrés.
            </span>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════ THÈMES ══ --}}
<section id="themes" style="background: var(--surface-warm); border-block: 1px solid var(--line);">
    <div class="wrap">
        <div class="section-head center">
            <p class="eyebrow">Identité</p>
            <h2>Votre mariage, pas notre gabarit.</h2>
            <p class="lead">
                {{ $themes->count() }} univers prêts à l’emploi, ou vos propres couleurs. Le thème
                s’applique partout : le site, le livret, les cadres du photobooth,
                l’écran de salle et le livre souvenir.
            </p>
        </div>

        {{-- De vrais aperçus, avec le couple de démonstration : on voit la
             police, la forme et le caractère, pas seulement une couleur. --}}
        <div class="themes-vitrine">
            @foreach ($themes as $theme)
                <figure>
                    @include('partials.apercu-theme', ['theme' => $theme, 'titre' => 'Clara & Maël'])
                    <figcaption>{{ $theme->nom }}</figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════ TARIFS ══ --}}
<section id="tarifs">
    <div class="wrap">
        <div class="section-head center">
            <p class="eyebrow">Tarifs</p>
            <h2>Un paiement unique. Pas d’abonnement.</h2>
            <p class="lead">
                On ne se marie pas tous les mois. Vous payez une fois, pour votre mariage,
                et vous gardez vos souvenirs.
            </p>
        </div>

        <div class="plan-grid">
            @foreach ($plans as $plan)
                <div class="plan @if(! empty($plan['populaire'])) is-pop @endif">
                    <span class="plan-accroche">{{ $plan['accroche'] }}</span>
                    <h3>{{ $plan['nom'] }}</h3>
                    <p class="plan-desc">{{ $plan['desc'] }}</p>

                    <div class="plan-price">
                        {{ $plan['prix'] }} €
                    </div>

                    <ul class="plan-features">
                        @foreach ($plan['features'] as $feature)
                            <li><i class="fa-solid fa-check" aria-hidden="true"></i> {{ $feature }}</li>
                        @endforeach
                    </ul>

                    <a href="{{ route('commander', $plan['key']) }}"
                       class="btn {{ ! empty($plan['populaire']) ? 'btn-primary' : 'btn-ghost' }}">
                        Choisir {{ $plan['nom'] }}
                    </a>

                    @if ($plan['limite'])
                        <p class="plan-limite">{{ $plan['limite'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="options">
            <h4>À la carte</h4>
            <ul>
                @foreach ($options as $option)
                    <li><span>{{ $option['nom'] }}</span> <strong>{{ $option['prix'] }}</strong></li>
                @endforeach
            </ul>
        </div>

        <p class="price-note">
            La cagnotte est versée directement sur votre compte bancaire.
            {{ $brand['name'] }} ne prélève <strong>aucune commission</strong> — seuls s’appliquent
            les frais de Stripe.
        </p>
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════ FAQ ══ --}}
<section id="faq" style="background: var(--surface-warm); border-block: 1px solid var(--line);">
    <div class="wrap">
        <div class="section-head center">
            <p class="eyebrow">Questions</p>
            <h2>Ce qu’on nous demande le plus.</h2>
        </div>

        <div class="faq">
            @foreach ($faq as $item)
                <details @if($loop->first) open @endif>
                    <summary>{{ $item['q'] }}</summary>
                    <p>{{ $item['r'] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════ CTA FINAL ══ --}}
<section class="cta-final">
    <div class="wrap">
        <h2>Votre date est prise. Le reste, on s’en occupe.</h2>
        <p class="lead">
            Choisissez votre formule, et votre site est en ligne dans la minute :
            déroulé, livret et jeux préremplis, il ne vous reste qu’à personnaliser.
        </p>
        <div class="hero-cta">
            <a href="{{ route('commander', 'celebration') }}" class="btn btn-primary">
                Créer mon mariage <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
            <a href="#modules" class="btn btn-ghost">Revoir les modules</a>
        </div>
    </div>
</section>

@endsection
