@extends('auth.coquille')

@section('titre', 'Espace organisateur')
@section('sous-titre', 'Réservé aux mariés et à leur équipe.')

@section('formulaire')
    <form method="POST" action="{{ route('auth.login') }}">
        @csrf

        <div class="field">
            <label for="email">Adresse e-mail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}"
                   required autofocus autocomplete="email">
            @error('email') <span class="erreur">{{ $message }}</span> @enderror
        </div>

        <div class="field">
            <label for="password">Mot de passe</label>
            <input id="password" name="password" type="password"
                   required autocomplete="current-password">
            @error('password') <span class="erreur">{{ $message }}</span> @enderror
        </div>

        <label class="remember">
            <input type="checkbox" name="remember" value="1"> Rester connecté
        </label>

        <button type="submit" class="btn btn-primary">Se connecter</button>
    </form>

    <div class="liens">
        <a href="{{ route('mot-de-passe.demande') }}">Mot de passe oublié ?</a>
        <a href="{{ route('solen.landing') }}">← Retour à l’accueil</a>
    </div>
@endsection
