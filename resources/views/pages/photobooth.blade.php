@extends('layout')

@section('content')

{{--
    Tout se passe dans le navigateur : la caméra, le compte à rebours, le
    montage des poses, le cadre et le filigrane. Rien ne part au serveur
    tant que l'invité n'a pas validé son cliché.
--}}

<style>
    .booth { max-width: 620px; margin-inline: auto; }
    .booth-scene {
        position: relative; border-radius: 18px; overflow: hidden;
        background: #111; aspect-ratio: 3 / 4;
        display: grid; place-items: center;
    }
    .booth-scene video, .booth-scene canvas, .booth-scene img {
        width: 100%; height: 100%; object-fit: cover; display: block;
    }
    /* La caméra frontale est plus naturelle en miroir. */
    .booth-scene video { transform: scaleX(-1); }
    /* Cadre à fenêtre : la scène prend le format du cadre, la caméra
       se loge dans sa zone transparente. */
    .booth-scene.avec-fenetre { background: #fff; }
    .booth-scene.avec-fenetre video { position: absolute; }
    .booth-cadre {
        position: absolute; inset: 0; width: 100%; height: 100%;
        object-fit: cover; pointer-events: none;
    }
    .booth-rebours {
        position: absolute; inset: 0; display: grid; place-items: center;
        font-size: 8rem; font-weight: 700; color: #fff;
        text-shadow: 0 4px 30px rgba(0,0,0,.6);
        background: rgba(0,0,0,.25);
    }
    .booth-flash {
        position: absolute; inset: 0; background: #fff; opacity: 0;
        pointer-events: none; transition: opacity .35s ease;
    }
    .booth-flash.actif { opacity: 1; transition: none; }
    .booth-etat {
        position: absolute; top: .8rem; left: .8rem;
        background: rgba(0,0,0,.55); color: #fff;
        font-size: .78rem; padding: .3rem .7rem; border-radius: 999px;
    }
    .booth-actions { display: flex; flex-wrap: wrap; gap: .6rem; justify-content: center; margin-top: 1.2rem; }
    .booth-message { text-align: center; margin-top: 1rem; min-height: 1.5rem; font-weight: 600; }
    .booth-identite { display: flex; gap: .6rem; margin-top: 1rem; }
    .booth-identite { margin-top: .7rem; }
    .booth-identite input {
        /* 16 px minimum : en dessous, iOS zoome au focus et décale la page. */
        flex: 1; font: inherit; font-size: 16px; padding: .6rem .85rem;
        border: 1px solid rgba(0,0,0,.15); border-radius: 10px;
        min-width: 0;
    }
    .booth-mention {
        font-size: .74rem; color: rgba(0,0,0,.5);
        margin: .4rem 0 0; text-align: center;
    }
    .booth-derniere { margin-top: 1.5rem; text-align: center; }
    .booth-derniere img { max-width: 180px; border-radius: 12px; }

    /* Mode borne : plein écran, gros boutons, aucune distraction. */
    body.borne .navbar, body.borne .footer-pro { display: none; }
    body.borne .main-content { padding-top: 0; }
    body.borne .booth { max-width: 760px; }
    body.borne .btn { font-size: 1.15rem; padding: 1rem 2.4rem; }
</style>

<div class="booth" id="booth"
     data-poses="{{ $poses }}"
     data-rebours="{{ $rebours }}"
     data-borne="{{ $borne ? '1' : '0' }}"
     data-cadre="{{ $cadre ?? '' }}"
     data-filigrane="{{ $filigrane ? '1' : '0' }}"
     data-signature="{{ $signature }}"
     data-url="{{ route('photobooth.stocker') }}"
     data-token="{{ csrf_token() }}">

    <h2 class="text-center ceremony-heading">
        <i class="fa-solid fa-camera-retro me-2"></i> Photobooth
    </h2>

    <p class="text-center mb-4">{{ $consigne }}</p>

    <div class="booth-scene">
        <video id="flux" autoplay playsinline muted></video>
        <canvas id="rendu" hidden></canvas>
        <img id="apercu" hidden alt="Votre photo">
        @if ($cadre)
            <img class="booth-cadre" src="{{ $cadre }}" alt="" id="cadre">
        @endif
        <div class="booth-rebours" id="rebours" hidden></div>
        <div class="booth-flash" id="flash"></div>
        <div class="booth-etat" id="etat" hidden></div>
    </div>

    <div class="booth-identite">
        <input id="prenom" placeholder="Votre prénom" maxlength="60" autocomplete="given-name">
        @unless ($borne)
            <input id="nom" placeholder="Votre nom" maxlength="60" autocomplete="family-name">
        @endunless
    </div>

    {{-- L'adresse ne sert qu'à l'envoi : on le dit, et on ne la conserve pas. --}}
    <div class="booth-identite">
        <input id="email" type="email" placeholder="Votre e-mail, pour recevoir la photo (facultatif)"
               maxlength="255" autocomplete="email" inputmode="email">
    </div>
    <p class="booth-mention">Votre adresse ne sert qu’à vous envoyer ce cliché.</p>

    <div class="booth-actions">
        <button class="btn btn-pro-primary" id="declencher">
            <i class="fa-solid fa-camera me-2"></i> Prendre la photo
        </button>
        <button class="btn btn-outline-secondary" id="recommencer" hidden>Recommencer</button>
        <button class="btn btn-pro-primary" id="valider" hidden>
            <i class="fa-solid fa-check me-2"></i> Envoyer aux mariés
        </button>
    </div>

    <p class="booth-message" id="message"></p>

    <div class="booth-derniere" id="derniere" hidden>
        <p class="small text-muted mb-2">Votre dernière photo</p>
        <img id="derniere-img" alt="">
    </div>

    <p class="text-center mt-4">
        <a href="{{ route('galerie.index') }}">Voir le mur photo</a>
    </p>
</div>

<script>
(() => {
    const b = document.getElementById('booth');
    const conf = {
        poses:     Math.max(1, parseInt(b.dataset.poses, 10) || 1),
        rebours:   parseInt(b.dataset.rebours, 10) || 0,
        borne:     b.dataset.borne === '1',
        cadre:     b.dataset.cadre || null,
        filigrane: b.dataset.filigrane === '1',
        signature: b.dataset.signature,
        url:       b.dataset.url,
        token:     b.dataset.token,
    };

    const scene   = document.querySelector('.booth-scene');
    const flux    = document.getElementById('flux');
    const rendu   = document.getElementById('rendu');
    const apercu  = document.getElementById('apercu');
    const cadreEl = document.getElementById('cadre');
    const reboursEl = document.getElementById('rebours');
    const flash   = document.getElementById('flash');
    const etat    = document.getElementById('etat');
    const message = document.getElementById('message');

    const bDeclencher  = document.getElementById('declencher');
    const bRecommencer = document.getElementById('recommencer');
    const bValider     = document.getElementById('valider');

    if (conf.borne) document.body.classList.add('borne');

    let composee = null;
    let occupe   = false;

    const dire = (texte, erreur = false) => {
        message.textContent = texte;
        message.style.color = erreur ? '#B3261E' : '';
    };

    const attendre = (ms) => new Promise((r) => setTimeout(r, ms));

    // ── Caméra ───────────────────────────────────────────────────────
    async function demarrerCamera() {
        if (!navigator.mediaDevices?.getUserMedia) {
            dire('Votre navigateur ne permet pas d’accéder à la caméra.', true);
            bDeclencher.disabled = true;
            return;
        }

        try {
            flux.srcObject = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 1707 } },
                audio: false,
            });
        } catch (e) {
            dire('Autorisez l’accès à la caméra pour utiliser le photobooth.', true);
            bDeclencher.disabled = true;
        }
    }

    // ── Cadre ────────────────────────────────────────────────────────
    // Un cadre plein (bordure seule) se pose sur la photo, comme avant.
    // Un cadre à fenêtre (un faire-part, un visuel avec prénoms et date)
    // impose son format : la photo est logée dans sa zone transparente.
    let cadreImg = null;
    let fenetre  = null; // { x, y, l, h } en fractions du cadre

    const charger = (src) => new Promise((resolve) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload  = () => resolve(img);
        img.onerror = () => resolve(null);
        img.src = src;
    });

    async function analyserCadre() {
        if (!conf.cadre) return;
        cadreImg = await charger(conf.cadre);
        if (!cadreImg) return;

        // Analyse sur une miniature : la fenêtre se repère très bien à 240 px.
        const l = 240;
        const h = Math.round(l * cadreImg.naturalHeight / cadreImg.naturalWidth);
        const c = document.createElement('canvas');
        c.width = l; c.height = h;
        const ctx = c.getContext('2d');
        ctx.drawImage(cadreImg, 0, 0, l, h);

        let donnees;
        try { donnees = ctx.getImageData(0, 0, l, h).data; } catch (e) { return; }

        let x0 = l, y0 = h, x1 = -1, y1 = -1;
        for (let y = 0; y < h; y++) {
            for (let x = 0; x < l; x++) {
                if (donnees[(y * l + x) * 4 + 3] < 20) {
                    if (x < x0) x0 = x; if (x > x1) x1 = x;
                    if (y < y0) y0 = y; if (y > y1) y1 = y;
                }
            }
        }

        // Pas de transparence, ou transparent presque partout : cadre plein.
        if (x1 < 0 || (x1 - x0) * (y1 - y0) > l * h * 0.9) return;

        fenetre = { x: x0 / l, y: y0 / h, l: (x1 - x0 + 1) / l, h: (y1 - y0 + 1) / h };

        scene.classList.add('avec-fenetre');
        scene.style.aspectRatio = `${cadreImg.naturalWidth} / ${cadreImg.naturalHeight}`;
        Object.assign(flux.style, {
            left:   `${fenetre.x * 100}%`, top:    `${fenetre.y * 100}%`,
            width:  `${fenetre.l * 100}%`, height: `${fenetre.h * 100}%`,
        });
    }

    /** Dessine une pose en la recadrant pour remplir exactement la zone. */
    function couvrir(ctx, pose, x, y, l, h) {
        const ratio = Math.max(l / pose.width, h / pose.height);
        const sl = l / ratio, sh = h / ratio;
        ctx.drawImage(pose, (pose.width - sl) / 2, (pose.height - sh) / 2, sl, sh, x, y, l, h);
    }

    // ── Capture d'une pose ───────────────────────────────────────────
    function capturer() {
        const c = document.createElement('canvas');
        c.width  = flux.videoWidth;
        c.height = flux.videoHeight;

        const ctx = c.getContext('2d');
        // On retourne l'image pour qu'elle corresponde à ce que l'invité a vu.
        ctx.translate(c.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(flux, 0, 0);

        return c;
    }

    async function compteARebours() {
        if (conf.rebours <= 0) return;

        reboursEl.hidden = false;
        for (let n = conf.rebours; n > 0; n--) {
            reboursEl.textContent = n;
            await attendre(1000);
        }
        reboursEl.hidden = true;
    }

    async function eclair() {
        flash.classList.add('actif');
        await attendre(60);
        flash.classList.remove('actif');
    }

    // ── Montage final ────────────────────────────────────────────────
    async function composer(poses) {
        if (fenetre) return composerDansFenetre(poses);

        const l = poses[0].width;
        const h = poses[0].height;
        const marge = Math.round(l * 0.03);

        const c = document.createElement('canvas');
        c.width  = l + marge * 2;
        c.height = poses.length * h + marge * (poses.length + 1);

        const ctx = c.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, c.width, c.height);

        poses.forEach((pose, i) => {
            ctx.drawImage(pose, marge, marge + i * (h + marge), l, h);
        });

        // Cadre par-dessus l'ensemble.
        if (conf.cadre) {
            await new Promise((resolve) => {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload  = () => { ctx.drawImage(img, 0, 0, c.width, c.height); resolve(); };
                img.onerror = resolve;
                img.src = conf.cadre;
            });
        }

        if (conf.filigrane && conf.signature) {
            const taille = Math.max(16, Math.round(c.width * 0.032));
            ctx.font = `600 ${taille}px Inter, system-ui, sans-serif`;
            ctx.textAlign = 'center';
            ctx.fillStyle = 'rgba(0,0,0,.55)';
            ctx.fillText(conf.signature, c.width / 2, c.height - marge * 0.9);
        }

        return c;
    }

    function composerDansFenetre(poses) {
        const L = Math.min(1440, cadreImg.naturalWidth);
        const H = Math.round(L * cadreImg.naturalHeight / cadreImg.naturalWidth);
        const c = document.createElement('canvas');
        c.width = L; c.height = H;

        const ctx = c.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, L, H);

        // Plusieurs poses se partagent la fenêtre, empilées.
        const fx = fenetre.x * L, fy = fenetre.y * H, fl = fenetre.l * L, fh = fenetre.h * H;
        const marge   = poses.length > 1 ? Math.round(fl * 0.02) : 0;
        const hauteur = (fh - marge * (poses.length - 1)) / poses.length;

        poses.forEach((pose, i) => couvrir(ctx, pose, fx, fy + i * (hauteur + marge), fl, hauteur));
        ctx.drawImage(cadreImg, 0, 0, L, H);

        if (conf.filigrane && conf.signature) {
            const taille = Math.max(14, Math.round(fl * 0.035));
            ctx.font = `600 ${taille}px Inter, system-ui, sans-serif`;
            ctx.textAlign = 'center';
            ctx.fillStyle = 'rgba(255,255,255,.85)';
            ctx.fillText(conf.signature, fx + fl / 2, fy + fh - taille * 0.8);
        }

        return c;
    }

    // ── Séquence complète ────────────────────────────────────────────
    async function sequence() {
        if (occupe) return;
        occupe = true;

        bDeclencher.hidden = true;
        bRecommencer.hidden = true;
        bValider.hidden = true;
        dire('');

        const poses = [];

        for (let i = 0; i < conf.poses; i++) {
            if (conf.poses > 1) {
                etat.hidden = false;
                etat.textContent = `Pose ${i + 1} sur ${conf.poses}`;
            }

            await compteARebours();
            await eclair();
            poses.push(capturer());

            if (i < conf.poses - 1) await attendre(900);
        }

        etat.hidden = true;

        const montage = await composer(poses);
        composee = montage.toDataURL('image/jpeg', 0.9);

        apercu.src = composee;
        apercu.hidden = false;
        flux.hidden = true;
        if (cadreEl) cadreEl.hidden = true;

        bRecommencer.hidden = false;
        bValider.hidden = false;
        occupe = false;
    }

    function reprendre() {
        composee = null;
        apercu.hidden = true;
        flux.hidden = false;
        if (cadreEl) cadreEl.hidden = false;
        bRecommencer.hidden = true;
        bValider.hidden = true;
        bDeclencher.hidden = false;
        dire('');
    }

    async function envoyer() {
        if (!composee) return;

        bValider.disabled = true;
        dire('Envoi en cours…');

        try {
            const reponse = await fetch(conf.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': conf.token, 'Accept': 'application/json' },
                body: JSON.stringify({
                    image:  composee,
                    prenom: document.getElementById('prenom')?.value || null,
                    nom:    document.getElementById('nom')?.value || null,
                    email:  document.getElementById('email')?.value || null,
                }),
            });

            const data = await reponse.json();

            if (!reponse.ok) throw new Error(data.erreur || 'Envoi impossible.');

            dire(data.message || 'Merci !');
            document.getElementById('derniere').hidden = false;
            document.getElementById('derniere-img').src = data.url;

            // Sur une borne, on se remet tout seul en position pour le suivant.
            if (conf.borne) {
                await attendre(4000);
                dire('');
                reprendre();
            } else {
                reprendre();
            }
        } catch (e) {
            dire(e.message, true);
        } finally {
            bValider.disabled = false;
        }
    }

    bDeclencher.addEventListener('click', sequence);
    bRecommencer.addEventListener('click', reprendre);
    bValider.addEventListener('click', envoyer);

    analyserCadre();
    demarrerCamera();
})();
</script>

@endsection
