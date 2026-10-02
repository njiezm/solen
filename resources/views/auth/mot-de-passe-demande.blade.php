@extends('auth.coquille')

@section('titre', 'Mot de passe oublié')
@section('sous-titre', 'Nous vous enverrons un lien pour en choisir un nouveau.')

@section('formulaire')
    <form method="POST" action="{{ route('mot-de-passe.envoyer') }}">
        @csrf

        <div class="field">
            <label for="email">Adresse e-mail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}"
                   required autofocus autocomplete="email">
            @error('email') <span class="erreur">{{ $message }}</span> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Envoyer le lien</button>
    </form>

    <a href="{{ route('auth.login') }}" class="retour">← Retour à la connexion</a>
@endsection
