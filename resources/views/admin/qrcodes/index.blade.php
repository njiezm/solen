@extends('espace.layout')

@section('titre', 'QR codes et impressions')
@section('chapeau', 'Un QR par usage et par table, prêts à imprimer : affiches, cartes de table, chevalets, stickers. Vous saurez lesquels ont été scannés.')

@section('contenu')

@php
    $modeles = \App\Http\Controllers\QrCodeController::MODELES;
@endphp

{{-- ─────────────────────────────────────────────────────── Le kit ── --}}
<div class="carte">
    <h2>Créer le kit en un clic</h2>
    <p class="carte-aide">
        Un QR pour le faire-part, l’accueil, le photobooth, la galerie, le livre d’or et le livret
        (selon vos modules), plus un par table. Ceux qui existent déjà ne sont pas recréés.
    </p>
    <form method="POST" action="{{ route('admin.qrcodes.kit') }}" class="ajout-ligne" style="margin-top:0">
        @csrf
        <label style="display:flex; align-items:center; gap:.5rem; flex:1 1 220px">
            Nombre de tables
            <input type="number" name="tables" min="0" max="80" value="{{ old('tables', 10) }}" style="width:90px; flex:none">
        </label>
        <button class="btn btn-primary"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Créer le kit</button>
    </form>
</div>

@if ($qrCodes->isEmpty())
    <div class="vide-illustre">
        <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
        <p>Aucun QR code pour l’instant. Le kit ci-dessus les crée tous d’un coup.</p>
    </div>
@else
    {{-- ───────────────────────────────────────── Sélection et impression ── --}}
    <form method="GET" action="{{ route('admin.qrcodes.impressions') }}" target="_blank" class="carte" id="impression">
        <h2>Imprimer</h2>
        <p class="carte-aide">Cochez les QR codes, choisissez le support : la planche s’ouvre prête à imprimer ou à enregistrer en PDF.</p>

        <div class="grille-champs">
            <div class="champ">
                <label for="modele">Support</label>
                <select id="modele" name="modele">
                    @foreach ($modeles as $cle => $m)
                        <option value="{{ $cle }}">{{ $m['nom'] }} — {{ $m['par_page'] }} par feuille</option>
                    @endforeach
                </select>
            </div>
            <div class="champ">
                <label for="couleur">Couleur</label>
                <select id="couleur" name="couleur">
                    <option value="theme">Aux couleurs du thème</option>
                    <option value="noir">Noir (impression économique)</option>
                </select>
            </div>
            <div class="champ">
                <label for="titre">Phrase <span class="champ-aide" style="display:inline">(facultatif)</span></label>
                <input type="text" id="titre" name="titre" maxlength="80" placeholder="Sinon, une phrase adaptée à chaque QR">
            </div>
            <div class="champ" style="align-self:end">
                <label class="champ-bascule">
                    <input type="checkbox" name="logo" value="1" @checked($event->logo) @disabled(! $event->logo)>
                    Avec le logo des mariés
                </label>
                @unless ($event->logo)
                    <p class="champ-aide">Aucun logo déposé pour l’instant.</p>
                @endunless
            </div>
        </div>

        <div class="actions" style="justify-content:space-between; flex-wrap:wrap">
            <span style="display:flex; gap:.4rem">
                <button type="button" class="mini" data-cocher="tout">Tout cocher</button>
                <button type="button" class="mini" data-cocher="tables">Les tables</button>
                <button type="button" class="mini" data-cocher="rien">Rien</button>
            </span>
            <button class="btn btn-primary"><i class="fa-solid fa-print" aria-hidden="true"></i> Ouvrir la planche</button>
        </div>

        <div class="liste" style="margin-top:1rem">
            @foreach ($qrCodes as $qr)
                <div class="ligne @unless($qr->is_active) inactive @endunless">
                    <input type="checkbox" name="qr[]" value="{{ $qr->id }}" aria-label="Imprimer {{ $qr->name }}"
                           data-table="{{ $qr->source === 'table' ? 1 : 0 }}" style="width:18px; height:18px; accent-color:var(--accent-deep)">
                    <div class="corps">
                        <strong>{{ $qr->name }}</strong>
                        <span>{{ $qr->scans_count }} scan{{ $qr->scans_count > 1 ? 's' : '' }} · {{ $qr->source }}</span>
                    </div>
                    <div class="outils">
                        <a href="{{ route('admin.qrcodes.stats', $qr->id) }}" class="mini">Statistiques</a>
                        <a href="{{ route('admin.qrcodes.download', ['qrCode' => $qr->id, 'logo' => 1]) }}" class="mini">SVG avec logo</a>
                        <a href="{{ route('admin.qrcodes.download', ['qrCode' => $qr->id, 'logo' => 0]) }}" class="mini">sans logo</a>
                    </div>
                </div>
            @endforeach
        </div>
    </form>
@endif

{{-- ──────────────────────────────────────────────────── Sur mesure ── --}}
<details class="ajout" @if($errors->any()) open @endif>
    <summary><i class="fa-solid fa-plus" aria-hidden="true"></i> Créer un QR code sur mesure</summary>

    <div class="ajout-corps">
        <form action="{{ route('admin.qrcodes.store') }}" method="POST">
            @csrf
            <div class="grille-champs">
                <x-champ :champ="['cle'=>'name','type'=>'texte','label'=>'Nom','requis'=>true,'placeholder'=>'Panneau du parking']" prefixe="" />
                <x-champ :champ="[
                            'cle'=>'source','type'=>'choix','label'=>'Emplacement','requis'=>true,
                            'options'=>['tableau'=>'Panneau','entree'=>'Entrée','eglise'=>'Cérémonie','table'=>'Table','whatsapp'=>'Message partagé','autre'=>'Autre'],
                         ]" prefixe="" />
                <x-champ :champ="['cle'=>'destination_url','type'=>'lien','label'=>'Où il mène','requis'=>true,'placeholder'=>route('home')]" prefixe="" />
            </div>
            <div class="actions"><button type="submit" class="btn btn-primary">Créer</button></div>
        </form>
    </div>
</details>

<script>
    document.querySelectorAll('[data-cocher]').forEach((b) => b.addEventListener('click', () => {
        document.querySelectorAll('#impression input[name="qr[]"]').forEach((c) => {
            c.checked = b.dataset.cocher === 'tout' || (b.dataset.cocher === 'tables' && c.dataset.table === '1');
        });
    }));
    document.getElementById('impression')?.addEventListener('submit', (e) => {
        if (!e.target.querySelector('input[name="qr[]"]:checked')) {
            e.preventDefault();
            alert('Cochez au moins un QR code à imprimer.');
        }
    });
</script>

@endsection
