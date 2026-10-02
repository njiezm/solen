@extends('console.layout')

@php
    use App\Models\DocumentCommercial as D;
    $existant = $doc->exists;
    $lignes = old('lignes', collect($doc->lignes ?: [[]])->map(fn ($l) => [
        'designation' => $l['designation'] ?? '',
        'detail'      => $l['detail'] ?? '',
        'quantite'    => $l['quantite'] ?? 1,
        'prix'        => isset($l['prix_unitaire_centimes']) ? $l['prix_unitaire_centimes'] / 100 : '',
        'deduction'   => ! empty($l['deduction']),
    ])->all());
@endphp

@section('titre', ($existant ? 'Modifier ' : 'Nouveau ') . mb_strtolower($doc->nomDuType()))
@section('chapeau', $existant ? $doc->libelle() : 'Le document reste en brouillon tant que vous ne l’émettez pas.')

@section('contenu')

<form method="POST" action="{{ $existant ? route('console.facturation.maj', $doc) : route('console.facturation.stocker') }}" id="formulaire-doc">
    @csrf
    @if ($existant) @method('PUT') @else <input type="hidden" name="type" value="{{ $doc->type }}"> @endif

    <div class="carte">
        <h2>Client</h2>
        <div class="grille-champs">
            <div class="champ">
                <label for="event_id">Mariage concerné <span class="champ-aide" style="display:inline">(facultatif)</span></label>
                <select id="event_id" name="event_id">
                    <option value="">— Aucun, prospect —</option>
                    @foreach ($mariages as $m)
                        <option value="{{ $m->id }}" data-remplir='@json($m->remplissage)' @selected(old('event_id', $doc->event_id) == $m->id)>{{ $m->nom }} ({{ $m->slug }})</option>
                    @endforeach
                </select>
                <p class="champ-aide">Choisir un mariage remplit le client, l’objet et la formule.</p>
            </div>
            <div class="champ">
                <label for="client_nom">Nom du client <span class="champ-requis">*</span></label>
                <input type="text" id="client_nom" name="client_nom" value="{{ old('client_nom', $doc->client_nom) }}" required>
                @error('client_nom') <p class="champ-erreur">{{ $message }}</p> @enderror
            </div>
            <div class="champ">
                <label for="client_email">E-mail</label>
                <input type="email" id="client_email" name="client_email" value="{{ old('client_email', $doc->client_email) }}">
            </div>
            <div class="champ">
                <label for="client_telephone">Téléphone WhatsApp</label>
                <input type="tel" id="client_telephone" name="client_telephone" value="{{ old('client_telephone', $doc->client_telephone) }}" placeholder="0696 12 34 56">
            </div>
            <div class="champ" style="grid-column:1 / -1">
                <label for="client_adresse">Adresse</label>
                <textarea id="client_adresse" name="client_adresse" rows="2">{{ old('client_adresse', $doc->client_adresse) }}</textarea>
            </div>
            <div class="champ" style="grid-column:1 / -1">
                <label for="objet">Objet</label>
                <input type="text" id="objet" name="objet" value="{{ old('objet', $doc->objet) }}" placeholder="Mariage de Manon & Teddy — formule Signature">
            </div>
        </div>
    </div>

    <div class="carte">
        <h2>Prestations</h2>
        <p class="carte-aide">Ajoutez une ligne type d’un geste, puis ajustez. Le total se calcule seul.</p>

        <div style="display:flex; flex-wrap:wrap; gap:.4rem; margin-bottom:1rem">
            @foreach ($catalogue as $item)
                <button type="button" class="mini" data-catalogue='@json($item)'>+ {{ $item['designation'] }} · {{ $item['prix'] }} €</button>
            @endforeach
        </div>

        <div id="lignes" class="liste">
            @foreach ($lignes as $i => $l)
                <div class="ligne ligne-facture">
                    <div class="corps" style="display:grid; gap:.4rem">
                        <input type="text" name="lignes[{{ $i }}][designation]" value="{{ $l['designation'] }}" placeholder="Désignation" required>
                        <input type="text" name="lignes[{{ $i }}][detail]" value="{{ $l['detail'] }}" placeholder="Précision (facultatif)">
                        @if (! empty($l['deduction']))<input type="hidden" name="lignes[{{ $i }}][deduction]" value="1">@endif
                    </div>
                    <div class="outils" style="flex-wrap:nowrap">
                        <label class="visuellement-cache" for="q{{ $i }}">Quantité</label>
                        <input type="number" id="q{{ $i }}" name="lignes[{{ $i }}][quantite]" value="{{ $l['quantite'] }}" step="0.01" min="0.01" style="width:70px" data-q>
                        <label class="visuellement-cache" for="p{{ $i }}">Prix unitaire</label>
                        <input type="number" id="p{{ $i }}" name="lignes[{{ $i }}][prix]" value="{{ $l['prix'] }}" step="0.01" @if (empty($l['deduction'])) min="0" @endif style="width:100px" placeholder="€" data-p required>
                        <button type="button" class="mini mini--danger" data-retirer title="Retirer"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                    </div>
                </div>
            @endforeach
        </div>
        @error('lignes') <p class="champ-erreur">{{ $message }}</p> @enderror

        <div class="actions" style="justify-content:space-between">
            <button type="button" class="mini" id="ajouter-ligne">+ Ligne libre</button>
            <strong style="font-size:1.1rem">Sous-total : <span id="total">0,00 €</span></strong>
        </div>
    </div>

    @php
        $remiseActuelle = old('remise_choix', $doc->code_promo_id ? 'code:' . $doc->code_promo_id
            : ($doc->remise_type ? $doc->remise_type . ':' . (float) $doc->remise_valeur : ''));
        $presets = [
            'pourcentage:5' => '5 %', 'pourcentage:10' => '10 %', 'pourcentage:15' => '15 %', 'pourcentage:20' => '20 %',
            'pourcentage:25' => '25 %', 'montant:20' => '20 €', 'montant:50' => '50 €', 'montant:100' => '100 €',
        ];
    @endphp
    <div class="carte">
        <h2>Réduction</h2>
        <div class="grille-champs">
            <div class="champ">
                <label for="remise_choix">Réduction ou code promo</label>
                <select id="remise_choix" name="remise_choix">
                    <option value="">Aucune</option>
                    <optgroup label="Réduction">
                        @foreach ($presets as $valeur => $libelle)
                            <option value="{{ $valeur }}" @selected($remiseActuelle === $valeur)>Remise de {{ $libelle }}</option>
                        @endforeach
                    </optgroup>
                    @if ($codes->isNotEmpty())
                        <optgroup label="Codes promo">
                            @foreach ($codes as $c)
                                <option value="code:{{ $c->id }}" data-type="{{ $c->type }}" data-valeur="{{ (float) $c->valeur }}" @selected($remiseActuelle === 'code:' . $c->id)>{{ $c->description() }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                </select>
                @error('code_promo') <p class="champ-erreur">{{ $message }}</p> @enderror
                <p class="champ-aide"><a href="{{ route('console.facturation.codes') }}">Gérer les codes promo</a></p>
            </div>
        </div>

        <table class="totaux-direct">
            <tr><td>Sous-total</td><td id="t-sous-total">0,00 €</td></tr>
            <tr id="ligne-remise" hidden><td id="t-remise-libelle">Remise</td><td id="t-remise">0,00 €</td></tr>
            <tr class="fort"><td>Total</td><td id="t-total">0,00 €</td></tr>
        </table>
    </div>

    <div class="carte">
        <h2>Notes</h2>
        <div class="grille-champs">
            <div class="champ">
                <label for="notes">Imprimées sur le document</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Acompte de 30 % à la signature, solde un mois avant le mariage.">{{ old('notes', $doc->notes) }}</textarea>
            </div>
            <div class="champ">
                <label for="notes_internes">Internes <span class="champ-aide" style="display:inline">(jamais imprimées)</span></label>
                <textarea id="notes_internes" name="notes_internes" rows="3">{{ old('notes_internes', $doc->notes_internes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="actions">
        <button class="btn btn-primary">Enregistrer le brouillon</button>
        <a href="{{ $existant ? route('console.facturation.montrer', $doc) : route('console.facturation') }}" class="btn">Annuler</a>
    </div>
</form>

<template id="modele-ligne">
    <div class="ligne ligne-facture">
        <div class="corps" style="display:grid; gap:.4rem">
            <input type="text" name="lignes[__][designation]" placeholder="Désignation" required>
            <input type="text" name="lignes[__][detail]" placeholder="Précision (facultatif)">
        </div>
        <div class="outils" style="flex-wrap:nowrap">
            <input type="number" name="lignes[__][quantite]" value="1" step="0.01" min="0.01" style="width:70px" data-q aria-label="Quantité">
            <input type="number" name="lignes[__][prix]" step="0.01" min="0" style="width:100px" placeholder="€" data-p required aria-label="Prix unitaire">
            <button type="button" class="mini mini--danger" data-retirer title="Retirer"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
        </div>
    </div>
</template>

<script>
(() => {
    const conteneur = document.getElementById('lignes');
    const modele = document.getElementById('modele-ligne');
    let index = {{ count($lignes) }};
    const euros = (n) => n.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' });

    const choix = document.getElementById('remise_choix');

    const total = () => {
        let t = 0;
        conteneur.querySelectorAll('.ligne-facture').forEach((l) => {
            t += (parseFloat(l.querySelector('[data-q]').value) || 0) * (parseFloat(l.querySelector('[data-p]').value) || 0);
        });
        document.getElementById('total').textContent = euros(t);

        // La réduction, calculée comme le fera le serveur.
        let type = '', valeur = 0, libelle = '';
        const opt = choix?.selectedOptions[0];
        if (opt && opt.value) {
            if (opt.value.startsWith('code:')) { type = opt.dataset.type; valeur = +opt.dataset.valeur; libelle = opt.textContent.trim(); }
            else { [type, valeur] = opt.value.split(':'); valeur = +valeur; libelle = opt.textContent.trim(); }
        }
        let remise = type === 'pourcentage' ? Math.round(t * valeur) / 100 : (type === 'montant' ? valeur : 0);
        remise = Math.min(remise, t);
        document.getElementById('t-sous-total').textContent = euros(t);
        document.getElementById('ligne-remise').hidden = !remise;
        document.getElementById('t-remise-libelle').textContent = libelle;
        document.getElementById('t-remise').textContent = '− ' + euros(remise);
        document.getElementById('t-total').textContent = euros(t - remise);
    };
    choix?.addEventListener('change', total);

    // Choisir un mariage remplit le client, l'objet, et la formule s'il n'y a encore rien.
    document.getElementById('event_id')?.addEventListener('change', (e) => {
        const d = JSON.parse(e.target.selectedOptions[0]?.dataset.remplir || 'null');
        if (!d) return;
        for (const champ of ['client_nom', 'client_email', 'objet']) {
            const el = document.getElementById(champ);
            if (el && d[champ]) el.value = d[champ];
        }
        const vides = [...conteneur.querySelectorAll('.ligne-facture')].every((l) => !l.querySelector('[name$="[designation]"]').value);
        if (vides && d.formule) {
            conteneur.querySelectorAll('.ligne-facture').forEach((l) => l.remove());
            ajouter(d.formule);
        }
    });

    const ajouter = (valeurs = {}) => {
        const html = modele.innerHTML.replaceAll('__', index++);
        conteneur.insertAdjacentHTML('beforeend', html);
        const l = conteneur.lastElementChild;
        if (valeurs.designation) l.querySelector('[name$="[designation]"]').value = valeurs.designation;
        if (valeurs.detail) l.querySelector('[name$="[detail]"]').value = valeurs.detail;
        if (valeurs.prix !== undefined) l.querySelector('[data-p]').value = valeurs.prix;
        total();
    };

    document.getElementById('ajouter-ligne').addEventListener('click', () => ajouter());
    document.querySelectorAll('[data-catalogue]').forEach((b) => b.addEventListener('click', () => {
        // Une première ligne vide est remplacée plutôt que laissée en trop.
        const vide = [...conteneur.querySelectorAll('.ligne-facture')].find((l) => !l.querySelector('[name$="[designation]"]').value);
        vide?.remove();
        ajouter(JSON.parse(b.dataset.catalogue));
    }));
    conteneur.addEventListener('click', (e) => {
        if (e.target.closest('[data-retirer]') && conteneur.children.length > 1) { e.target.closest('.ligne-facture').remove(); total(); }
    });
    conteneur.addEventListener('input', total);
    total();
})();
</script>

@endsection
