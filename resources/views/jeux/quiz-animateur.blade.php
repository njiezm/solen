@extends('layout')
@section('titre', 'Pupitre du quiz')
@section('content')

{{--
    Le pupitre de l'animateur : il lance chaque question, voit les réponses
    arriver, révèle la bonne, enchaîne, puis affiche le classement. Les
    téléphones des invités suivent d'eux-mêmes. Peut être projeté.
--}}

<header class="page-tete">
    <p class="page-tete-sur">Animateur</p>
    <h1>Quiz des mariés</h1>
    <p>{{ count($quiz) }} questions. Les invités ouvrent « Quiz des mariés » sur leur téléphone et suivent votre rythme.</p>
    <p><a href="{{ route('quiz.ecran', ['jeton' => $jeton]) }}" target="_blank" rel="noopener" class="bouton bouton--contour bouton--petit">
        <i class="fa-solid fa-tv" aria-hidden="true"></i> Ouvrir l’écran à projeter</a></p>
</header>

<div class="bloc pu" id="pu"
     data-etat="{{ route('quiz.animateur.etat', ['jeton' => $jeton]) }}"
     data-commande="{{ route('quiz.animateur.commande', ['jeton' => $jeton]) }}"
     data-token="{{ csrf_token() }}"
     data-quiz='@json($quiz)'>

    <div class="pu-tete">
        <span id="pu-etat" class="pu-pastille">…</span>
        <span id="pu-joueurs" class="doux"></span>
    </div>

    <div id="pu-question"></div>

    {{-- La question suivante, visible du seul animateur avant qu'il la lance :
         il a l'intitulé et la bonne réponse sous les yeux pour l'annoncer. --}}
    <div class="pu-suivante" id="pu-suivante" hidden></div>

    <div class="pu-actions" id="pu-actions"></div>

    <details class="pu-plus">
        <summary>Aller à une question précise · recommencer</summary>
        <div class="pu-liste" id="pu-liste"></div>
        <button type="button" class="bouton bouton--contour bouton--petit" data-action="reinitialiser"
                data-confirmer="Tout recommencer ? Les réponses et les scores du quiz seront effacés.">Recommencer le quiz à zéro</button>
    </details>
</div>

<style>
    .pu-tete { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem; }
    .pu-pastille { padding: .3rem .8rem; border-radius: 999px; background: var(--c-accent-pale); color: var(--c-accent-carte); font-weight: 700; font-size: .8rem; letter-spacing: .08em; text-transform: uppercase; }
    .pu-numero { font: 700 .8rem/1 var(--f-texte); letter-spacing: .14em; text-transform: uppercase; color: var(--c-accent-carte); }
    .pu-texte { font-family: var(--f-titre); font-size: clamp(1.7rem, 4.5vw, 2.6rem); line-height: 1.2; margin: .4rem 0 1rem; }
    .pu-choix { display: grid; gap: .55rem; }
    .pu-choix div { position: relative; overflow: hidden; display: flex; justify-content: space-between; gap: 1rem; padding: .8rem 1rem; border: 2px solid var(--c-bord); border-radius: var(--r-carte); font-weight: 600; }
    .pu-choix div.bonne { border-color: #2F7D5D; background: #E7F3EC; }
    .pu-choix .barre { position: absolute; inset: 0 auto 0 0; background: color-mix(in srgb, var(--c-accent) 14%, transparent); z-index: 0; transition: width .4s ease; }
    .pu-choix span { position: relative; z-index: 1; }
    /* Le pupitre n'a que faire de la navigation des invités ; son bouton
       principal reste collé en bas de l'écran, à portée de pouce. */
    body:has(#pu) .barre-basse { display: none; }
    body:has(#pu) .contenu, body:has(#pu) .pied { padding-bottom: 6rem; }
    .pu-actions {
        display: grid; gap: .6rem; margin-top: 1.2rem;
        position: sticky; bottom: 0; z-index: 5;
        padding: .8rem 0 calc(.8rem + env(safe-area-inset-bottom));
        background: linear-gradient(to top, var(--c-carte) 75%, transparent);
    }
    .pu-actions .bouton { justify-content: center; font-size: 1.1rem; padding: 1rem; }
    .pu-suivante { margin-top: 1.2rem; padding: .9rem 1rem; border: 2px dashed var(--c-bord); border-radius: var(--r-carte); background: var(--c-voile); }
    .pu-suivante .pu-numero { color: var(--c-sur-carte-doux); }
    .pu-suivante .texte { font-weight: 700; font-size: 1.05rem; margin: .35rem 0 .5rem; }
    .pu-suivante ul { margin: 0; padding-left: 1.1rem; font-size: .95rem; }
    .pu-suivante li.bonne { color: #1E5E3E; font-weight: 700; }
    .pu-plus { margin-top: 1.5rem; font-size: .9rem; }
    .pu-plus summary { cursor: pointer; color: var(--c-sur-carte-doux); }
    .pu-liste { display: flex; flex-wrap: wrap; gap: .4rem; margin: .8rem 0; }
    .pu-classement { margin: 0; padding-left: 1.4rem; font-size: 1.15rem; line-height: 1.8; }
</style>

@push('scripts')
<script>
(() => {
    const pu = document.getElementById('pu');
    const quiz = JSON.parse(pu.dataset.quiz);
    const echapper = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    let etat = null, cleActions = '';

    async function envoyer(action, index = null) {
        const r = await fetch(pu.dataset.commande, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': pu.dataset.token },
            body: JSON.stringify({ action, index }),
        });
        if (r.ok) afficher(await r.json());
    }

    function bouton(libelle, action, index = null, plein = true) {
        return `<button type="button" class="bouton ${plein ? 'bouton--plein' : 'bouton--contour'}" data-action="${action}" ${index !== null ? `data-index="${index}"` : ''}>${libelle}</button>`;
    }

    // Aperçu d'une question, réservé au pupitre : intitulé et bonne réponse.
    function apercu(i, titre) {
        const bloc = document.getElementById('pu-suivante');
        const q = quiz[i];
        if (!q) { bloc.hidden = true; return; }
        bloc.hidden = false;
        bloc.innerHTML = `<p class="pu-numero">${titre} · Question ${i + 1} / ${quiz.length} · ${echapper(q.sujet)}</p>
            <p class="texte">${echapper(q.question)}</p>
            <ul>${q.choix.map((c, j) => `<li class="${j === q.bonne ? 'bonne' : ''}">${echapper(c)}${j === q.bonne ? ' ✓ bonne réponse' : ''}</li>`).join('')}</ul>`;
    }

    function afficher(e) {
        etat = e;
        // À l'attente : la 1re. Question révélée : la suivante. Sinon, rien.
        if (e.etat === 'attente') apercu(0, 'À lancer');
        else if (e.etat === 'reponse') apercu(e.index + 1, 'À suivre');
        else apercu(-1);
        const libelles = { attente: 'En attente', question: 'Question ouverte', reponse: 'Réponse révélée', fin: 'Terminé' };
        document.getElementById('pu-etat').textContent = libelles[e.etat] || e.etat;
        document.getElementById('pu-joueurs').textContent = `${e.joueurs} joueur${e.joueurs > 1 ? 's' : ''}`;

        const q = quiz[e.index];
        const zone = document.getElementById('pu-question');
        const actions = document.getElementById('pu-actions');

        if (e.etat === 'attente') {
            zone.innerHTML = `<p>Quand tout le monde a ouvert le quiz, lancez la première question.</p>`;
            if (cleActions !== 'attente') { cleActions = 'attente'; actions.innerHTML = bouton('▶ Lancer la question 1', 'lancer', 0); }
        } else if (e.etat === 'fin') {
            zone.innerHTML = `<p class="pu-numero">Classement du quiz</p>` + (e.classement.length
                ? `<ol class="pu-classement">${e.classement.map((c) => `<li><strong>${echapper(c.nom)}</strong> — ${c.bonnes ?? 0} / ${quiz.length}</li>`).join('')}</ol>`
                : `<p>Aucune réponse.</p>`);
            cleActions = 'fin';
            actions.innerHTML = '';
        } else {
            const total = e.stats.reponses || 0;
            zone.innerHTML = `<p class="pu-numero">Question ${e.index + 1} / ${quiz.length} · ${echapper(q.sujet)}</p>
                <p class="pu-texte">${echapper(q.question)}</p>
                <div class="pu-choix">${q.choix.map((c, i) => {
                    const n = e.stats.parChoix[i] || 0;
                    const pct = total ? Math.round(n / total * 100) : 0;
                    const bonne = e.etat === 'reponse' && i === q.bonne;
                    return `<div class="${bonne ? 'bonne' : ''}"><span class="barre" style="width:${pct}%"></span><span>${bonne ? '✓ ' : ''}${echapper(c)}</span><span>${n}</span></div>`;
                }).join('')}</div>
                <p class="doux" style="margin:.8rem 0 0">${total} réponse${total > 1 ? 's' : ''}</p>`;

            const derniere = e.index >= quiz.length - 1;
            // Les boutons ne sont redessinés que si l'étape change : un tap
            // pendant un rafraîchissement ne doit jamais se perdre.
            if (cleActions === `${e.etat}-${e.index}`) return;
            cleActions = `${e.etat}-${e.index}`;
            actions.innerHTML = e.etat === 'question'
                ? bouton('Révéler la réponse', 'reveler')
                : (derniere ? bouton('🏆 Terminer et afficher le classement', 'terminer') : bouton(`▶ Question suivante (${e.index + 2})`, 'lancer', e.index + 1));
        }

        const liste = document.getElementById('pu-liste');
        if (!liste.childElementCount) liste.innerHTML = quiz.map((_, i) => bouton(String(i + 1), 'lancer', i, false)).join('');
    }

    pu.addEventListener('click', (ev) => {
        const b = ev.target.closest('[data-action]');
        if (!b) return;
        if (b.dataset.confirmer && !confirm(b.dataset.confirmer)) return;
        envoyer(b.dataset.action, b.dataset.index !== undefined ? +b.dataset.index : null);
    });

    const interroger = async () => {
        try { const r = await fetch(pu.dataset.etat, { headers: { 'Accept': 'application/json' }, cache: 'no-store' }); if (r.ok) afficher(await r.json()); } catch (e) {}
    };
    interroger();
    setInterval(interroger, 1500);
})();
</script>
@endpush

@endsection
