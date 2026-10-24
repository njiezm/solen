@extends('layout')
@section('titre', 'Quiz des mariés')
@section('largeur', 'cadre--etroit')
@section('content')

{{--
    Le quiz en direct : la page suit l'animateur. Elle demande l'état toutes
    les 1,5 s et s'affiche selon lui (attente, question, réponse, fin). Une
    question ne se répond qu'une fois, tant qu'elle est ouverte.
--}}

<header class="page-tete">
    <p class="page-tete-sur">Jeu en direct</p>
    <h1>Quiz des mariés</h1>
    <p>Qui connaît le mieux {{ $prenoms[0] }} et {{ $prenoms[1] }} ? Les questions arrivent sur votre téléphone.</p>
</header>

<div class="bloc qd" id="qd"
     data-etat="{{ route('quiz.direct.etat') }}"
     data-joueur="{{ route('quiz.direct.joueur') }}"
     data-repondre="{{ route('quiz.direct.repondre') }}"
     data-token="{{ csrf_token() }}"
     data-connu="{{ empty($joueur['id']) ? '0' : '1' }}">

    {{-- Qui joue : demandé une seule fois. --}}
    <form class="qd-joueur" id="qd-joueur" @unless (empty($joueur['id'])) hidden @endunless>
        <p class="qd-consigne">Pour jouer, dites-nous qui vous êtes :</p>
        <div class="grille-2">
            <label class="champ"><span>Prénom</span><input class="saisie" name="prenom" required autocomplete="given-name"></label>
            <label class="champ"><span>Nom <small>(ou initiale)</small></span><input class="saisie" name="nom" required autocomplete="family-name"></label>
        </div>
        <p class="champ-erreur" id="qd-erreur" hidden></p>
        <button class="bouton bouton--plein bouton--large"><i class="fa-solid fa-play" aria-hidden="true"></i> Je joue</button>
    </form>

    <div class="qd-scene" id="qd-scene" @if (empty($joueur['id'])) hidden @endif aria-live="polite">
        <p class="qd-moi">@if (! empty($joueur['prenom'])) Vous jouez en tant que <strong>{{ $joueur['prenom'] }}</strong> @endif</p>
        <div id="qd-contenu">
            <div class="qd-attente"><span class="qd-coeur">♥</span><p>Connexion au quiz…</p></div>
        </div>
    </div>
</div>

<style>
    .qd-consigne { margin: 0 0 .8rem; }
    .qd-moi { margin: 0 0 1rem; font-size: .85rem; color: var(--c-sur-carte-doux); text-align: center; }
    .qd-attente { text-align: center; padding: 1.5rem 0; }
    .qd-attente p { margin: .8rem 0 0; font-size: 1.05rem; }
    .qd-coeur { display: inline-block; font-size: 2.6rem; color: var(--c-accent); animation: qd-bat 1.2s ease-in-out infinite; }
    @keyframes qd-bat { 0%, 100% { transform: scale(1); } 15% { transform: scale(1.22); } 30% { transform: scale(1); } 45% { transform: scale(1.12); } }
    .qd-numero { display: block; margin-bottom: .4rem; font: 700 .75rem/1 var(--f-texte); letter-spacing: .16em; text-transform: uppercase; color: var(--c-accent-carte); }
    .qd-question { margin: 0 0 1.1rem; font-family: var(--f-titre); font-size: clamp(1.5rem, 6vw, 1.9rem); line-height: 1.25; }
    .qd-choix { display: grid; gap: .7rem; }
    .qd-choix button {
        min-height: 64px; padding: .9rem 1rem; border: 2px solid var(--c-bord); border-radius: var(--r-carte);
        background: var(--c-page); color: var(--c-sur-page); font: 650 1.05rem/1.25 var(--f-texte); text-align: center;
        transition: transform .12s ease, border-color .15s ease, background .15s ease;
    }
    .qd-choix button:active { transform: scale(.98); }
    .qd-choix button.choisi { background: var(--c-accent); border-color: var(--c-accent); color: var(--c-sur-accent); }
    .qd-choix button.bonne { background: #2F7D5D; border-color: #2F7D5D; color: #fff; }
    .qd-choix button.fausse { background: #FBEAE8; border-color: #B3261E; color: #8C1D16; }
    .qd-choix button:disabled { cursor: default; }
    .qd-statut { margin: 1rem 0 0; text-align: center; font-weight: 600; min-height: 1.4em; }
    .qd-fin { text-align: center; padding: 1rem 0; }
    .qd-fin .qd-score { font-family: var(--f-titre); font-size: 3rem; margin: .4rem 0; color: var(--c-accent-carte); }
</style>

@push('scripts')
<script>
(() => {
    const box = document.getElementById('qd');
    const contenu = document.getElementById('qd-contenu');
    const scene = document.getElementById('qd-scene');
    const formJoueur = document.getElementById('qd-joueur');
    const entetes = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': box.dataset.token };
    let dernier = '', envoi = false;

    // Les propositions dans un ordre propre à chaque téléphone, mais stable
    // pour une question donnée (pas de valse à chaque rafraîchissement).
    const ordre = (index, n) => {
        const cle = `qd-ordre-${index}`;
        try { const o = JSON.parse(sessionStorage.getItem(cle)); if (o && o.length === n) return o; } catch (e) {}
        const o = [...Array(n).keys()].sort(() => Math.random() - .5);
        try { sessionStorage.setItem(cle, JSON.stringify(o)); } catch (e) {}
        return o;
    };

    const echapper = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function afficher(e) {
        const cle = JSON.stringify([e.etat, e.index, e.mienne, e.bonne, e.score]);
        if (cle === dernier) return;
        dernier = cle;

        if (e.etat === 'attente') {
            contenu.innerHTML = `<div class="qd-attente"><span class="qd-coeur">♥</span><p>Le quiz va bientôt commencer.<br>Gardez cette page ouverte !</p></div>`;
            return;
        }

        if (e.etat === 'fin') {
            contenu.innerHTML = `<div class="qd-fin"><p class="qd-numero">Quiz terminé</p><p>Votre score</p>
                <p class="qd-score">${e.score ?? 0} / ${e.total}</p><p>Merci d’avoir joué ! Le classement est annoncé par l’animateur.</p></div>`;
            return;
        }

        const revele = e.etat === 'reponse';
        const boutons = ordre(e.index, e.choix.length).map((i) => {
            let classe = '';
            if (revele && i === e.bonne) classe = 'bonne';
            else if (revele && i === e.mienne) classe = 'fausse';
            else if (i === e.mienne) classe = 'choisi';
            return `<button type="button" class="${classe}" data-choix="${i}" ${(e.mienne !== null && e.mienne !== undefined) || revele ? 'disabled' : ''}>${echapper(e.choix[i])}</button>`;
        }).join('');

        let statut = '';
        if (revele) statut = e.mienne === undefined || e.mienne === null ? 'Temps écoulé : pas de réponse.' : (e.mienne === e.bonne ? 'Bravo, bonne réponse ! 🎉' : 'Raté… la bonne réponse est en vert.');
        else if (e.mienne !== undefined && e.mienne !== null) statut = 'Réponse enregistrée. Attendez la révélation…';

        contenu.innerHTML = `<span class="qd-numero">Question ${e.index + 1} / ${e.total} · ${echapper(e.sujet ?? '')}</span>
            <p class="qd-question">${echapper(e.question)}</p>
            <div class="qd-choix">${boutons}</div>
            <p class="qd-statut">${statut}</p>`;
    }

    async function interroger() {
        try {
            const r = await fetch(box.dataset.etat, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
            if (r.ok) afficher(await r.json());
        } catch (e) { /* réseau capricieux : on réessaie au prochain tour */ }
    }

    contenu.addEventListener('click', async (ev) => {
        const b = ev.target.closest('[data-choix]');
        if (!b || b.disabled || envoi) return;
        envoi = true;
        contenu.querySelectorAll('[data-choix]').forEach((x) => x.disabled = true);
        b.classList.add('choisi');
        const etat = JSON.parse(dernier);
        try {
            await fetch(box.dataset.repondre, { method: 'POST', headers: entetes, body: JSON.stringify({ index: etat[1], choix: +b.dataset.choix }) });
        } catch (e) {}
        envoi = false;
        dernier = '';
        interroger();
    });

    formJoueur.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const donnees = Object.fromEntries(new FormData(formJoueur));
        const r = await fetch(box.dataset.joueur, { method: 'POST', headers: entetes, body: JSON.stringify(donnees) });
        const err = document.getElementById('qd-erreur');
        if (!r.ok) {
            const j = await r.json().catch(() => ({}));
            err.textContent = j.message || 'Vérifiez votre prénom et votre nom.'; err.hidden = false; return;
        }
        const j = await r.json();
        formJoueur.hidden = true;
        scene.hidden = false;
        scene.querySelector('.qd-moi').innerHTML = `Vous jouez en tant que <strong>${echapper(j.prenom)}</strong>`;
        demarrer();
    });

    let minuteur = null;
    const demarrer = () => { if (minuteur) return; interroger(); minuteur = setInterval(interroger, 1500); };
    if (box.dataset.connu === '1') demarrer();
})();
</script>
@endpush

@endsection
