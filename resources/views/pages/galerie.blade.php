@extends('layout')
@section('titre', 'Galerie')
@section('content')

{{--
    Le mur photo. Le sélecteur de fichiers du téléphone propose déjà
    l'appareil photo et la pellicule : inutile de rejouer une caméra en
    JavaScript. On accepte plusieurs photos d'un coup, avec un aperçu.
--}}

<header class="page-tete">
    <p class="page-tete-sur">Vos souvenirs</p>
    <h1>La galerie</h1>
    @if ($consigne)
        <p>{{ $consigne }}</p>
    @endif
</header>

<div class="cadre--etroit" style="margin-inline:auto">
    @if (session('success'))
        <div class="message message--succes" role="status">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('erreur'))
        <div class="message message--alerte" role="alert">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><span>{{ session('erreur') }}</span>
        </div>
    @endif

    @if ($pleine)
        <div class="vide">
            <i class="fa-solid fa-images" aria-hidden="true"></i>
            La galerie a atteint sa capacité maximale. Merci pour toutes ces photos !
        </div>
    @else
        <form method="POST" action="{{ route('galerie.store') }}" enctype="multipart/form-data" class="bloc" id="envoi">
            @csrf
            <h2 class="bloc-titre">
                <span class="pastille"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i></span>
                Partager mes photos
            </h2>

            <div class="grille-2">
                <label class="champ">
                    <span>Prénom</span>
                    <input type="text" name="prenom" class="saisie" value="{{ old('prenom') }}" autocomplete="given-name" required>
                </label>
                <label class="champ">
                    <span>Nom <small>(facultatif)</small></span>
                    <input type="text" name="nom" class="saisie" value="{{ old('nom') }}" autocomplete="family-name">
                </label>
            </div>

            <label class="depot" id="depot">
                <input type="file" name="photos[]" accept="image/*" multiple required id="fichiers" class="visuellement-cache">
                <i class="fa-solid fa-camera" aria-hidden="true"></i>
                <strong>Choisir ou prendre des photos</strong>
                <small>Jusqu’à 20 à la fois · {{ $poidsMax }} Mo par photo</small>
            </label>
            {{-- Prise de vue en direct : affichée seulement si le navigateur
                 donne accès à la caméra. --}}
            <button type="button" class="bouton bouton--contour bouton--large camera-ouvrir" id="camera-ouvrir" hidden>
                <i class="fa-solid fa-camera-retro" aria-hidden="true"></i> Photo en direct
            </button>
            <div class="apercus" id="apercus"></div>

            @if ($errors->any())
                <span class="champ-erreur">{{ $errors->first() }}</span>
            @endif

            <button class="bouton bouton--plein bouton--large" style="margin-top:1rem" id="envoyer">
                <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Envoyer
            </button>
        </form>
    @endif
</div>

<section class="section">
    <div class="section-tete">
        <h2>Les photos des invités</h2>
        @if ($photos->isNotEmpty())
            <span class="doux">{{ $photos->count() }}</span>
        @endif
    </div>

    @if ($photos->isEmpty())
        <div class="vide">
            <i class="fa-regular fa-image" aria-hidden="true"></i>
            Aucune photo pour l’instant. Soyez le premier à partager la vôtre !
        </div>
    @else
        <div class="mosaique">
            @foreach ($photos as $photo)
                <button type="button" class="mosaique-item" data-photo="{{ Storage::url($photo->path) }}"
                        data-auteur="{{ $photo->participant?->prenom }}">
                    <img src="{{ Storage::url($photo->path) }}" alt="Photo de {{ $photo->participant?->prenom }}" loading="lazy">
                </button>
            @endforeach
        </div>
    @endif
</section>

<dialog class="visionneuse" id="visionneuse" aria-label="Photo">
    <img id="visionneuse-image" alt="">
    <div class="visionneuse-barre">
        <span id="visionneuse-auteur"></span>
        <span style="display:flex; gap:.5rem">
            @if ($telechargement)
                <a id="visionneuse-telecharger" class="bouton bouton--clair bouton--petit" download>
                    <i class="fa-solid fa-download" aria-hidden="true"></i> Enregistrer
                </a>
            @endif
            <button type="button" class="bouton bouton--clair bouton--petit" data-fermer>
                <i class="fa-solid fa-xmark" aria-hidden="true"></i> Fermer
            </button>
        </span>
    </div>
</dialog>

<dialog class="modale camera" id="camera" aria-label="Prendre une photo">
    <div class="camera-scene">
        <video id="camera-flux" autoplay playsinline muted></video>
        <img id="camera-cliche" alt="Votre photo" hidden>
        <div class="camera-flash" id="camera-flash"></div>
        <span class="camera-compte" id="camera-compte" hidden></span>
    </div>
    <p class="camera-message" id="camera-message"></p>
    <div class="camera-commandes" data-etape="direct">
        <button type="button" class="camera-rond" data-camera="retourner" aria-label="Changer de caméra">
            <i class="fa-solid fa-camera-rotate" aria-hidden="true"></i>
        </button>
        <button type="button" class="camera-declencheur" data-camera="declencher" aria-label="Prendre la photo"></button>
        <button type="button" class="camera-rond" data-camera="fermer" aria-label="Fermer">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>
    <div class="camera-commandes" data-etape="cliche" hidden>
        <button type="button" class="bouton bouton--contour" data-camera="reprendre">
            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reprendre
        </button>
        <button type="button" class="bouton bouton--plein" data-camera="garder">
            <i class="fa-solid fa-check" aria-hidden="true"></i> Garder
        </button>
    </div>
</dialog>

@push('scripts')
<script>
(() => {
    const champ   = document.getElementById('fichiers');
    const apercus = document.getElementById('apercus');
    const bouton  = document.getElementById('envoyer');

    champ?.addEventListener('change', () => {
        apercus.replaceChildren(...[...champ.files].slice(0, 20).map((f) => {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(f);
            img.alt = '';
            return img;
        }));
        const n = champ.files.length;
        bouton.innerHTML = `<i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Envoyer ${n > 1 ? n + ' photos' : 'la photo'}`;
    });

    // ── Photo en direct ───────────────────────────────────────────────
    // Chaque cliché gardé rejoint les fichiers du formulaire : l'envoi,
    // le poids maximal et la modération restent ceux de la galerie.
    const ouvrir  = document.getElementById('camera-ouvrir');
    const camera  = document.getElementById('camera');
    const flux    = document.getElementById('camera-flux');
    const cliche  = document.getElementById('camera-cliche');
    const message = document.getElementById('camera-message');
    const compte  = document.getElementById('camera-compte');
    const etapes  = camera?.querySelectorAll('[data-etape]') ?? [];
    let flot = null, cote = 'environment', blob = null, gardees = 0;

    if (ouvrir && camera?.showModal && navigator.mediaDevices?.getUserMedia && window.DataTransfer) {
        ouvrir.hidden = false;
    }

    const etape = (nom) => etapes.forEach((e) => e.hidden = e.dataset.etape !== nom);

    const couper = () => { flot?.getTracks().forEach((t) => t.stop()); flot = null; };

    async function demarrer() {
        couper();
        message.textContent = '';
        try {
            flot = await navigator.mediaDevices.getUserMedia({ video: { facingMode: cote, width: { ideal: 1920 } }, audio: false });
            flux.srcObject = flot;
            // Le selfie en miroir, comme dans un miroir ; la vue arrière telle quelle.
            flux.classList.toggle('miroir', cote === 'user');
        } catch (e) {
            message.textContent = 'Autorisez l’accès à la caméra pour prendre une photo.';
        }
    }

    function montrer(enDirect) {
        flux.hidden = !enDirect;
        cliche.hidden = enDirect;
        etape(enDirect ? 'direct' : 'cliche');
    }

    function declencher() {
        if (!flux.videoWidth) return;
        const c = document.createElement('canvas');
        c.width = flux.videoWidth;
        c.height = flux.videoHeight;
        const ctx = c.getContext('2d');
        if (cote === 'user') { ctx.translate(c.width, 0); ctx.scale(-1, 1); }
        ctx.drawImage(flux, 0, 0);

        const flash = document.getElementById('camera-flash');
        flash.classList.add('actif');
        setTimeout(() => flash.classList.remove('actif'), 60);

        c.toBlob((b) => {
            blob = b;
            cliche.src = URL.createObjectURL(b);
            montrer(false);
        }, 'image/jpeg', 0.9);
    }

    function garder() {
        if (!blob) return;
        const dt = new DataTransfer();
        [...champ.files].forEach((f) => dt.items.add(f));
        dt.items.add(new File([blob], `photo-${Date.now()}.jpg`, { type: 'image/jpeg' }));
        champ.files = dt.files;
        champ.dispatchEvent(new Event('change'));

        gardees++;
        compte.textContent = `${gardees} photo${gardees > 1 ? 's' : ''} gardée${gardees > 1 ? 's' : ''}`;
        compte.hidden = false;
        blob = null;
        montrer(true);
    }

    ouvrir?.addEventListener('click', () => {
        gardees = 0;
        compte.hidden = true;
        montrer(true);
        camera.showModal();
        demarrer();
    });

    camera?.addEventListener('click', (e) => {
        const action = e.target.closest('[data-camera]')?.dataset.camera;
        if (action === 'declencher') declencher();
        if (action === 'reprendre') { blob = null; montrer(true); }
        if (action === 'garder') garder();
        if (action === 'retourner') { cote = cote === 'user' ? 'environment' : 'user'; demarrer(); }
        if (action === 'fermer') camera.close();
    });

    // Fermer la fenêtre, par le bouton ou par Échap, coupe la caméra.
    camera?.addEventListener('close', couper);

    document.getElementById('envoi')?.addEventListener('submit', () => {
        bouton.disabled = true;
        bouton.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Envoi en cours…';
    });

    const v = document.getElementById('visionneuse');
    document.querySelectorAll('[data-photo]').forEach((b) => b.addEventListener('click', () => {
        document.getElementById('visionneuse-image').src = b.dataset.photo;
        document.getElementById('visionneuse-auteur').textContent = b.dataset.auteur ? `Par ${b.dataset.auteur}` : '';
        const t = document.getElementById('visionneuse-telecharger');
        if (t) t.href = b.dataset.photo;
        v.showModal();
    }));
    v.querySelector('[data-fermer]').addEventListener('click', () => v.close());
    v.addEventListener('click', (e) => { if (e.target === v) v.close(); });
})();
</script>
@endpush

@endsection
