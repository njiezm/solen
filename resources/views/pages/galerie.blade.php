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
                    <span>Nom</span>
                    <input type="text" name="nom" class="saisie" value="{{ old('nom') }}" autocomplete="family-name" required>
                </label>
            </div>

            <label class="depot" id="depot">
                <input type="file" name="photos[]" accept="image/*" multiple required id="fichiers" class="visuellement-cache">
                <i class="fa-solid fa-camera" aria-hidden="true"></i>
                <strong>Choisir ou prendre des photos</strong>
                <small>Jusqu’à 20 à la fois · {{ $poidsMax }} Mo par photo</small>
            </label>
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
