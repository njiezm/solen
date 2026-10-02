@extends('espace.layout')

@section('titre', 'Équipe')
@section('chapeau', 'Vous n’êtes pas seuls : invitez un témoin, une wedding planner ou un proche à vous aider. Chacun reçoit son propre accès.')

@section('contenu')

<div class="carte">
    <h2>Qui gère ce mariage</h2>
    <div class="liste">
        @foreach ($membres as $membre)
            <div class="ligne">
                <span class="icone"><i class="fa-solid {{ $membre->pivot->role === 'proprietaire' ? 'fa-heart' : 'fa-user' }}" aria-hidden="true"></i></span>
                <div class="corps">
                    <strong>{{ $membre->name }} @if ($membre->is(auth()->user())) <small>(vous)</small> @endif</strong>
                    <span>{{ $membre->email }} · {{ $roles[$membre->pivot->role] ?? $membre->pivot->role }}</span>
                </div>
                @if ($peutGerer && ! $membre->is(auth()->user()))
                    <div class="outils">
                        <form method="POST" action="{{ route('espace.equipe.retirer', $membre->id) }}"
                              onsubmit="return confirm('Retirer l’accès de {{ $membre->name }} ?')">
                            @csrf @method('DELETE')
                            <button class="mini mini--danger">Retirer</button>
                        </form>
                    </div>
                @endif
            </div>
        @endforeach

        <div class="ligne">
            <span class="icone"><i class="fa-solid fa-life-ring" aria-hidden="true"></i></span>
            <div class="corps">
                <strong>L’équipe Solen</strong>
                <span>Toujours là pour vous aider, et pour prendre en charge ce que vous lui confiez.</span>
            </div>
        </div>
    </div>
</div>

@if ($peutGerer)
    <div class="carte">
        <h2>Inviter quelqu’un</h2>
        <p class="carte-aide">La personne reçoit un e-mail pour choisir son mot de passe. Elle pourra tout modifier, sauf l’équipe.</p>

        <form method="POST" action="{{ route('espace.equipe.inviter') }}">
            @csrf
            <div class="grille-champs">
                <div class="champ">
                    <label for="name">Nom</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="Léa, témoin de Manon">
                </div>
                <div class="champ">
                    <label for="email">Adresse e-mail</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                </div>
                <div class="champ">
                    <label for="role">Rôle</label>
                    <select id="role" name="role">
                        <option value="collaborateur">Aide à l’organisation</option>
                        <option value="proprietaire">Marié·e (accès complet)</option>
                    </select>
                </div>
            </div>
            <div class="actions"><button class="btn btn-primary">Envoyer l’invitation</button></div>
        </form>
    </div>
@endif

@endsection
