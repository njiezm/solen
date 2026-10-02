@extends('espace.layout')

@section('titre', $page['nom'])
@section('chapeau', 'Ajoutez, réorganisez ou retirez les éléments de cette page.')
@section('retour', route('espace.pages'))
@section('retour-libelle', 'Toutes les pages')

@section('contenu')

<div class="carte">
    <h2>Ajouter</h2>
    <p class="carte-aide">Choisissez le type d’élément à ajouter à cette page.</p>

    <div class="actions" style="margin-top:0; padding-top:0; border:0">
        @foreach ($types as $type => $definition)
            <a href="{{ route('espace.bloc.creer', [$cle, $type]) }}" class="mini">
                <i class="fa-solid {{ $definition['icone'] }}" aria-hidden="true"></i>
                {{ $definition['nom'] }}
            </a>
        @endforeach
    </div>
</div>

<div class="carte">
    <h2>Contenu de la page</h2>

    @if ($blocs->isEmpty())
        <p class="vide">Cette page est vide. Ajoutez un premier élément ci-dessus.</p>
    @else
        <div class="liste">
            @foreach ($blocs as $bloc)
                <div class="ligne @unless($bloc->actif) inactive @endunless">
                    <span class="poignee">{{ $loop->iteration }}</span>

                    <span class="icone">
                        <i class="fa-solid {{ $bloc->definition()['icone'] ?? 'fa-align-left' }}" aria-hidden="true"></i>
                    </span>

                    <div class="corps">
                        <strong>{{ $bloc->etiquette() }}</strong>
                        <span>{{ $bloc->nomDuType() }} @unless($bloc->actif) — masqué @endunless</span>
                    </div>

                    <div class="outils">
                        @unless ($loop->first)
                            <form method="POST" action="{{ route('espace.bloc.deplacer', [$bloc->id, 'monter']) }}">
                                @csrf
                                <button class="mini" title="Monter" aria-label="Monter">↑</button>
                            </form>
                        @endunless

                        @unless ($loop->last)
                            <form method="POST" action="{{ route('espace.bloc.deplacer', [$bloc->id, 'descendre']) }}">
                                @csrf
                                <button class="mini" title="Descendre" aria-label="Descendre">↓</button>
                            </form>
                        @endunless

                        <a href="{{ route('espace.bloc.editer', $bloc->id) }}" class="mini">Modifier</a>

                        <form method="POST" action="{{ route('espace.bloc.supprimer', $bloc->id) }}"
                              onsubmit="return confirm('Supprimer « {{ addslashes($bloc->etiquette()) }} » ? Cette action est définitive.')">
                            @csrf
                            @method('DELETE')
                            <button class="mini mini--danger">Supprimer</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<div class="actions">
    <a href="{{ route('espace.pages') }}" class="btn btn-ghost">Toutes les pages</a>
    <a href="{{ route('landing') }}" target="_blank" rel="noopener" class="btn btn-primary pousse">Voir mon site</a>
</div>

@endsection
