@extends('acces.gabarit')

@section('contenu')
    <p class="couverture-mot">Ce site est réservé à nos invités. Saisissez le code qui figure sur votre faire-part.</p>

    <form method="POST" action="{{ route('acces.verifier') }}" class="acces-formulaire">
        @csrf
        <label class="visuellement-cache" for="code">Code d’accès</label>
        <input id="code" name="code" class="saisie" autocomplete="off" autocapitalize="none" required autofocus
               placeholder="Code d’accès" value="{{ old('code') }}">
        <button class="bouton bouton--plein">Entrer <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
    </form>

    @error('code')
        <p class="acces-erreur" role="alert">{{ $message }}</p>
    @enderror
@endsection
