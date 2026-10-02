@extends('layout')
@section('titre', 'Memory')
@section('largeur', 'cadre--etroit')
@section('content')

{{-- Huit paires : les photos du couple, complétées de pictogrammes. --}}

<header class="page-tete">
    <p class="page-tete-sur">Jeu</p>
    <h1>Memory</h1>
    <p>Retrouvez les huit paires en un minimum de coups.</p>
</header>

<div class="memory-compteurs" aria-live="polite">
    <span><i class="fa-solid fa-hand-pointer" aria-hidden="true"></i> <strong id="coups">0</strong> coups</span>
    <span><i class="fa-regular fa-clock" aria-hidden="true"></i> <strong id="chrono">0:00</strong></span>
    <span><i class="fa-solid fa-check" aria-hidden="true"></i> <strong id="paires">0</strong>/8</span>
</div>

<div class="memory-plateau" id="plateau">
    @foreach ($cartes as $carte)
        <button type="button" class="memory-carte" data-paire="{{ $carte['paire'] }}" aria-label="Carte cachée">
            <span class="memory-dos" aria-hidden="true"><i class="fa-solid fa-heart"></i></span>
            <span class="memory-face" aria-hidden="true">
                @if ($carte['image'])
                    <img src="{{ $carte['image'] }}" alt="" loading="lazy">
                @else
                    <i class="fa-solid {{ $carte['picto'] }}"></i>
                @endif
            </span>
        </button>
    @endforeach
</div>

<form method="POST" action="{{ route('jeux.submitMemory') }}" class="bloc" id="memory-fin" hidden style="margin-top:1.2rem; text-align:center">
    @csrf
    <h2 class="bloc-titre" style="justify-content:center">Bravo, toutes les paires sont trouvées !</h2>
    <input type="hidden" name="coups" id="coups-champ">
    <input type="hidden" name="temps" id="temps-champ">
    <div style="text-align:left">@include('jeux.partials.joueur')</div>
    <button class="bouton bouton--plein bouton--large"><i class="fa-solid fa-trophy" aria-hidden="true"></i> Enregistrer mon score</button>
</form>

@push('scripts')
<script>
(() => {
    const cartes = [...document.querySelectorAll('.memory-carte')];
    const el = (id) => document.getElementById(id);
    let retournees = [], coups = 0, trouvees = 0, debut = null, minuteur = null, verrou = false;

    const chrono = () => {
        const s = Math.floor((Date.now() - debut) / 1000);
        el('chrono').textContent = `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
    };

    cartes.forEach((carte) => carte.addEventListener('click', () => {
        if (verrou || carte.classList.contains('visible')) return;
        if (!debut) { debut = Date.now(); minuteur = setInterval(chrono, 500); }

        carte.classList.add('visible');
        retournees.push(carte);
        if (retournees.length < 2) return;

        coups++;
        el('coups').textContent = coups;
        const [a, b] = retournees;
        retournees = [];

        if (a.dataset.paire === b.dataset.paire) {
            a.classList.add('trouvee'); b.classList.add('trouvee');
            trouvees++;
            el('paires').textContent = trouvees;
            if (trouvees === cartes.length / 2) {
                clearInterval(minuteur);
                el('coups-champ').value = coups;
                el('temps-champ').value = Math.max(1, Math.round((Date.now() - debut) / 1000));
                setTimeout(() => { el('memory-fin').hidden = false; el('memory-fin').scrollIntoView({ behavior: 'smooth' }); }, 600);
            }
            return;
        }

        verrou = true;
        setTimeout(() => { a.classList.remove('visible'); b.classList.remove('visible'); verrou = false; }, 850);
    }));
})();
</script>
@endpush

@endsection
