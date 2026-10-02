@extends('auth.coquille')

@section('titre', 'Nouveau mot de passe')
@section('sous-titre', 'Choisissez-en un que vous retiendrez.')

@section('formulaire')
    <form method="POST" action="{{ route('mot-de-passe.reinitialiser') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="field">
            <label for="email">Adresse e-mail</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}"
                   required autocomplete="email">
            @error('email') <span class="erreur">{{ $message }}</span> @enderror
        </div>

        <div class="field">
            <label for="password">Nouveau mot de passe</label>
            <input id="password" name="password" type="password" required autocomplete="new-password">
            <span class="aide">Huit caractères au minimum.</span>
            @error('password') <span class="erreur">{{ $message }}</span> @enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Confirmer</label>
            <input id="password_confirmation" name="password_confirmation" type="password"
                   required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary">Changer mon mot de passe</button>
    </form>

    <a href="{{ route('auth.login') }}" class="retour">← Retour à la connexion</a>
@endsection
