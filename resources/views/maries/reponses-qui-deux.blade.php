@extends('espace.layout')

@section('titre', 'Qui de nous 2 ?')
@section('chapeau', 'Ce que vos invités ont répondu, question par question.')
@section('retour', route('maries.index'))
@section('retour-libelle', 'Contributions')

@section('contenu')

@if (empty($statsParQuestion))
    <div class="vide-illustre">
        <i class="fa-solid fa-question" aria-hidden="true"></i>
        <p>Personne n’a encore joué.</p>
    </div>
@else
    @foreach ($statsParQuestion as $stat)
        @php
            $a = $stat['reponses_gilles'] ?? 0;
            $b = $stat['reponses_maeva'] ?? 0;
            $total = max($a + $b, 1);
        @endphp

        <div class="carte">
            <h2 style="font-size:.98rem">{{ $stat['question'] }}</h2>
            <p class="carte-aide">La bonne réponse était <strong>{{ $stat['bonne_reponse'] }}</strong>.</p>

            <div style="display:grid; gap:.6rem">
                @foreach ([[$event->partenaire_2 ?: 'Lui', $a], [$event->partenaire_1 ?: 'Elle', $b]] as [$nom, $n])
                    <div>
                        <div style="display:flex; justify-content:space-between; font-size:.85rem; margin-bottom:.25rem">
                            <span>{{ $nom }}</span>
                            <span style="color:var(--ink-mute)">{{ $n }} ({{ round($n / $total * 100) }} %)</span>
                        </div>
                        <div class="progression-piste" style="height:6px">
                            <div class="progression-barre" style="width: {{ round($n / $total * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
@endif

@endsection
