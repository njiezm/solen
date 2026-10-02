@extends('console.layout')

@section('titre', $theme->exists ? $theme->nom : 'Nouveau thème')
@section('chapeau', 'Un thème, ce n’est pas qu’une palette : les formes, le caractère et la respiration changent aussi.')
@section('retour', route('console.themes'))
@section('retour-libelle', 'Tous les thèmes')

@section('contenu')

<style>
    .editeur { display: grid; grid-template-columns: 1fr 330px; gap: 1.2rem; align-items: start; }
    @media (max-width: 980px) { .editeur { grid-template-columns: 1fr; } }

    /* Aperçu : les jetons du thème y sont appliqués en direct. */
    #apercu {
        border: 1px solid var(--line);
        overflow: hidden;
        transition: border-radius .2s ease;
    }
    #ap-bandeau { padding: 1.7rem 1.2rem; text-align: center; transition: padding .2s ease; }
    #ap-titre   { font-size: 1.7rem; line-height: 1.15; }
    #ap-date    { font-size: .7rem; opacity: .65; margin-top: .45rem; }
    #ap-filet   { width: 44px; height: 2px; margin: .7rem auto 0; border-radius: 999px; }
    #ap-corps   { padding: 1.1rem; }
    #ap-carte   { padding: .95rem; text-align: center; font-size: .84rem; transition: border-radius .2s ease; }
    #ap-pastille{ width: 30px; height: 30px; border-radius: 50%; margin: 0 auto .5rem; }
    #ap-bouton  {
        margin-top: .9rem; text-align: center; padding: .65rem;
        font-size: .82rem; transition: border-radius .2s ease;
    }
    #ap-photo {
        height: 96px;
        background: linear-gradient(120deg, #E9743F 0%, #C9A227 35%, #4E8C7A 70%, #3B5C8C 100%);
        transition: filter .25s ease;
    }
</style>

<form method="POST"
      action="{{ $theme->exists ? route('console.theme.maj', $theme) : route('console.theme.stocker') }}">
    @csrf

    <div class="editeur">
        <div>
            <div class="carte">
                <h2>Identité</h2>
                <div class="grille-champs">
                    <x-champ :champ="['cle'=>'nom','type'=>'texte','label'=>'Nom du thème','requis'=>true,'placeholder'=>'Bleu Nuit & Or']"
                             :valeur="$theme->nom" prefixe="" />
                </div>
            </div>

            <div class="carte">
                <h2>Couleurs</h2>
                <p class="carte-aide">
                    L’encre porte les textes et les fonds sombres, l’accent souligne,
                    la secondaire nuance. Prévoyez un contraste net entre encre et fond.
                </p>

                <div class="grille-champs">
                    <x-champ :champ="['cle'=>'ink','type'=>'couleur','label'=>'Encre','requis'=>true,'aide'=>'Les textes et les aplats sombres.']"
                             :valeur="$theme->ink" prefixe="" />
                    <x-champ :champ="['cle'=>'surface','type'=>'couleur','label'=>'Cartes','requis'=>true,'aide'=>'Le fond des blocs de contenu.']"
                             :valeur="$theme->surface" prefixe="" />
                    <x-champ :champ="['cle'=>'accent','type'=>'couleur','label'=>'Accent','requis'=>true]"
                             :valeur="$theme->accent" prefixe="" />
                    <x-champ :champ="['cle'=>'secondaire','type'=>'couleur','label'=>'Secondaire','aide'=>'Nuances et fonds de section.']"
                             :valeur="$theme->secondaire ?: $theme->accent" prefixe="" />
                    <x-champ :champ="['cle'=>'fond','type'=>'couleur','label'=>'Fond de page','aide'=>'Laissez tel quel pour le déduire de l’encre. À définir pour un thème sombre.']"
                             :valeur="$theme->fond" prefixe="" />
                </div>
            </div>

            <div class="carte">
                <h2>Photographie</h2>
                <p class="carte-aide">
                    Le levier le plus fort : les mêmes clichés en argentique ou
                    en noir et blanc racontent deux mariages différents.
                </p>

                <div class="grille-champs">
                    <x-champ :champ="['cle'=>'traitement','type'=>'choix','label'=>'Traitement des photos','requis'=>true,'options'=>$traitements]"
                             :valeur="$theme->traitement" prefixe="" />
                </div>
            </div>

            <div class="carte">
                <h2>Formes et caractère</h2>
                <p class="carte-aide">
                    C’est ce qui distingue vraiment deux thèmes. Un mariage anguleux
                    et sobre ne ressemble pas à un mariage arrondi et festif, même
                    avec les mêmes couleurs.
                </p>

                <div class="grille-champs">
                    <x-champ :champ="['cle'=>'forme','type'=>'choix','label'=>'Forme des éléments','requis'=>true,'options'=>$formes,'aide'=>'Arrondi des cartes, des boutons et des photos.']"
                             :valeur="$theme->forme" prefixe="" />

                    <x-champ :champ="['cle'=>'caractere','type'=>'choix','label'=>'Caractère','requis'=>true,'options'=>$caracteres,'aide'=>'Épuré : rien. Classique : un filet. Festif : capitales et fleuron.']"
                             :valeur="$theme->caractere" prefixe="" />

                    <x-champ :champ="['cle'=>'densite','type'=>'choix','label'=>'Respiration','requis'=>true,'options'=>$densites,'aide'=>'L’air entre les blocs.']"
                             :valeur="$theme->densite" prefixe="" />
                </div>
            </div>

            <div class="carte">
                <h2>Typographie</h2>
                <div class="grille-champs">
                    <x-champ :champ="['cle'=>'font_display','type'=>'choix','label'=>'Police des titres','requis'=>true,'options'=>$polices]"
                             :valeur="$theme->font_display" prefixe="" />
                    <x-champ :champ="['cle'=>'font_body','type'=>'choix','label'=>'Police du texte','requis'=>true,'options'=>$polices]"
                             :valeur="$theme->font_body" prefixe="" />
                </div>
            </div>

            <div class="carte">
                <div class="champ" style="margin-bottom:1rem">
                    <label for="event_id">Réservé à un mariage</label>
                    <select id="event_id" name="event_id">
                        <option value="">Non : thème du catalogue, proposé à tous</option>
                        @foreach (\App\Models\Event::orderBy('nom')->get(['id', 'nom', 'slug']) as $m)
                            <option value="{{ $m->id }}" @selected(old('event_id', $theme->event_id ?? request('mariage_id')) == $m->id)>Sur mesure pour {{ $m->nom }} ({{ $m->slug }})</option>
                        @endforeach
                    </select>
                    <p class="champ-aide">Un thème sur mesure n’apparaît que chez ce couple : ni dans le choix des autres, ni sur la vitrine.</p>
                </div>

                <x-champ :champ="['cle'=>'actif','type'=>'booleen','label'=>'Proposer ce thème aux mariés']"
                         :valeur="$theme->exists ? $theme->actif : true" prefixe="" />
            </div>
        </div>

        {{-- Aperçu collant : il suit la lecture pendant les réglages. --}}
        <div class="carte" style="position:sticky; top:1.5rem">
            <h2>Aperçu</h2>
            <div id="apercu">
                {{-- Un dégradé coloré tient lieu de photo : il montre le
                     traitement sans emprunter le cliché de personne. --}}
                <div id="ap-photo" aria-label="Aperçu du traitement photographique"></div>

                <div id="ap-bandeau">
                    <div id="ap-titre">Clara &amp; Maël</div>
                    <div id="ap-filet"></div>
                    <div id="ap-date">12 septembre 2026</div>
                </div>
                <div id="ap-corps">
                    <div id="ap-carte">
                        <div id="ap-pastille"></div>
                        Livre d’or
                    </div>
                    <div id="ap-bouton">Entrer sur l’application</div>
                </div>
            </div>
            <p class="carte-aide" style="margin:.9rem 0 0">
                Le contraste doit rester confortable en plein soleil comme dans
                une salle sombre.
            </p>
        </div>
    </div>

    <div class="actions">
        <button type="submit" class="btn btn-primary">{{ $theme->exists ? 'Enregistrer' : 'Créer le thème' }}</button>
        <a href="{{ route('console.themes') }}" class="btn btn-ghost">Annuler</a>
    </div>
</form>

<script>
(() => {
    // Les mêmes tables que côté PHP : l'aperçu doit dire la vérité.
    const FORMES = @json(App\Models\Theme::FORMES);
    const CARACS = @json(App\Models\Theme::CARACTERES);
    const DENSIT = @json(App\Models\Theme::DENSITES);
    const TRAIT  = @json(App\Models\Theme::TRAITEMENTS);

    const champs = ['ink', 'surface', 'accent', 'secondaire', 'fond', 'forme', 'caractere',
                    'densite', 'traitement', 'font_display', 'font_body']
        .map((c) => document.getElementById(c))
        .filter(Boolean);

    const el = (id) => document.getElementById(id);

    function peindre() {
        const ink  = el('ink').value;
        const surf = el('surface').value;
        const acc  = el('accent').value;
        const sec  = el('secondaire')?.value || acc;

        const f = FORMES[el('forme').value]      ?? FORMES.doux;
        const c = CARACS[el('caractere').value]  ?? CARACS.classique;
        const d = DENSIT[el('densite').value]    ?? DENSIT.confortable;

        el('apercu').style.borderRadius = f.carte;

        // Le traitement photographique, appliqué au dégradé témoin.
        const t = TRAIT[el('traitement')?.value] ?? TRAIT.naturel;
        el('ap-photo').style.filter = t.filtre;

        const bandeau = el('ap-bandeau');
        bandeau.style.background = ink;
        bandeau.style.padding    = `${1.7 * parseFloat(d.echelle)}rem 1.2rem`;

        const titre = el('ap-titre');
        titre.style.color          = surf;
        titre.style.fontFamily     = el('font_display').value;
        titre.style.letterSpacing  = c.interlettrage;
        titre.style.textTransform  = c.capitales;
        titre.style.fontWeight     = c.graisse;

        // L'ornement suit le caractère choisi.
        const filet = el('ap-filet');
        filet.textContent = '';
        if (c.ornement === 'filet') {
            filet.style.cssText = `width:44px;height:2px;margin:.7rem auto 0;border-radius:999px;background:${acc}`;
        } else if (c.ornement === 'double') {
            filet.style.cssText = `margin:.5rem auto 0;color:${acc};font-size:1.1rem;line-height:1`;
            filet.textContent = '❦';
        } else {
            filet.style.cssText = 'display:none';
        }

        el('ap-date').style.color = surf;

        const corps = el('ap-corps');
        corps.style.background = surf;
        corps.style.fontFamily = el('font_body').value;
        corps.style.padding    = `${1.1 * parseFloat(d.echelle)}rem`;

        const carte = el('ap-carte');
        carte.style.background   = '#fff';
        carte.style.color        = ink;
        carte.style.border       = `1px solid ${acc}44`;
        carte.style.borderRadius = f.image;

        el('ap-pastille').style.background = sec;

        const bouton = el('ap-bouton');
        bouton.style.background    = ink;
        bouton.style.color         = surf;
        bouton.style.borderRadius  = f.bouton;
        bouton.style.letterSpacing = c.interlettrage;
        bouton.style.textTransform = c.capitales;
        bouton.style.fontWeight    = c.graisse;
    }

    champs.forEach((c) => c.addEventListener('input', peindre));
    peindre();
})();
</script>

@endsection
