{{-- Rappel tant que l'identité légale est incomplète : aucune facture ne peut être émise. --}}
@if (! empty($manquants))
    <div class="carte" style="border-color:#E9C46A; background:#FFF8E1">
        <h2 style="margin-bottom:.4rem"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true" style="color:#A06A00"></i> Mentions légales à compléter</h2>
        <p class="carte-aide" style="margin:0 0 .5rem">
            Une facture française doit porter ces mentions : tant qu’elles manquent, les devis restent possibles mais aucune
            facture ne peut être émise. Renseignez-les dans le fichier <code>.env</code> (variables <code>SOLEN_ENTREPRISE_…</code>).
        </p>
        <p style="margin:0; font-weight:600">{{ implode(' · ', $manquants) }}</p>
    </div>
@endif
