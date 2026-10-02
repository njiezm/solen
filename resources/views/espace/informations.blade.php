@extends('espace.layout')

@section('titre', 'Informations')
@section('chapeau', 'Les bases de votre mariage : les prénoms, la date, le lieu et l’allure du site.')

@section('contenu')

<form method="POST" action="{{ route('espace.informations.enregistrer') }}">
    @csrf

    <div class="carte">
        <h2>Vous deux</h2>
        <p class="carte-aide">Le nom du mariage apparaît sur la page d’entrée et dans les liens partagés.</p>

        <div class="grille-champs">
            <x-champ :champ="['cle' => 'nom', 'type' => 'texte', 'label' => 'Nom du mariage', 'requis' => true, 'placeholder' => 'Clara & Maël']"
                     :valeur="$event->nom" prefixe="" />

            <x-champ :champ="['cle' => 'partenaire_1', 'type' => 'texte', 'label' => 'Premier prénom']"
                     :valeur="$event->partenaire_1" prefixe="" />

            <x-champ :champ="['cle' => 'partenaire_2', 'type' => 'texte', 'label' => 'Second prénom']"
                     :valeur="$event->partenaire_2" prefixe="" />

            <x-champ :champ="['cle' => 'hashtag', 'type' => 'texte', 'label' => 'Mot-dièse', 'placeholder' => '#' . Str::studly(Str::ascii(collect([$event->partenaire_1, $event->partenaire_2])->filter()->implode(' et ') ?: 'NotreMariage')), 'aide' => 'Utilisé sur le mur photo et les supports imprimés.']"
                     :valeur="$event->hashtag" prefixe="" />
        </div>
    </div>

    <div class="carte">
        <h2>Quand et où</h2>
        <p class="carte-aide">Le fuseau horaire compte : c’est lui qui rend le compte à rebours juste pour tout le monde.</p>

        <div class="grille-champs">
            <x-champ :champ="['cle' => 'date_principale', 'type' => 'date', 'label' => 'Date du mariage']"
                     :valeur="$event->date_principale?->format('Y-m-d')" prefixe="" />

            <x-champ :champ="['cle' => 'timezone', 'type' => 'choix', 'label' => 'Fuseau horaire', 'requis' => true, 'options' => $fuseaux]"
                     :valeur="$event->timezone" prefixe="" />

            <x-champ :champ="['cle' => 'lieu_ville', 'type' => 'texte', 'label' => 'Ville', 'placeholder' => 'Le Lamentin']"
                     :valeur="$event->lieu_ville" prefixe="" />

            <x-champ :champ="['cle' => 'lieu_pays', 'type' => 'texte', 'label' => 'Pays ou territoire', 'placeholder' => 'Martinique']"
                     :valeur="$event->lieu_pays" prefixe="" />
        </div>
    </div>

    {{-- Les polices des thèmes, pour que les aperçus soient fidèles. --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@400;600&family=Montserrat:wght@400;600&family=Fraunces:opsz,wght@9..144,400;9..144,600&family=Inter:wght@400;600&family=Cormorant+Garamond:wght@400;600&family=Lora:wght@400;600&display=swap">

    @php
        $prenoms = collect([$event->partenaire_1, $event->partenaire_2])->filter()->implode(' & ') ?: $event->nom;
        $perso   = (array) ($event->couleurs ?? []);
        $actuel  = $event->theme ?? $themes->first();
    @endphp

    <div class="carte" id="apparence">
        <h2>Votre thème</h2>
        <p class="carte-aide">Le thème s’applique partout : le site, le livret, les QR codes, les supports imprimés et le livre souvenir. Chaque aperçu est le rendu réel.</p>

        <div class="themes-filtres" role="group" aria-label="Filtrer les thèmes">
            <button type="button" class="actif" data-filtre="tous">Tous ({{ $themes->count() }})</button>
            <button type="button" data-filtre="clair">Clairs</button>
            <button type="button" data-filtre="sombre">Sombres</button>
            <button type="button" data-filtre="epure">Épurés</button>
            <button type="button" data-filtre="classique">Classiques</button>
            <button type="button" data-filtre="festif">Festifs</button>
        </div>

        <div class="themes-choix">
            @foreach ($themes as $theme)
                <label class="theme-option" data-tags="{{ $theme->estSombre() ? 'sombre' : 'clair' }} {{ $theme->caractere }}">
                    <input type="radio" name="theme_id" value="{{ $theme->id }}" @checked(($event->theme_id ?? $themes->first()?->id) === $theme->id)>
                    @include('partials.apercu-theme', ['theme' => $theme, 'titre' => $prenoms])
                    <span class="legende">{{ $theme->nom }} <small>{{ \App\Models\Theme::CARACTERES[$theme->caractere]['nom'] ?? '' }}</small></span>
                </label>
            @endforeach
        </div>
        @error('theme_id') <p class="champ-erreur">{{ $message }}</p> @enderror
    </div>

    <div class="carte" id="retouches">
        <h2>Retouches</h2>
        <p class="carte-aide">
            Ajustez le thème choisi à vos couleurs. Les textes restent toujours lisibles : leur couleur
            s’adapte d’elle-même à l’accent que vous choisissez. Laissez vide pour garder le thème tel quel.
        </p>

        <div class="retouches">
            <div class="grille-champs" style="grid-template-columns:1fr">
                <div class="champ">
                    <label for="perso-accent">Couleur d’accent</label>
                    <div style="display:flex; gap:.5rem; align-items:center">
                        <input type="color" id="perso-accent" name="perso[accent]" value="{{ $perso['accent'] ?? $actuel?->accent ?? '#C99B63' }}" style="flex:1">
                        <label class="champ-bascule" style="white-space:nowrap">
                            <input type="checkbox" name="perso[accent_actif]" value="1" @checked(! empty($perso['accent']))> Utiliser
                        </label>
                    </div>
                    <p class="champ-aide">Celle de vos faire-part, de vos fleurs ou de vos tenues.</p>
                </div>
                <div class="champ">
                    <label for="perso-police">Police des titres</label>
                    <select id="perso-police" name="perso[police]">
                        <option value="">Celle du thème</option>
                        @foreach (\App\Models\Theme::POLICES_TITRE as $cle => $police)
                            <option value="{{ $cle }}" @selected(($perso['police'] ?? '') === $cle) style="font-family: {{ $police['pile'] }}">{{ $police['nom'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="champ">
                    <label for="perso-forme">Forme des angles</label>
                    <select id="perso-forme" name="perso[forme]">
                        <option value="">Celle du thème</option>
                        @foreach (\App\Models\Theme::FORMES as $cle => $forme)
                            <option value="{{ $cle }}" @selected(($perso['forme'] ?? '') === $cle)>{{ $forme['nom'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <p class="carte-aide" style="margin:0 0 .5rem">Votre thème aujourd’hui, retouches comprises :</p>
                @if ($actuel)
                    @include('partials.apercu-theme', ['theme' => $actuel, 'titre' => $prenoms, 'surcharges' => $event->surchargesTheme()])
                @endif
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-filtre]').forEach((b) => b.addEventListener('click', () => {
            document.querySelectorAll('[data-filtre]').forEach((x) => x.classList.toggle('actif', x === b));
            document.querySelectorAll('.theme-option').forEach((o) => {
                o.hidden = b.dataset.filtre !== 'tous' && !o.dataset.tags.split(' ').includes(b.dataset.filtre);
            });
        }));
        // Toucher le sélecteur de couleur vaut intention de l'utiliser.
        document.getElementById('perso-accent')?.addEventListener('input', () => {
            document.querySelector('[name="perso[accent_actif]"]').checked = true;
        });
    </script>

    <div class="carte">
        <h2>Visibilité</h2>
        <p class="carte-aide">Tant que le site est en brouillon, vos invités ne peuvent pas y accéder.</p>

        <x-champ :champ="[
                    'cle'     => 'statut',
                    'type'    => 'choix',
                    'label'   => 'État du site',
                    'requis'  => true,
                    'options' => [
                        'brouillon' => 'Brouillon — visible de vous seuls',
                        'publie'    => 'Publié — accessible à vos invités',
                        'archive'   => 'Archivé — le site est fermé',
                    ],
                 ]"
                 :valeur="$event->statut" prefixe="" />
    </div>

    <div class="actions">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="{{ route('landing') }}" target="_blank" rel="noopener" class="btn btn-ghost">Voir mon site</a>
    </div>
</form>

@endsection
