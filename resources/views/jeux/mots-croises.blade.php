@extends('layout')
@section('titre', 'Mots croisés')
@section('content')

{{--
    La grille est générée depuis la liste de mots des mariés. Le navigateur
    ne reçoit que les cases et les définitions, jamais les réponses : la
    correction se fait sur le serveur.
--}}

@php
    // Numéro de la case de départ de chaque mot.
    $numeros = [];
    foreach ($grille['mots'] as $mot) {
        $numeros["{$mot['x']},{$mot['y']}"] = $mot['numero'];
    }
    $horizontaux = collect($grille['mots'])->where('direction', 'horizontal')->sortBy('numero');
    $verticaux   = collect($grille['mots'])->where('direction', 'vertical')->sortBy('numero');
@endphp

<header class="page-tete">
    <p class="page-tete-sur">Jeu</p>
    <h1>Mots croisés</h1>
    <p>{{ count($grille['mots']) }} mots à retrouver. Touchez une case pour écrire.</p>
</header>

<form method="POST" action="{{ route('jeux.submitMotsCroises') }}" id="mots-croises">
    @csrf

    <div class="mc-disposition">
        <div class="bloc mc-plateau">
            <div class="mc-grille" style="--mc-colonnes: {{ $grille['largeur'] }}" role="grid" aria-label="Grille de mots croisés">
                @for ($y = 0; $y < $grille['hauteur']; $y++)
                    @for ($x = 0; $x < $grille['largeur']; $x++)
                        @if ($grille['cases'][$y][$x] !== null)
                            <label class="mc-case">
                                @isset($numeros["{$x},{$y}"])<span class="mc-num">{{ $numeros["{$x},{$y}"] }}</span>@endisset
                                <input name="grid[{{ $y }}][{{ $x }}]" maxlength="1" autocomplete="off" autocapitalize="characters"
                                       spellcheck="false" data-x="{{ $x }}" data-y="{{ $y }}" aria-label="Case {{ $x + 1 }}, {{ $y + 1 }}">
                            </label>
                        @else
                            <span class="mc-noire" aria-hidden="true"></span>
                        @endif
                    @endfor
                @endfor
            </div>
        </div>

        <div class="bloc mc-definitions">
            @if ($horizontaux->isNotEmpty())
                <h2 class="bloc-titre" style="font-size:1rem"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Horizontalement</h2>
                <ol class="mc-liste">
                    @foreach ($horizontaux as $mot)
                        <li data-x="{{ $mot['x'] }}" data-y="{{ $mot['y'] }}" data-sens="horizontal"><strong>{{ $mot['numero'] }}.</strong> {{ $mot['definition'] }} <small>({{ $mot['longueur'] }})</small></li>
                    @endforeach
                </ol>
            @endif
            @if ($verticaux->isNotEmpty())
                <h2 class="bloc-titre" style="font-size:1rem; margin-top:1.2rem"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i> Verticalement</h2>
                <ol class="mc-liste">
                    @foreach ($verticaux as $mot)
                        <li data-x="{{ $mot['x'] }}" data-y="{{ $mot['y'] }}" data-sens="vertical"><strong>{{ $mot['numero'] }}.</strong> {{ $mot['definition'] }} <small>({{ $mot['longueur'] }})</small></li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>

    <div class="bloc" style="margin-top:1rem">
        @include('jeux.partials.joueur')
        <button type="submit" class="bouton bouton--plein bouton--large">
            <i class="fa-solid fa-check" aria-hidden="true"></i> Vérifier ma grille
        </button>
    </div>
</form>

@push('scripts')
<script>
(() => {
    // Saisie fluide : chaque lettre fait avancer dans le sens du mot choisi,
    // l'effacement recule. Toucher une définition place le curseur.
    const cases = new Map([...document.querySelectorAll('.mc-case input')].map((i) => [`${i.dataset.x},${i.dataset.y}`, i]));
    let sens = 'horizontal';

    const voisine = (input, pas) => {
        const x = +input.dataset.x + (sens === 'horizontal' ? pas : 0);
        const y = +input.dataset.y + (sens === 'vertical' ? pas : 0);
        return cases.get(`${x},${y}`);
    };

    cases.forEach((input) => {
        input.addEventListener('focus', () => {
            // Sens naturel de la case : celui où elle a une voisine.
            const h = cases.has(`${+input.dataset.x + 1},${input.dataset.y}`) || cases.has(`${+input.dataset.x - 1},${input.dataset.y}`);
            const v = cases.has(`${input.dataset.x},${+input.dataset.y + 1}`) || cases.has(`${input.dataset.x},${+input.dataset.y - 1}`);
            if (!(sens === 'horizontal' ? h : v)) sens = h ? 'horizontal' : 'vertical';
            input.select();
        });
        input.addEventListener('input', () => {
            input.value = input.value.normalize('NFD').replace(/[^a-zA-Z]/g, '').toUpperCase().slice(-1);
            if (input.value) voisine(input, 1)?.focus();
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !input.value) { e.preventDefault(); const p = voisine(input, -1); if (p) { p.value = ''; p.focus(); } }
            const fleches = { ArrowRight: [1, 0], ArrowLeft: [-1, 0], ArrowDown: [0, 1], ArrowUp: [0, -1] };
            if (fleches[e.key]) {
                e.preventDefault();
                const [dx, dy] = fleches[e.key];
                sens = dx ? 'horizontal' : 'vertical';
                cases.get(`${+input.dataset.x + dx},${+input.dataset.y + dy}`)?.focus();
            }
        });
    });

    document.querySelectorAll('.mc-liste li').forEach((li) => li.addEventListener('click', () => {
        sens = li.dataset.sens;
        const input = cases.get(`${li.dataset.x},${li.dataset.y}`);
        input?.focus();
        input?.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }));
})();
</script>
@endpush

@endsection
