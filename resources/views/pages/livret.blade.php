@extends('layout')
@section('titre', $titre)
@section('content')

{{--
    La liseuse : chaque page du PDF est dessinée dans un canevas, et les pages
    se suivent dans une piste horizontale qu'on fait glisser du doigt. Le
    défilement par aimantation (scroll-snap) donne le geste d'un livre sans
    une ligne de code de détection de balayage.

    Les pages sont dessinées à la demande, près de celle qu'on lit : un
    livret de quarante pages ne met pas un vieux téléphone à genoux.
--}}

<header class="page-tete">
    <p class="page-tete-sur">La cérémonie</p>
    <h1>{{ $titre }}</h1>
</header>

<div class="liseuse" id="liseuse" data-pdf="{{ $url }}">
    <div class="liseuse-pages" id="pages" tabindex="0" aria-label="Pages du livret">
        <div class="liseuse-page">
            <div class="liseuse-attente" id="attente">
                <i class="fa-solid fa-spinner fa-spin fa-2x" aria-hidden="true"></i>
                <span>Ouverture du livret…</span>
            </div>
        </div>
    </div>

    <div class="liseuse-outils">
        <button type="button" id="precedente" aria-label="Page précédente" disabled>
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>

        <span class="liseuse-compteur" id="compteur" aria-live="polite">–</span>

        <div class="liseuse-actions">
            @if ($telechargeable)
                <a href="{{ $url }}" download aria-label="Télécharger le livret">
                    <i class="fa-solid fa-download" aria-hidden="true"></i>
                </a>
            @endif
            <button type="button" id="plein-ecran" aria-label="Plein écran">
                <i class="fa-solid fa-expand" aria-hidden="true"></i>
            </button>
            <button type="button" id="suivante" aria-label="Page suivante" disabled>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</div>

<p class="liseuse-astuce">
    <i class="fa-regular fa-hand-pointer" aria-hidden="true"></i>
    Faites glisser pour tourner les pages.
</p>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
(async () => {
    const liseuse = document.getElementById('liseuse');
    const piste   = document.getElementById('pages');
    const compteur = document.getElementById('compteur');
    const avant   = document.getElementById('precedente');
    const apres   = document.getElementById('suivante');

    pdfjsLib.GlobalWorkerOptions.workerSrc =
        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    let doc;
    try {
        doc = await pdfjsLib.getDocument(liseuse.dataset.pdf).promise;
    } catch (e) {
        document.getElementById('attente').innerHTML =
            '<i class="fa-solid fa-triangle-exclamation fa-2x" aria-hidden="true"></i>' +
            '<span>Le livret n’a pas pu être ouvert. Vérifiez votre connexion puis rechargez la page.</span>';
        return;
    }

    // Une case par page, vide tant qu'on n'en approche pas.
    piste.innerHTML = '';
    const cases = [];
    for (let n = 1; n <= doc.numPages; n++) {
        const c = document.createElement('div');
        c.className = 'liseuse-page';
        c.dataset.page = n;
        c.setAttribute('role', 'group');
        c.setAttribute('aria-label', `Page ${n} sur ${doc.numPages}`);
        piste.appendChild(c);
        cases.push(c);
    }

    const dessinees = new Map();

    const dessiner = async (n) => {
        if (n < 1 || n > doc.numPages || dessinees.has(n)) return;
        dessinees.set(n, true);

        const page = await doc.getPage(n);
        const boite = cases[n - 1].getBoundingClientRect();
        const base = page.getViewport({ scale: 1 });

        // La page tient dans la case, à la résolution réelle de l'écran.
        const echelle = Math.min((boite.width - 32) / base.width, (boite.height - 32) / base.height);
        const dpr = Math.min(window.devicePixelRatio || 1, 2.5);
        const vue = page.getViewport({ scale: echelle * dpr });

        const canevas = document.createElement('canvas');
        canevas.width = vue.width;
        canevas.height = vue.height;
        canevas.style.width = `${vue.width / dpr}px`;
        canevas.style.height = `${vue.height / dpr}px`;

        await page.render({ canvasContext: canevas.getContext('2d'), viewport: vue }).promise;
        cases[n - 1].replaceChildren(canevas);
    };

    let courante = 1;

    const aller = (n) => {
        n = Math.max(1, Math.min(doc.numPages, n));
        piste.scrollTo({ left: cases[n - 1].offsetLeft, behavior: 'smooth' });
    };

    const suivre = () => {
        const n = Math.round(piste.scrollLeft / piste.clientWidth) + 1;
        if (n === courante && dessinees.has(n)) return;
        courante = n;
        compteur.textContent = `${n} / ${doc.numPages}`;
        avant.disabled = n <= 1;
        apres.disabled = n >= doc.numPages;
        [n, n + 1, n - 1, n + 2].forEach(dessiner);
    };

    piste.addEventListener('scroll', () => requestAnimationFrame(suivre), { passive: true });
    avant.addEventListener('click', () => aller(courante - 1));
    apres.addEventListener('click', () => aller(courante + 1));
    piste.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowRight') { e.preventDefault(); aller(courante + 1); }
        if (e.key === 'ArrowLeft')  { e.preventDefault(); aller(courante - 1); }
    });

    document.getElementById('plein-ecran').addEventListener('click', () => {
        if (document.fullscreenElement) document.exitFullscreen();
        else liseuse.requestFullscreen?.();
    });

    // Au changement de taille (rotation, plein écran), on redessine net.
    let attente;
    const redessiner = () => {
        clearTimeout(attente);
        attente = setTimeout(() => {
            dessinees.clear();
            cases.forEach((c) => c.replaceChildren());
            piste.scrollLeft = cases[courante - 1].offsetLeft;
            [courante, courante + 1, courante - 1].forEach(dessiner);
        }, 200);
    };
    addEventListener('resize', redessiner);
    document.addEventListener('fullscreenchange', redessiner);

    courante = 0;
    suivre();
})();
</script>
@endpush

@endsection
