{{--
    Une grille de photos paginée, et sa visionneuse en diaporama.

    La grille n'affiche qu'une page (10 photos), mais la visionneuse fait
    défiler toutes les photos : on ouvre une image, on glisse jusqu'à la
    dernière sans repasser par la pagination.

    Paramètres : $photos (paginateur), $diaporama (liste [url, auteur] de
    toutes les photos, dans le même ordre), $telechargement, $ancre, $vide.
--}}
@php $base = $photos->firstItem() ? $photos->firstItem() - 1 : 0; @endphp

@if ($photos->isEmpty())
    <div class="vide">
        <i class="fa-regular fa-image" aria-hidden="true"></i>
        {{ $vide }}
    </div>
@else
    <div class="mosaique">
        @foreach ($photos as $photo)
            <button type="button" class="mosaique-item" data-diaporama="{{ $ancre }}" data-index="{{ $base + $loop->index }}"
                    aria-label="Agrandir la photo{{ $photo->participant?->prenom ? ' de ' . $photo->participant->prenom : '' }}">
                <img src="{{ Storage::url($photo->path) }}" alt="" loading="lazy">
            </button>
        @endforeach
    </div>

    @if ($photos->hasPages())
        <nav class="pagination-photos" aria-label="Pages de photos">
            @if ($photos->onFirstPage())
                <span class="bouton bouton--contour bouton--petit" aria-disabled="true"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></span>
            @else
                <a class="bouton bouton--contour bouton--petit" href="{{ $photos->previousPageUrl() }}" aria-label="Page précédente"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
            @endif
            <span>Page {{ $photos->currentPage() }} / {{ $photos->lastPage() }}</span>
            @if ($photos->hasMorePages())
                <a class="bouton bouton--contour bouton--petit" href="{{ $photos->nextPageUrl() }}" aria-label="Page suivante"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            @else
                <span class="bouton bouton--contour bouton--petit" aria-disabled="true"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
            @endif
        </nav>
    @endif

    <dialog class="visionneuse diaporama" id="diaporama-{{ $ancre }}" aria-label="Photos" data-photos='@json($diaporama)'>
        <div class="diaporama-scene">
            <img alt="" data-image>
            <button type="button" class="diaporama-fleche diaporama-fleche--prec" data-aller="-1" aria-label="Photo précédente"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
            <button type="button" class="diaporama-fleche diaporama-fleche--suiv" data-aller="1" aria-label="Photo suivante"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
        </div>
        <div class="visionneuse-barre">
            <span><span data-compteur></span> <span class="diaporama-auteur" data-auteur></span></span>
            <span style="display:flex; gap:.5rem">
                @if ($telechargement)
                    <a class="bouton bouton--clair bouton--petit" data-telecharger download>
                        <i class="fa-solid fa-download" aria-hidden="true"></i> Enregistrer
                    </a>
                @endif
                <button type="button" class="bouton bouton--clair bouton--petit" data-fermer aria-label="Fermer">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </span>
        </div>
    </dialog>

    @once
        @push('scripts')
        <script>
        (() => {
            document.querySelectorAll('dialog.diaporama').forEach((d) => {
                const photos = JSON.parse(d.dataset.photos || '[]');
                const img = d.querySelector('[data-image]');
                const compteur = d.querySelector('[data-compteur]');
                const auteur = d.querySelector('[data-auteur]');
                const telecharger = d.querySelector('[data-telecharger]');
                let i = 0;

                const montrer = (n) => {
                    i = (n + photos.length) % photos.length;
                    const [url, qui] = photos[i];
                    img.src = url;
                    compteur.textContent = `${i + 1} / ${photos.length}`;
                    auteur.textContent = qui ? `· ${qui}` : '';
                    if (telecharger) telecharger.href = url;
                    // L'image suivante se charge d'avance : le glissement reste fluide.
                    new Image().src = photos[(i + 1) % photos.length][0];
                };

                document.querySelectorAll(`[data-diaporama="${d.id.replace('diaporama-', '')}"]`).forEach((b) =>
                    b.addEventListener('click', () => { montrer(+b.dataset.index); d.showModal(); }));

                d.addEventListener('click', (e) => {
                    const aller = e.target.closest('[data-aller]');
                    if (aller) return montrer(i + +aller.dataset.aller);
                    if (e.target.closest('[data-fermer]') || e.target === d) d.close();
                });

                d.addEventListener('keydown', (e) => {
                    if (e.key === 'ArrowLeft') montrer(i - 1);
                    if (e.key === 'ArrowRight') montrer(i + 1);
                });

                // Glisser au doigt : à gauche, la suivante ; à droite, la précédente.
                let x0 = null;
                img.addEventListener('pointerdown', (e) => { x0 = e.clientX; });
                img.addEventListener('pointerup', (e) => {
                    if (x0 === null) return;
                    const dx = e.clientX - x0; x0 = null;
                    if (Math.abs(dx) > 40) montrer(i + (dx < 0 ? 1 : -1));
                });

                if (photos.length < 2) d.querySelectorAll('[data-aller]').forEach((b) => b.hidden = true);
            });
        })();
        </script>
        @endpush
    @endonce
@endif
