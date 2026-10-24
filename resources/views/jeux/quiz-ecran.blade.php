@php
    /*
     * L'écran projeté du quiz en direct : la question, les réponses qui
     * arrivent, la bonne réponse, le classement. Aucun bouton : il suit le
     * pupitre de l'animateur. Plein écran, lisible du fond de la salle.
     */
    $style = $event->styleInline();
    $prenoms = $event->theme?->visuel('titre');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quiz — {{ $event->nom }}</title>
<link rel="stylesheet" href="{{ asset('css/invite.css') }}?v={{ filemtime(public_path('css/invite.css')) }}">
<style>
    html, body { height: 100%; margin: 0; }
    body.ecran {
        background: var(--c-page); color: var(--c-sur-page); font-family: var(--f-texte);
        display: grid; grid-template-rows: auto 1fr auto; overflow: hidden;
    }
    .ec-tete { display: flex; align-items: center; justify-content: space-between; padding: 2.5vh 4vw; }
    .ec-tete img { height: 9vh; }
    .ec-tete .titre { font-family: var(--f-titre); font-size: 5vh; }
    .ec-info { font-size: 2.6vh; color: var(--c-sur-page-doux); text-align: right; }
    .ec-scene { display: grid; place-items: center; padding: 0 6vw; }
    .ec-bloc { width: 100%; max-width: 1500px; }
    .ec-numero { font-size: 2.4vh; font-weight: 700; letter-spacing: .2em; text-transform: uppercase; color: var(--c-accent-texte); }
    .ec-question { font-family: var(--f-titre); font-size: clamp(4vh, 7vh, 9vh); line-height: 1.15; margin: 1.5vh 0 4vh; }
    .ec-choix { display: grid; gap: 2vh; }
    .ec-choix div {
        position: relative; overflow: hidden; display: flex; justify-content: space-between; align-items: center;
        padding: 2.4vh 2.4vw; border: .4vh solid var(--c-bord); border-radius: 2vh; background: var(--c-carte);
        font-size: 3.6vh; font-weight: 600;
    }
    .ec-choix .barre { position: absolute; inset: 0 auto 0 0; width: 0; background: color-mix(in srgb, var(--c-accent) 22%, transparent); transition: width .6s ease; }
    .ec-choix span { position: relative; }
    .ec-choix div.bonne { border-color: #2F7D5D; background: #E7F3EC; color: #1E5E3E; }
    .ec-choix div.bonne .barre { background: color-mix(in srgb, #2F7D5D 25%, transparent); }
    .ec-choix div.terne { opacity: .45; }
    .ec-attente { text-align: center; }
    .ec-attente .coeur { font-size: 14vh; color: var(--c-accent); animation: ec-bat 1.3s ease-in-out infinite; display: inline-block; }
    .ec-attente p { font-size: 4vh; margin: 2vh 0 0; }
    .ec-lien { font-size: 2.6vh; color: var(--c-sur-page-doux); }
    @keyframes ec-bat { 0%, 100% { transform: scale(1); } 15% { transform: scale(1.18); } 30% { transform: scale(1); } 45% { transform: scale(1.1); } }
    .ec-podium { list-style: none; margin: 0; padding: 0; display: grid; gap: 1.6vh; }
    .ec-podium li { display: flex; justify-content: space-between; padding: 2vh 2.4vw; border-radius: 2vh; background: var(--c-carte); border: .3vh solid var(--c-bord); font-size: 4vh; }
    .ec-podium li:first-child { background: var(--c-accent); color: var(--c-sur-accent); font-size: 5vh; }
    .ec-pied { padding: 2vh 4vw; display: flex; justify-content: space-between; font-size: 2.4vh; color: var(--c-sur-page-doux); }
    .ec-mini { font-size: 2.4vh; margin-top: 3vh; color: var(--c-sur-page-doux); }
    .ec-mini strong { color: var(--c-sur-page); }
</style>
</head>
<body class="ecran" @if ($style) style="{{ $style }}" @endif>

<header class="ec-tete">
    @if ($prenoms)<img src="{{ asset($prenoms) }}" alt="{{ $event->nom }}">@else<span class="titre">{{ $event->nom }}</span>@endif
    <div class="ec-info"><div class="titre" style="font-size:4.4vh">Quiz des mariés</div><span id="ec-joueurs"></span></div>
</header>

<main class="ec-scene"><div class="ec-bloc" id="ec"></div></main>

<footer class="ec-pied">
    <span>Pour jouer : ouvrez le site du mariage, menu « Quiz des mariés »</span>
    <span>{{ route('landing', $event->slug) }}</span>
</footer>

<script>
(() => {
    const quiz = @json($quiz);
    const urlEtat = @json(route('quiz.animateur.etat', ['jeton' => $jeton]));
    const zone = document.getElementById('ec');
    const echapper = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    let cle = '';

    const top = (liste, n) => liste.slice(0, n).map((c, i) => `<li><span>${['🥇', '🥈', '🥉'][i] ?? (i + 1) + '.'} ${echapper(c.nom)}</span><span>${c.bonnes ?? 0} / ${quiz.length}</span></li>`).join('');

    function afficher(e) {
        document.getElementById('ec-joueurs').textContent = `${e.joueurs} joueur${e.joueurs > 1 ? 's' : ''}`;
        const nouvelle = JSON.stringify([e.etat, e.index]);

        if (e.etat === 'attente') {
            if (cle !== nouvelle) zone.innerHTML = `<div class="ec-attente"><span class="coeur">♥</span><p>Le quiz va commencer !</p><p class="ec-lien">Sortez vos téléphones…</p></div>`;
        } else if (e.etat === 'fin') {
            zone.innerHTML = `<p class="ec-numero">Le podium</p><p class="ec-question">Qui connaît le mieux les mariés ?</p>`
                + (e.classement.length ? `<ol class="ec-podium">${top(e.classement, 5)}</ol>` : `<p>Aucune réponse.</p>`);
        } else {
            const q = quiz[e.index];
            const total = e.stats.reponses || 0;
            const revele = e.etat === 'reponse';
            // La question ne se redessine qu'en changeant : seules les barres bougent.
            if (cle !== nouvelle) {
                zone.innerHTML = `<p class="ec-numero">Question ${e.index + 1} / ${quiz.length} · ${echapper(q.sujet)}</p>
                    <p class="ec-question">${echapper(q.question)}</p>
                    <div class="ec-choix">${q.choix.map((c, i) => `<div data-i="${i}"><span class="barre"></span><span>${echapper(c)}</span><span class="n"></span></div>`).join('')}</div>
                    <p class="ec-mini" id="ec-mini"></p>`;
            }
            zone.querySelectorAll('.ec-choix div').forEach((d) => {
                const i = +d.dataset.i, n = e.stats.parChoix[i] || 0;
                d.querySelector('.barre').style.width = (total ? Math.round(n / total * 100) : 0) + '%';
                // Le décompte par proposition n'apparaît qu'à la révélation : il
                // ne doit pas souffler la réponse aux retardataires.
                d.querySelector('.n').textContent = revele ? n : '';
                if (!revele) d.querySelector('.barre').style.width = '0%';
                d.classList.toggle('bonne', revele && i === q.bonne);
                d.classList.toggle('terne', revele && i !== q.bonne);
            });
            const mini = document.getElementById('ec-mini');
            mini.innerHTML = revele && e.classement.length
                ? `En tête : ${e.classement.slice(0, 3).map((c) => `<strong>${echapper(c.nom)}</strong> (${c.bonnes})`).join(' · ')}`
                : `${total} réponse${total > 1 ? 's' : ''}`;
        }
        cle = nouvelle;
    }

    const interroger = async () => {
        try { const r = await fetch(urlEtat, { headers: { 'Accept': 'application/json' }, cache: 'no-store' }); if (r.ok) afficher(await r.json()); } catch (e) {}
    };
    interroger();
    setInterval(interroger, 1500);
})();
</script>
</body>
</html>
