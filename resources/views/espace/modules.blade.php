@extends('espace.layout')

@section('titre', 'Modules')
@section('chapeau', 'Activez ce qui vous sert, ignorez le reste. Un module désactivé disparaît du site.')

@section('contenu')

@foreach ($phases as $cle => $phase)
    @continue(! isset($modules[$cle]))

    <div class="carte">
        <h2>{{ $phase['nom'] }}</h2>
        <p class="carte-aide">{{ $phase['desc'] }}</p>

        <div class="liste">
            @foreach ($modules[$cle] as $module)
                @php $disponible = $module->statut === 'live'; @endphp

                <div class="ligne @unless($module->pivot->actif) inactive @endunless">
                    <span class="icone"><i class="fa-solid {{ $module->icone }}" aria-hidden="true"></i></span>

                    <div class="corps">
                        <strong>{{ $module->nom }}</strong>
                        <span>
                            {{ $module->description }}
                            @unless($disponible)
                                — <em>{{ $module->statut === 'build' ? 'en cours de développement' : 'prévu prochainement' }}</em>
                            @endunless
                        </span>
                    </div>

                    <div class="outils">
                        @if ($disponible && $module->reglable() && $module->pivot->actif)
                            <a href="{{ route('espace.modules.editer', $module->cle) }}" class="mini">Régler</a>
                        @endif

                        <form method="POST" action="{{ route('espace.modules.basculer', $module->cle) }}">
                            @csrf
                            <label class="interrupteur" title="{{ $disponible ? 'Activer ou désactiver' : 'Pas encore disponible' }}">
                                <input type="checkbox"
                                       @checked($module->pivot->actif)
                                       @disabled(! $disponible)
                                       onchange="this.form.submit()">
                                <span></span>
                            </label>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endforeach

@endsection
