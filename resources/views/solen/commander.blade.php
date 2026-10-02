@extends('solen.layout')

@section('title', 'Commander — ' . $formule->nom . ' — Solen')

@section('content')

<style>
    .cmd { max-width: 720px; margin-inline: auto; }
    .cmd-formules { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .7rem; margin-bottom: 1.6rem; }
    .cmd-formule {
        display: block; cursor: pointer;
        background: var(--surface-card); border: 1px solid var(--line);
        border-radius: var(--r-md); padding: 1rem; text-align: center;
    }
    .cmd-formule:has(input:checked) { border-color: var(--ink); box-shadow: var(--shadow-sm); }
    .cmd-formule input { position: absolute; opacity: 0; }
    .cmd-formule .nom { font-weight: 600; color: var(--ink); font-size: .95rem; }
    .cmd-formule .prix { font-family: var(--display); font-size: 1.6rem; color: var(--ink); margin-top: .2rem; }
    .cmd fieldset { border: 0; padding: 0; margin: 0 0 1.6rem; }
    .cmd legend {
        font-family: var(--body); font-size: .74rem; font-weight: 600;
        letter-spacing: .14em; text-transform: uppercase; color: var(--accent-deep);
        padding: 0; margin-bottom: .9rem;
    }
    .cmd-grille { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
    .cmd label.champ-l { display: block; font-size: .82rem; font-weight: 600; color: var(--ink); margin-bottom: .3rem; }
    .cmd input[type=text], .cmd input[type=email], .cmd input[type=date], .cmd select {
        width: 100%; font: inherit; font-size: .93rem; padding: .6rem .8rem;
        border: 1px solid var(--line); border-radius: var(--r-sm); background: var(--surface-card); color: var(--ink);
    }
    .cmd .cases { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: .5rem; }
    .cmd .case { display: flex; gap: .5rem; align-items: center; font-size: .9rem; cursor: pointer; }
    .cmd .err { font-size: .78rem; color: #B3261E; margin: .3rem 0 0; }
    .cmd-promo { margin: 0 0 1.2rem; }
    .cmd-promo label { display: block; font-size: .85rem; font-weight: 600; margin-bottom: .35rem; }
    .cmd-promo input { min-height: 44px; padding: .5rem .8rem; border: 1px solid var(--line); border-radius: 10px; font: inherit; }
    .cmd-promo .ok { color: #2F7D5D; font-weight: 600; }
    .cmd-total {
        display: flex; justify-content: space-between; align-items: baseline;
        background: var(--surface-warm); border-radius: var(--r-md);
        padding: 1.1rem 1.3rem; margin-bottom: 1.2rem;
    }
    .cmd-total .montant { font-family: var(--display); font-size: 2rem; color: var(--ink); }
</style>

<section style="padding-block: clamp(2.5rem, 6vw, 4.5rem)">
    <div class="wrap cmd">

        <p class="eyebrow">Commande</p>
        <h1 style="font-size: clamp(2rem, 4.5vw, 2.8rem)">Créons votre mariage.</h1>
        <p class="lead" style="margin-bottom: 2.2rem">
            Cinq champs, un paiement, et votre site est en ligne. Vous pourrez
            tout modifier ensuite depuis votre espace.
        </p>

        @if (session('erreur'))
            <p class="alerte alerte--erreur" style="background:#FBEAE8; color:#B3261E; padding:.8rem 1rem; border-radius:10px">
                {{ session('erreur') }}
            </p>
        @endif

        <form method="POST" action="{{ route('commande.payer') }}" id="cmd">
            @csrf

            <fieldset>
                <legend>Votre formule</legend>
                <div class="cmd-formules">
                    @foreach ($formules as $f)
                        <label class="cmd-formule">
                            <input type="radio" name="plan" value="{{ $f->cle }}"
                                   data-prix="{{ $f->prix }}"
                                   @checked(old('plan', $formule->cle) === $f->cle)>
                            <span class="nom">{{ $f->nom }}</span>
                            <span class="prix">{{ $f->prix }} €</span>
                        </label>
                    @endforeach
                </div>
                @error('plan') <p class="err">{{ $message }}</p> @enderror
            </fieldset>

            <fieldset>
                <legend>Vous deux</legend>
                <div class="cmd-grille">
                    <div>
                        <label class="champ-l" for="partenaire_1">Premier prénom</label>
                        <input id="partenaire_1" name="partenaire_1" type="text" value="{{ old('partenaire_1') }}" placeholder="Clara">
                    </div>
                    <div>
                        <label class="champ-l" for="partenaire_2">Second prénom</label>
                        <input id="partenaire_2" name="partenaire_2" type="text" value="{{ old('partenaire_2') }}" placeholder="Maël">
                    </div>
                    <div style="grid-column: 1 / -1">
                        <label class="champ-l" for="nom">Nom de votre mariage *</label>
                        <input id="nom" name="nom" type="text" value="{{ old('nom') }}" placeholder="Clara &amp; Maël" required>
                        @error('nom') <p class="err">{{ $message }}</p> @enderror
                    </div>
                    <div style="grid-column: 1 / -1">
                        <label class="champ-l" for="email">Votre adresse e-mail *</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                        <p class="champ-aide" style="font-size:.78rem; color:var(--ink-mute); margin:.3rem 0 0">
                            C’est avec elle que vous vous connecterez à votre espace.
                        </p>
                        @error('email') <p class="err">{{ $message }}</p> @enderror
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Quand et où</legend>
                <div class="cmd-grille">
                    <div>
                        <label class="champ-l" for="date_principale">Date du mariage</label>
                        <input id="date_principale" name="date_principale" type="date" value="{{ old('date_principale') }}">
                        @error('date_principale') <p class="err">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="champ-l" for="timezone">Fuseau horaire *</label>
                        <select id="timezone" name="timezone" required>
                            @foreach ($fuseaux as $cle => $libelle)
                                <option value="{{ $cle }}" @selected(old('timezone', 'Europe/Paris') === $cle)>{{ $libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="grid-column: 1 / -1">
                        <label class="champ-l" for="lieu_ville">Ville</label>
                        <input id="lieu_ville" name="lieu_ville" type="text" value="{{ old('lieu_ville') }}" placeholder="Arcachon">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Votre journée</legend>

                <label class="champ-l">Les moments prévus *</label>
                <div class="cases" style="margin-bottom:1.2rem">
                    @foreach ($parties as $cle => $partie)
                        <label class="case">
                            <input type="checkbox" name="parties[]" value="{{ $cle }}"
                                   @checked(in_array($cle, old('parties', ['ceremonie','vin-honneur','diner']), true))>
                            <span><i class="fa-solid {{ $partie['icone'] }}" aria-hidden="true"></i> {{ $partie['nom'] }}</span>
                        </label>
                    @endforeach
                </div>
                @error('parties') <p class="err">{{ $message }}</p> @enderror

                <label class="champ-l" for="type_ceremonie">Type de cérémonie *</label>
                <select id="type_ceremonie" name="type_ceremonie" required>
                    @foreach ($cultes as $cle => $libelle)
                        <option value="{{ $cle }}" @selected(old('type_ceremonie', 'civil') === $cle)>{{ $libelle }}</option>
                    @endforeach
                </select>
                <p class="champ-aide" style="font-size:.78rem; color:var(--ink-mute); margin:.3rem 0 0">
                    Nous pré-remplirons le déroulé correspondant. Vous pourrez tout changer.
                </p>
            </fieldset>

            <fieldset>
                <legend>Votre univers</legend>
                <p style="font-size:.86rem; color:var(--ink-mute); margin:-.4rem 0 1rem">
                    Le thème colore votre site, votre livret et les cadres du photobooth.
                    Vous pourrez en changer à tout moment.
                </p>

                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:.7rem">
                    @foreach ($themes as $theme)
                        <label class="cmd-formule" style="padding:0; overflow:hidden; text-align:left">
                            <input type="radio" name="theme_id" value="{{ $theme->id }}"
                                   @checked((int) old('theme_id', $themes->first()->id) === $theme->id)>

                            <span style="display:block; height:66px; position:relative; background: {{ $theme->surface }}">
                                <span style="position:absolute; inset:0; display:grid; place-items:center">
                                    <span style="width:30px; height:30px; border-radius:50%; border:2px solid {{ $theme->ink }}"></span>
                                </span>
                                <span style="position:absolute; bottom:0; left:0; right:0; height:5px; background: {{ $theme->accent }}"></span>
                            </span>

                            <span class="nom" style="display:block; padding:.6rem .7rem; font-size:.85rem">
                                {{ $theme->nom }}
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('theme_id') <p class="err">{{ $message }}</p> @enderror
            </fieldset>

            {{-- Récapitulatif : suit la formule cochée, sans recharger. --}}
            <div class="carte" style="background:var(--surface-card); border:1px solid var(--line); border-radius:var(--r-md); padding:1.3rem; margin-bottom:1.2rem">
                <h3 style="font-family:var(--body); font-size:.95rem; font-weight:600; margin-bottom:.3rem">
                    Formule <span id="recap-nom">{{ $formule->nom }}</span>
                </h3>
                <p style="font-size:.86rem; color:var(--ink-mute); margin-bottom:1rem" id="recap-desc">
                    {{ $formule->description }}
                </p>
                <ul id="recap-features" style="list-style:none; padding:0; margin:0; display:grid; gap:.5rem; font-size:.88rem"></ul>
            </div>

            {{-- Code promo : vérifié à la volée pour l'aperçu, revérifié au paiement. --}}
            <div class="cmd-promo">
                <label for="code_promo">Code promo</label>
                <div style="display:flex; gap:.5rem">
                    <input id="code_promo" name="code_promo" value="{{ old('code_promo') }}" autocomplete="off" style="text-transform:uppercase; flex:1">
                    <button type="button" class="btn btn-ghost" id="appliquer-code">Appliquer</button>
                </div>
                <p id="code-message" class="{{ $errors->has('code_promo') ? 'err' : '' }}" style="margin:.4rem 0 0; font-size:.85rem">{{ $errors->first('code_promo') }}</p>
            </div>

            <div class="cmd-total">
                <span>Total à régler</span>
                <span class="montant" id="total">{{ $formule->prix }} €</span>
            </div>

            <label class="case" style="margin-bottom:1.4rem">
                <input type="checkbox" name="cgv" value="1" @checked(old('cgv'))>
                <span>
                    J’accepte les <a href="{{ route('legal.cgv') }}" target="_blank" rel="noopener">conditions générales de vente</a>
                    et demande la mise en service immédiate de mon site. Je reconnais qu’une fois le service pleinement
                    exécuté, je ne pourrai plus exercer mon droit de rétractation.
                </span>
            </label>
            @error('cgv') <p class="err" style="margin-top:-1rem; margin-bottom:1rem">{{ $message }}</p> @enderror

            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center" id="envoyer">
                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                <span id="libelle-bouton">Payer et créer mon mariage</span>
            </button>

            <p style="text-align:center; font-size:.82rem; color:var(--ink-mute); margin-top:1rem">
                Paiement sécurisé par Stripe. Nous ne voyons jamais vos coordonnées bancaires.
            </p>
        </form>

        <p style="text-align:center; margin-top:2rem">
            <a href="{{ route('solen.landing') }}">← Revoir les formules</a>
        </p>
    </div>
</section>

<script>
(() => {
    // Changer de formule met à jour le récapitulatif, le total, le libellé
    // du bouton, le titre de l'onglet — et l'adresse, pour que la page reste
    // partageable et que le retour arrière du navigateur ait du sens.
    const details = @json($details);

    const total    = document.getElementById('total');
    const libelle  = document.getElementById('libelle-bouton');
    const recapNom = document.getElementById('recap-nom');
    const recapDesc = document.getElementById('recap-desc');
    const recapList = document.getElementById('recap-features');

    let planCourant = @json($formule->cle);
    const champCode = document.getElementById('code_promo');
    const messageCode = document.getElementById('code-message');

    async function verifierCode() {
        const code = champCode.value.trim();
        if (!code) { messageCode.textContent = ''; total.textContent = `${details[planCourant].prix} €`; return; }
        const r = await fetch(`{{ route('commande.code') }}?plan=${encodeURIComponent(planCourant)}&code=${encodeURIComponent(code)}`, { headers: { Accept: 'application/json' } });
        const d = await r.json();
        const eur = (n) => n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        messageCode.className = d.valide ? 'ok' : 'err';
        messageCode.textContent = d.valide ? `${d.message} · vous économisez ${eur(d.remise)} €` : d.message;
        total.innerHTML = d.valide
            ? `<s style="opacity:.5; font-size:.6em; margin-right:.4rem">${details[planCourant].prix} €</s>${eur(d.total)} €`
            : `${details[planCourant].prix} €`;
    }
    document.getElementById('appliquer-code').addEventListener('click', verifierCode);
    champCode.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); verifierCode(); } });

    function appliquer(cle, poussserHistorique) {
        const d = details[cle];
        if (!d) return;

        total.textContent    = `${d.prix} €`;
        planCourant = cle;
        if (document.getElementById('code_promo').value) verifierCode();
        libelle.textContent  = 'Payer et créer mon mariage';
        recapNom.textContent = d.nom;
        recapDesc.textContent = d.desc || '';

        recapList.innerHTML = '';
        (d.features || []).forEach((f) => {
            const li = document.createElement('li');
            li.style.display = 'flex';
            li.style.gap = '.55rem';
            li.innerHTML = '<i class="fa-solid fa-check" style="color:var(--accent-deep);font-size:.72rem;margin-top:.32rem"></i>';
            li.appendChild(document.createTextNode(f));
            recapList.appendChild(li);
        });

        document.title = `Commander — ${d.nom} — Solen`;

        if (poussserHistorique) {
            history.pushState({ plan: cle }, '', d.url);
        }
    }

    document.querySelectorAll('input[name="plan"]').forEach((radio) => {
        radio.addEventListener('change', () => appliquer(radio.value, true));
    });

    // Bouton « précédent » du navigateur : on recoche la bonne formule.
    addEventListener('popstate', (e) => {
        const cle = e.state?.plan
            || location.pathname.split('/').filter(Boolean).pop();
        const radio = document.querySelector(`input[name="plan"][value="${cle}"]`);
        if (radio) { radio.checked = true; appliquer(cle, false); }
    });

    appliquer(document.querySelector('input[name="plan"]:checked').value, false);
})();
</script>

@endsection
