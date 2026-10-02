@extends('auth.coquille')

@section('titre', 'Console Solen')
@section('sous-titre', 'Accès réservé à l’équipe Solen by NJIEZM.FR.')

@section('formulaire')
    <form method="POST" action="{{ route('admin.connexion') }}">
        @csrf

        <div class="field">
            <label for="email">Adresse e-mail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email') <span class="erreur">{{ $message }}</span> @enderror
        </div>

        <div class="field">
            <label for="password">Mot de passe</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
            @error('password') <span class="erreur">{{ $message }}</span> @enderror
        </div>

        <label class="remember">
            <input type="checkbox" name="remember" value="1"> Rester connecté
        </label>

        <button type="submit" class="btn btn-primary">Entrer dans la console</button>
    </form>

    <div class="liens">
        <a href="{{ route('mot-de-passe.demande') }}">Mot de passe oublié ?</a>
        <a href="{{ route('auth.login') }}">Vous êtes mariés ? Votre espace est ici</a>
    </div>
@endsection
