@extends('layout')
@section('titre', 'Livre d’or')
@section('content')

<header class="page-tete">
    <p class="page-tete-sur">Vos mots</p>
    <h1>Livre d’or</h1>
    @if ($introduction)
        <p>{{ $introduction }}</p>
    @endif
</header>

<div class="cadre--etroit" style="margin-inline:auto">
    @if (session('success'))
        <div class="message message--succes" role="status">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('livreOr.store') }}" class="bloc">
        @csrf
        <h2 class="bloc-titre">
            <span class="pastille"><i class="fa-solid fa-pen-fancy" aria-hidden="true"></i></span>
            Signer le livre d’or
        </h2>

        <div class="grille-2">
            <label class="champ">
                <span>Prénom</span>
                <input type="text" name="prenom" class="saisie" value="{{ old('prenom') }}" autocomplete="given-name" required>
                @error('prenom') <span class="champ-erreur">{{ $message }}</span> @enderror
            </label>
            <label class="champ">
                <span>Nom</span>
                <input type="text" name="nom" class="saisie" value="{{ old('nom') }}" autocomplete="family-name" required>
                @error('nom') <span class="champ-erreur">{{ $message }}</span> @enderror
            </label>
        </div>

        <label class="champ">
            <span>Votre message</span>
            <textarea name="message" class="saisie" rows="5" maxlength="{{ $longueurMax }}" required
                      placeholder="Vos vœux, un conseil, un souvenir…">{{ old('message') }}</textarea>
            @error('message') <span class="champ-erreur">{{ $message }}</span> @enderror
        </label>

        <button class="bouton bouton--plein bouton--large">
            <i class="fa-solid fa-feather-pointed" aria-hidden="true"></i> Envoyer mon message
        </button>
    </form>
</div>

<section class="section">
    <div class="section-tete">
        <h2>Vos messages</h2>
        @if ($messages->isNotEmpty())
            <span class="doux">{{ $messages->count() }} {{ Str::plural('message', $messages->count()) }}</span>
        @endif
    </div>

    @if ($messages->isEmpty())
        <div class="vide">
            <i class="fa-regular fa-comment-dots" aria-hidden="true"></i>
            Soyez le premier à laisser un mot aux mariés.
        </div>
    @else
        <div class="mots">
            @foreach ($messages as $msg)
                <figure class="mot">
                    <blockquote>{{ $msg->message }}</blockquote>
                    <footer>
                        <strong>{{ $msg->participant?->prenom }} {{ $msg->participant?->nom }}</strong>
                        <span>{{ $msg->created_at->translatedFormat('j M Y') }}</span>
                    </footer>
                </figure>
            @endforeach
        </div>
    @endif
</section>

@endsection
