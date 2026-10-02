@extends('espace.layout')

@section('titre', 'Messages des invités')
@section('retour', route('espace.index'))
@section('retour-libelle', 'Tableau de bord')
@section('chapeau', 'Tous les mots laissés dans votre livre d’or, du plus récent au plus ancien.')

@section('contenu')

@if ($messages->isEmpty())
    <p class="vide">Personne n’a encore laissé de message.</p>
@else
    <div class="carte">
        <h2>{{ $messages->count() }} message{{ $messages->count() > 1 ? 's' : '' }}</h2>

        <div class="liste">
            @foreach ($messages as $message)
                <div class="ligne" style="align-items:flex-start">
                    <span class="icone"><i class="fa-solid fa-feather-pointed" aria-hidden="true"></i></span>

                    <div class="corps">
                        <strong>
                            {{ $message->participant?->prenom }} {{ $message->participant?->nom }}
                        </strong>
                        <span style="white-space:normal; font-size:.92rem; color:var(--ink-soft); margin:.35rem 0">
                            {{ $message->message }}
                        </span>
                        <span>{{ $message->created_at?->translatedFormat('j F Y à H\hi') }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

@endsection
