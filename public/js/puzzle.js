/*
 * Puzzle — mécanique reprise du projet puzzle_game (GSAP + Draggable).
 *
 * Vingt pièces SVG remplies par l'image du mariage. Au départ, elles sont
 * rangées en rangées au-dessus et au-dessous du cadre ; une pièce lâchée
 * près de sa place s'y aimante et se verrouille. Quand toutes sont posées,
 * l'image complète se dévoile et le score part au serveur.
 *
 * Ajouts propres à Solen : chronomètre, envoi du temps, bouton « Résoudre »
 * qui termine la partie sans rapporter de points.
 */
document.addEventListener('DOMContentLoaded', () => {
    gsap.registerPlugin(Draggable);

    const puzzle = document.querySelector('.puzzle');
    if (!puzzle) return;

    const config = window.puzzleConfig;
    const pieces = puzzle.querySelector('.pieces');
    const paths = puzzle.querySelectorAll(':scope > path');
    const endImg = puzzle.querySelector('.endImg');
    const box = puzzle.querySelector('.box');
    const ghost = puzzle.querySelector('.ghost');

    const solveButton = document.getElementById('puzzle-resoudre');
    const chrono = document.getElementById('puzzle-chrono');
    const fin = document.getElementById('puzzle-fin');

    // Repères : viewBox du SVG, et taille du puzzle assemblé.
    const VIEW_W = 220;
    const VIEW_H = 400;
    const SRC_W = 125;
    const SRC_H = 100;

    const COLS = 4;
    const MARGIN = 3;
    const GAP = 6;
    const ROW_GAP = 3;
    const PIECE_GAP = 4;

    const boxes = [...paths].map((path) => path.getBBox());

    // Rangées de départ, par hauteur décroissante, mélangées.
    const byHeight = boxes.map((b, i) => i).sort((a, b) => boxes[b].height - boxes[a].height);
    const rows = [];
    for (let i = 0; i < byHeight.length; i += COLS) {
        rows.push(gsap.utils.shuffle(byHeight.slice(i, i + COLS)));
    }
    gsap.utils.shuffle(rows);

    const rowsTop = Math.floor(rows.length / 2);
    const rowWidths = rows.map((row) => row.reduce((t, i) => t + boxes[i].width, 0));
    const rowHeights = rows.map((row) => Math.max(...row.map((i) => boxes[i].height)));

    const scale = Math.min(
        (VIEW_W - MARGIN * 2 - PIECE_GAP * (COLS - 1)) / Math.max(SRC_W, ...rowWidths),
        (VIEW_H - MARGIN * 2 - GAP * 2 - ROW_GAP * rows.length) /
            (rowHeights.reduce((t, h) => t + h, 0) + SRC_H)
    );

    const boardW = SRC_W * scale;
    const boardH = SRC_H * scale;
    const totalH = rowHeights.reduce((t, h) => t + h * scale, 0) + ROW_GAP * rows.length + boardH + GAP * 2;
    const PUZZLE_X = (VIEW_W - boardW) / 2;

    let cursorY = (VIEW_H - totalH) / 2;
    let puzzleY = 0;
    const slots = [];

    rows.forEach((row, rowIndex) => {
        if (rowIndex === rowsTop) {
            cursorY += GAP;
            puzzleY = cursorY;
            cursorY += boardH + GAP;
        }

        const rowHeight = rowHeights[rowIndex] * scale;
        const gap = (VIEW_W - rowWidths[rowIndex] * scale) / (row.length + 1);
        let cursorX = gap;

        row.forEach((index) => {
            const width = boxes[index].width * scale;
            slots[index] = { x: cursorX + width / 2, y: cursorY + rowHeight / 2 };
            cursorX += width + gap;
        });

        cursorY += rowHeight + ROW_GAP;
    });

    const PUZZLE_Y = puzzleY;
    const entries = [];
    let finished = false;
    let debut = null;
    let minuteur = null;

    const secondes = () => (debut ? Math.max(1, Math.round((Date.now() - debut) / 1000)) : 1);

    const demarrer = () => {
        if (debut) return;
        debut = Date.now();
        minuteur = setInterval(() => {
            const s = secondes();
            chrono.textContent = `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
        }, 500);
    };

    function endGame(resolu) {
        if (finished) return;
        finished = true;
        clearInterval(minuteur);

        fin.querySelector('[name="temps"]').value = secondes();
        fin.querySelector('[name="resolu"]').value = resolu ? 1 : 0;
        fin.querySelector('[data-titre]').textContent = resolu
            ? 'Le puzzle est reconstitué.'
            : `Bravo ! Reconstitué en ${chrono.textContent}.`;
        fin.hidden = false;
        fin.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    let resoluAuto = false;

    function reveal() {
        puzzle.appendChild(endImg);
        gsap.to(endImg, { duration: 1, opacity: 1, ease: 'power2.inOut', onComplete: () => endGame(resoluAuto) });
    }

    function check() {
        const complete = [...puzzle.querySelectorAll('.piece')].every((piece) =>
            Math.abs(Number(gsap.getProperty(piece, 'x'))) < 1 &&
            Math.abs(Number(gsap.getProperty(piece, 'y'))) < 1
        );
        if (complete) reveal();
    }

    function solve() {
        if (finished) return;
        resoluAuto = true;

        entries.forEach((entry) => {
            entry.draggable.disable();
            pieces.insertBefore(entry.piece, pieces.firstChild);
            gsap.to(entry.shadow, { duration: 0.4, opacity: 0 });
        });

        gsap.to(entries.map((e) => e.piece), {
            duration: 0.5, x: 0, y: 0, scale: 1, rotation: 0,
            ease: 'power2.inOut', stagger: 0.05, onComplete: reveal,
        });
    }

    solveButton?.addEventListener('click', () => {
        if (!confirm('Résoudre le puzzle ? La partie ne rapportera aucun point.')) return;
        solveButton.disabled = true;
        solve();
    });

    // Cadre de dépôt et image finale, à l'échelle du puzzle.
    gsap.set(box, { attr: { x: PUZZLE_X, y: PUZZLE_Y, width: boardW, height: boardH } });

    const boardTransform = `translate(${PUZZLE_X} ${PUZZLE_Y}) scale(${scale})`;
    pieces.setAttribute('transform', boardTransform);
    endImg.setAttribute('transform', boardTransform);

    // Silhouettes discrètes des pièces dans le cadre.
    if (ghost) {
        ghost.setAttribute('transform', boardTransform);
        paths.forEach((path) => {
            const outline = path.cloneNode(true);
            ghost.appendChild(outline);
            gsap.set(outline, { attr: { fill: 'none', stroke: 'currentColor', 'stroke-opacity': 0.25, 'stroke-width': 0.4 } });
        });
    }

    paths.forEach((path, index) => {
        const piece = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        const shadow = path.cloneNode(true);

        pieces.appendChild(piece);
        piece.append(shadow, path);

        const bounds = boxes[index];
        const slot = slots[index];
        const startX = (slot.x - PUZZLE_X) / scale - (bounds.x + bounds.width / 2);
        const startY = (slot.y - PUZZLE_Y) / scale - (bounds.y + bounds.height / 2);

        gsap.set(piece, {
            transformOrigin: '50% 50%', x: startX, y: startY,
            rotation: gsap.utils.random(-2.5, 2.5), attr: { class: 'piece' },
        });
        gsap.set(shadow, { opacity: 0.35 });
        gsap.set(path, { attr: { fill: 'url(#img)', filter: 'url(#bevel)' } });

        const [draggable] = Draggable.create(piece, {
            type: 'x,y',
            bounds: puzzle,
            edgeResistance: 0.85,

            onPress: () => {
                demarrer();
                pieces.appendChild(piece);
                gsap.to(piece, { scale: 1.1, rotation: gsap.utils.random(-3, 3), duration: 0.2 });
            },

            onRelease: () => {
                const x = Number(gsap.getProperty(piece, 'x'));
                const y = Number(gsap.getProperty(piece, 'y'));

                // Assez proche de sa place : la pièce s'aimante et se verrouille.
                if (Math.abs(x) < 10 && Math.abs(y) < 10) {
                    draggable.disable();
                    pieces.insertBefore(piece, pieces.firstChild);
                    gsap.to(piece, { duration: 0.2, x: 0, y: 0, scale: 1, rotation: 0, onComplete: check });
                    gsap.to(shadow, { duration: 0.2, opacity: 0 });
                    return;
                }

                gsap.to(piece, { scale: 1, duration: 0.2 });
            },
        });

        entries.push({ piece, shadow, draggable });
    });

    gsap.set('#imgSrc', { attr: { href: config.image } });
});
