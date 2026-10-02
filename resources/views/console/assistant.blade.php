@extends('console.layout')

@section('titre', 'Nouveau mariage')
@section('chapeau', 'Cinq étapes. Tout ce qui peut être déduit de vos réponses le sera : le déroulé, les modules, les horaires indicatifs.')

@section('contenu')

<style>
    /* Le parcours reste utilisable sans JavaScript : tous les panneaux
       s'affichent alors, et le formulaire s'envoie d'un seul bloc. */
    .etapes { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1.6rem }
    .etapes li {
        list-style:none; font-size:.78rem; font-weight:600;
        padding:.4rem .85rem; border-radius:var(--r-pill);
        background:var(--surface-card); border:1px solid var(--line); color:var(--ink-mute);
    }
    .etapes li.actif { background:var(--ink); border-color:var(--ink); color:var(--surface) }
    .etapes li.faite { background:var(--live-wash); border-color:transparent; color:var(--live) }
    .js .panneau { display:none }
    .js .panneau.actif { display:block }
    .choix-grille { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:.7rem }
    .choix {
        display:flex; gap:.7rem; align-items:flex-start; cursor:pointer;
        background:var(--surface-card); border:1px solid var(--line);
        border-radius:var(--r-md); padding:.9rem 1rem;
    }
    .choix:has(input:checked) { border-color:var(--ink); box-shadow:var(--shadow-sm) }
    .choix input { margin-top:.2rem; accent-color:var(--accent-deep) }
    .choix strong { display:block; font-size:.92rem; color:var(--ink) }
    .choix span { display:block; font-size:.8rem; color:var(--ink-mute) }
</style>

<ul class="etapes" id="fil">
    @foreach (['Les mariés','Quand et où','La journée','La formule','Apparence et accès'] as $i => $titre)
        <li data-etape="{{ $i }}" class="{{ $i === 0 ? 'actif' : '' }}">{{ $i + 1 }}. {{ $titre }}</li>
    @endforeach
</ul>

<form method="POST" action="{{ route('console.mariage.stocker') }}" id="assistant">
    @csrf

    {{-- 1 ─────────────────────────────────────────────────────────── --}}
    <div class="panneau actif" data-panneau="0">
        <div class="carte">
            <h2>Qui se marie ?</h2>
            <p class="carte-aide">Le nom du mariage apparaît sur la page d’entrée et dans les liens partagés.</p>

            <div class="grille-champs">
                <x-champ :champ="['cle'=>'partenaire_1','type'=>'texte','label'=>'Premier prénom','placeholder'=>'Clara']" prefixe="" />
                <x-champ :champ="['cle'=>'partenaire_2','type'=>'texte','label'=>'Second prénom','placeholder'=>'Maël']" prefixe="" />
                <x-champ :champ="['cle'=>'nom','type'=>'texte','label'=>'Nom du mariage','requis'=>true,'placeholder'=>'Clara & Maël']" prefixe="" />
                <x-champ :champ="['cle'=>'slug','type'=>'texte','label'=>'Identifiant dans l’adresse','placeholder'=>'clara-mael','aide'=>'Laissez vide pour le déduire du nom. Lettres minuscules et tirets.']" prefixe="" />
            </div>

            <p class="carte-aide" style="margin:1rem 0 0">
                Adresse du site : <code id="apercu-url">{{ url('/mariage') }}/…</code>
            </p>
        </div>
    </div>

    {{-- 2 ─────────────────────────────────────────────────────────── --}}
    <div class="panneau" data-panneau="1">
        <div class="carte">
            <h2>Quand et où</h2>
            <p class="carte-aide">Le fuseau horaire rend le compte à rebours juste pour les invités de métropole comme des Antilles.</p>

            <div class="grille-champs">
                <x-champ :champ="['cle'=>'date_principale','type'=>'date','label'=>'Date du mariage']" prefixe="" />
                <x-champ :champ="['cle'=>'timezone','type'=>'choix','label'=>'Fuseau horaire','requis'=>true,'options'=>$fuseaux,'defaut'=>'Europe/Paris']" prefixe="" />
                <x-champ :champ="['cle'=>'lieu_ville','type'=>'texte','label'=>'Ville','placeholder'=>'Le Lamentin']" prefixe="" />
                <x-champ :champ="['cle'=>'lieu_pays','type'=>'texte','label'=>'Pays ou territoire','defaut'=>'France']" prefixe="" />
            </div>
        </div>
    </div>

    {{-- 3 ─────────────────────────────────────────────────────────── --}}
    <div class="panneau" data-panneau="2">
        <div class="carte">
            <h2>Les moments de la journée</h2>
            <p class="carte-aide">Chaque moment aura son lieu, son horaire et son propre déroulé. Un horaire indicatif est posé, à ajuster ensuite.</p>

            <div class="choix-grille">
                @foreach ($parties as $cle => $partie)
                    <label class="choix">
                        <input type="checkbox" name="parties[]" value="{{ $cle }}"
                               @checked(in_array($cle, old('parties', ['ceremonie','vin-honneur','diner']), true))>
                        <span>
                            <strong><i class="fa-solid {{ $partie['icone'] }}" aria-hidden="true"></i> {{ $partie['nom'] }}</strong>
                            <span>{{ $partie['ceremonie'] ? 'Avec un déroulé détaillé' : 'Moment festif' }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('parties') <p class="champ-erreur">{{ $message }}</p> @enderror
        </div>

        <div class="carte">
            <h2>Type de cérémonie</h2>
            <p class="carte-aide">Détermine la trame de déroulé pré-remplie. Elle reste entièrement modifiable.</p>

            <div class="choix-grille">
                @foreach ($cultes as $cle => $libelle)
                    <label class="choix">
                        <input type="radio" name="type_ceremonie" value="{{ $cle }}"
                               @checked(old('type_ceremonie', 'civil') === $cle)>
                        <span>
                            <strong>{{ $libelle }}</strong>
                            <span>{{ $deroules[$cle] }} étapes pré-remplies</span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('type_ceremonie') <p class="champ-erreur">{{ $message }}</p> @enderror

            <p class="carte-aide" style="margin:1rem 0 0">
                La mairie reçoit toujours la trame civile, quel que soit ce choix.
            </p>
        </div>
    </div>

    {{-- 4 ─────────────────────────────────────────────────────────── --}}
    <div class="panneau" data-panneau="3">
        <div class="carte">
            <h2>La formule</h2>
            <p class="carte-aide">Elle détermine les modules débloqués. Elle se change à tout moment sans rien perdre.</p>

            <div class="choix-grille">
                @foreach ($formules as $formule)
                    <label class="choix">
                        <input type="radio" name="plan" value="{{ $formule->cle }}"
                               @checked(old('plan', 'celebration') === $formule->cle)>
                        <span>
                            <strong>{{ $formule->nom }} — {{ $formule->estGratuit() ? 'gratuit' : $formule->prix . ' €' }}</strong>
                            <span>{{ $formule->modules()->count() }} modules · {{ $formule->accroche }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('plan') <p class="champ-erreur">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- 5 ─────────────────────────────────────────────────────────── --}}
    <div class="panneau" data-panneau="4">
        <div class="carte">
            <h2>Apparence</h2>
            <p class="carte-aide">Le thème s’applique au site, au livret et aux cadres photo.</p>

            <div class="choix-grille">
                @foreach ($themes as $theme)
                    <label class="choix">
                        <input type="radio" name="theme_id" value="{{ $theme->id }}"
                               @checked((int) old('theme_id', $themes->first()->id) === $theme->id)>
                        <span>
                            <strong>{{ $theme->nom }}</strong>
                            <span style="display:flex; gap:.3rem; margin-top:.4rem">
                                @foreach ([$theme->ink, $theme->surface, $theme->accent] as $couleur)
                                    <i style="width:18px;height:18px;border-radius:50%;background:{{ $couleur }};border:1px solid rgba(0,0,0,.12)"></i>
                                @endforeach
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('theme_id') <p class="champ-erreur">{{ $message }}</p> @enderror
        </div>

        <div class="carte">
            <h2>Accès des mariés</h2>
            <p class="carte-aide">Un compte est créé et son mot de passe affiché une seule fois, juste après. Laissez vide pour créer le mariage sans compte.</p>

            <div class="grille-champs">
                <x-champ :champ="['cle'=>'email','type'=>'email','label'=>'Adresse e-mail des mariés','placeholder'=>'clara.et.mael@exemple.fr']" prefixe="" />
            </div>

            <div style="margin-top:1rem">
                <x-champ :champ="['cle'=>'est_demo','type'=>'booleen','label'=>'Ce mariage est une démonstration']" prefixe="" />
            </div>
        </div>
    </div>

    <div class="actions">
        <button type="button" class="btn btn-ghost" id="precedent" hidden>← Précédent</button>
        <button type="button" class="btn btn-primary" id="suivant">Suivant →</button>
        <button type="submit" class="btn btn-primary" id="creer">Créer le mariage</button>
        <a href="{{ route('console.index') }}" class="btn btn-ghost pousse">Annuler</a>
    </div>
</form>

<script>
document.documentElement.classList.add('js');

const panneaux  = [...document.querySelectorAll('.panneau')];
const etapes    = [...document.querySelectorAll('#fil li')];
const precedent = document.getElementById('precedent');
const suivant   = document.getElementById('suivant');
const creer     = document.getElementById('creer');
let position    = 0;

// Si la validation serveur a renvoyé des erreurs, on ouvre le premier
// panneau concerné plutôt que de laisser le client les chercher.
const enErreur = document.querySelector('.champ--erreur, .champ-erreur');
if (enErreur) {
    const panneau = enErreur.closest('.panneau');
    if (panneau) position = panneaux.indexOf(panneau);
}

function afficher() {
    panneaux.forEach((p, i) => p.classList.toggle('actif', i === position));
    etapes.forEach((e, i) => {
        e.classList.toggle('actif', i === position);
        e.classList.toggle('faite', i < position);
    });
    precedent.hidden = position === 0;
    suivant.hidden   = position === panneaux.length - 1;
    creer.hidden     = position !== panneaux.length - 1;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

suivant.addEventListener('click', () => {
    // Laisser le navigateur signaler les champs obligatoires du panneau courant.
    const manquant = panneaux[position].querySelector(':invalid');
    if (manquant) { manquant.reportValidity(); return; }
    position = Math.min(position + 1, panneaux.length - 1);
    afficher();
});

precedent.addEventListener('click', () => {
    position = Math.max(position - 1, 0);
    afficher();
});

etapes.forEach((e, i) => e.addEventListener('click', () => { position = i; afficher(); }));

// Proposition d'adresse en direct.
const champNom  = document.getElementById('nom');
const champSlug = document.getElementById('slug');
const apercu    = document.getElementById('apercu-url');
const base      = @json(url('/mariage'));

const slugifier = (v) => v.toLowerCase().normalize('NFD')
    .replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

function majApercu() {
    const slug = champSlug.value.trim() || slugifier(champNom.value.trim());
    apercu.textContent = `${base}/${slug || '…'}`;
}

champNom.addEventListener('input', majApercu);
champSlug.addEventListener('input', majApercu);
majApercu();
afficher();
</script>

@endsection
