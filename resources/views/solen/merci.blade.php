@extends('solen.layout')

@section('title', 'Votre mariage est prêt — Solen')

@section('content')

<section style="padding-block: clamp(3rem, 7vw, 5.5rem)">
    <div class="wrap" style="max-width: 620px">

        @if ($commande->honoree())

            <p class="eyebrow">C’est fait</p>
            <h1 style="font-size: clamp(2rem, 5vw, 3rem)">Votre mariage est en ligne.</h1>
            <p class="lead">
                {{ $commande->nom }} a désormais son espace. Nous avons déjà préparé
                votre déroulé et activé les modules de la formule {{ $commande->formule()?->nom }}.
            </p>

            @if ($identifiants)
                <div class="split-card us" style="margin-top: 2rem">
                    <h3>Vos identifiants</h3>
                    <p style="font-size:.9rem">
                        Notez-les maintenant : le mot de passe ne sera plus jamais affiché.
                        Vous pourrez le changer une fois connecté.
                    </p>
                    <ul class="split-list" style="margin-top:1.2rem">
                        <li>
                            <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                            <span><strong id="v-email">{{ $identifiants['email'] }}</strong></span>
                        </li>
                        <li>
                            <i class="fa-solid fa-key" aria-hidden="true"></i>
                            <span><strong id="v-mdp">{{ $identifiants['motDePasse'] }}</strong></span>
                        </li>
                    </ul>
                    <button type="button" class="btn btn-light btn-sm" style="margin-top:1.2rem" data-copier="v-mdp">
                        Copier le mot de passe
                    </button>
                </div>
            @else
                <p class="alerte" style="background:var(--accent-wash); color:var(--accent-deep); padding:.9rem 1.1rem; border-radius:12px; margin-top:1.5rem">
                    Ce compte existait déjà : connectez-vous avec votre mot de passe habituel.
                </p>
            @endif

            <div class="hero-cta" style="margin-top:2rem">
                <a href="{{ route('espace.index', $commande->event->slug) }}" class="btn btn-primary">
                    Ouvrir mon espace <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
                <a href="{{ route('landing', $commande->event->slug) }}" class="btn btn-ghost" target="_blank" rel="noopener">
                    Voir mon site
                </a>
            </div>

            <div class="carte" style="background:var(--surface-warm); border-radius:var(--r-lg); padding:1.4rem; margin-top:2.5rem">
                <h3 style="font-size:1rem; font-family:var(--body); font-weight:600">Vos trois prochaines étapes</h3>
                <ol style="font-size:.92rem; color:var(--ink-mute); padding-left:1.2rem; margin:.8rem 0 0">
                    <li>Renseigner les lieux et les horaires de votre journée.</li>
                    <li>Raconter votre histoire et remplir vos pages.</li>
                    <li>Publier votre site, puis partager le lien à vos invités.</li>
                </ol>
            </div>

        @elseif ($commande->statut === 'en_attente')

            <p class="eyebrow">Paiement en cours</p>
            <h1 style="font-size: clamp(2rem, 5vw, 3rem)">Nous attendons la confirmation.</h1>
            <p class="lead">
                Votre banque n’a pas encore confirmé le paiement. Cette page se
                met à jour toute seule — laissez-la ouverte quelques instants.
            </p>
            <p style="font-size:.88rem; color:var(--ink-mute)">
                Si rien ne se passe d’ici deux minutes, écrivez-nous à
                <a href="mailto:{{ config('solen.brand.email') }}">{{ config('solen.brand.email') }}</a>
                en mentionnant la référence <code>{{ $commande->uuid }}</code>.
            </p>
            <meta http-equiv="refresh" content="10">

        @else

            <p class="eyebrow">Commande</p>
            <h1 style="font-size: clamp(2rem, 5vw, 3rem)">Cette commande n’a pas abouti.</h1>
            <p class="lead">Aucun montant n’a été prélevé. Vous pouvez recommencer quand vous voulez.</p>
            <div class="hero-cta">
                <a href="{{ route('commander', $commande->plan) }}" class="btn btn-primary">Reprendre ma commande</a>
                <a href="{{ route('solen.landing') }}" class="btn btn-ghost">Retour à l’accueil</a>
            </div>

        @endif

    </div>
</section>

<script>
    document.querySelectorAll('[data-copier]').forEach((b) => {
        b.addEventListener('click', async () => {
            await navigator.clipboard.writeText(document.getElementById(b.dataset.copier).textContent.trim());
            const t = b.textContent; b.textContent = 'Copié';
            setTimeout(() => (b.textContent = t), 2000);
        });
    });
</script>

@endsection
