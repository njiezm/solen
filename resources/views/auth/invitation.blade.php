@extends('auth.coquille')

@section('titre', 'Bienvenue, ' . explode(' ', $compte->name)[0])
@section('sous-titre', 'Choisissez votre mot de passe pour accéder à l’espace de ' . $event->nom . '.')

@section('formulaire')
    {{-- L'action reprend l'adresse signée : la signature protège aussi l'envoi. --}}
    <form method="POST" action="{{ request()->fullUrl() }}">
        @csrf

        <div class="field">
            <label for="email">Votre identifiant</label>
            <input id="email" type="email" value="{{ $compte->email }}" disabled>
        </div>

        <div class="field">
            <label for="password">Mot de passe</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" autofocus>
            <span class="aide">Huit caractères au minimum.</span>
            @error('password') <span class="erreur">{{ $message }}</span> @enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Confirmer</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary">Entrer dans mon espace</button>
    </form>
@endsection
