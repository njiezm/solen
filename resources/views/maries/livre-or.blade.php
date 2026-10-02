@extends('espace.layout')

@section('titre', 'Livre d’or')
@section('chapeau', 'Les mots de vos invités, du plus ancien au plus récent.')
@section('retour', route('maries.index'))
@section('retour-libelle', 'Contributions')

@section('contenu')

@if ($messages->isEmpty())
    <div class="vide-illustre">
        <i class="fa-solid fa-feather-pointed" aria-hidden="true"></i>
        <p>Personne n’a encore écrit. Les premiers mots arrivent souvent le jour même.</p>
    </div>
@else
    <p class="carte-aide" style="margin-bottom:.9rem">
        {{ $messages->count() }} message{{ $messages->count() > 1 ? 's' : '' }}
    </p>

    <div class="liste">
        @foreach ($messages as $message)
            <div class="ligne" style="align-items:flex-start">
                <span class="icone"><i class="fa-solid fa-quote-left" aria-hidden="true"></i></span>
                <div class="corps">
                    <strong>{{ $message->participant?->prenom }} {{ $message->participant?->nom }}</strong>
                    <span style="white-space:normal; font-size:.93rem; color:var(--ink-soft); margin:.35rem 0">
                        {{ $message->message }}
                    </span>
                    <span>
                        {{ $message->created_at?->translatedFormat('j F Y à H\hi') }}
                        @unless ($message->publie) · <strong style="color:var(--accent-ink, #8D6D45)">en attente de validation</strong> @endunless
                    </span>
                </div>
                <div class="outils">
                    @unless ($message->publie)
                        <form method="POST" action="{{ route('maries.livreOr.publier', $message->id) }}">
                            @csrf
                            <button class="mini">Publier</button>
                        </form>
                    @endunless
                    <form method="POST" action="{{ route('maries.livreOr.supprimer', $message->id) }}"
                          onsubmit="return confirm('Retirer ce message du livre d’or ?')">
                        @csrf @method('DELETE')
                        <button class="mini">Retirer</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection
