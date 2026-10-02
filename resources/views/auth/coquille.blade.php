{{--
    Coquille commune aux écrans d'authentification : connexion, demande de
    nouveau mot de passe, réinitialisation. Un seul endroit à maintenir.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titre') — Solen</title>
    <meta name="robots" content="noindex, nofollow">

    <link rel="icon" href="{{ asset('images/solen/mark.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/solen.css') }}">

    <style>
        body { display: grid; place-items: center; min-height: 100vh; padding: var(--gutter); }

        .auth-card {
            width: 100%; max-width: 400px;
            background: var(--surface-card);
            border: 1px solid var(--line);
            border-radius: var(--r-lg);
            padding: 2.4rem 2rem;
            box-shadow: var(--shadow-md);
        }
        .auth-card .logo { justify-content: center; margin-bottom: 1.6rem; }
        .auth-card h1 { font-size: 1.55rem; text-align: center; margin-bottom: .4rem; }
        .auth-sub { text-align: center; font-size: .89rem; color: var(--ink-mute); margin-bottom: 1.8rem; }

        .field { margin-bottom: 1.1rem; }
        .field label {
            display: block; font-size: .82rem; font-weight: 600;
            color: var(--ink); margin-bottom: .35rem;
        }
        .field input {
            width: 100%; font: inherit; font-size: .95rem;
            padding: .7rem .9rem;
            border: 1px solid var(--line); border-radius: var(--r-sm);
            background: var(--surface); color: var(--ink);
        }
        .field input:focus { outline: 2px solid var(--accent); outline-offset: 1px; border-color: transparent; }
        .field .erreur { display: block; font-size: .8rem; color: #B3261E; margin-top: .35rem; }
        .field .aide   { display: block; font-size: .78rem; color: var(--ink-mute); margin-top: .35rem; }

        .remember { display: flex; align-items: center; gap: .5rem; font-size: .87rem; margin-bottom: 1.4rem; }
        .auth-card .btn { width: 100%; justify-content: center; }

        .statut {
            background: var(--live-wash); color: #1F5A43;
            border-radius: var(--r-sm); padding: .8rem .9rem;
            font-size: .85rem; margin-bottom: 1.2rem; text-align: center; line-height: 1.5;
        }

        .liens { display: grid; gap: .5rem; margin-top: 1.4rem; text-align: center; }
        .liens a { font-size: .84rem; color: var(--ink-mute); }
        .liens a:hover { color: var(--ink); }
        .retour { display: block; text-align: center; margin-top: 1.4rem; font-size: .84rem; color: var(--ink-mute); }
        .retour:hover { color: var(--ink); }
    </style>
</head>
<body>

<div class="auth-card">
    <a href="{{ route('solen.landing') }}" class="logo">
        @include('solen.partials.mark')
        <span class="logo-word">solen</span>
    </a>

    <h1>@yield('titre')</h1>
    <p class="auth-sub">@yield('sous-titre')</p>

    @if (session('statut') || session('status'))
        <p class="statut">{{ session('statut') ?? session('status') }}</p>
    @endif

    @yield('formulaire')
</div>

</body>
</html>
