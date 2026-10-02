@extends('espace.layout')

@section('titre', 'Jeux')
@section('chapeau', 'Cochez les jeux à proposer : ils s’ouvrent seuls, rien à lancer le jour J. Personnalisez-les si vous le souhaitez.')

@section('contenu')

@php
    $etat = function (array $jeu) {
        if (! $jeu['inclus']) return ['Formule Célébration ou Signature', 'chip-next'];
        if (! $jeu['active']) return ['Non proposé', 'chip-next'];
        if (! $jeu['pret'])   return ['À compléter', 'chip-build'];
        return ['En ligne', 'chip-live'];
    };
    $repondues = $questions->whereNotNull('bonne_reponse')->where('active', true)->count();
@endphp

{{-- ─────────────────────────────────────────── Les jeux proposés ── --}}
<div class="carte">
    <h2>Les jeux proposés</h2>
    <p class="carte-aide">Un jeu coché apparaît dans le menu de vos invités dès qu’il est prêt.</p>

    <div class="liste">
        @foreach ($catalogue as $jeu)
            @php [$libelle, $classe] = $etat($jeu); @endphp
            <div class="ligne @unless($jeu['active']) inactive @endunless">
                <span class="icone"><i class="fa-solid {{ $jeu['icone'] }}" aria-hidden="true"></i></span>
                <div class="corps">
                    <strong>{{ $jeu['nom'] }}</strong>
                    <span>{{ $jeu['desc'] }}</span>
                </div>
                <div class="outils">
                    <span class="chip {{ $classe }}" style="position:static">{{ $libelle }}</span>
                    @if ($jeu['inclus'])
                        <form method="POST" action="{{ route('espace.jeux.basculer', $jeu['cle']) }}">
                            @csrf
                            <label class="interrupteur" title="Proposer ou retirer ce jeu">
                                <input type="checkbox" @checked($jeu['active']) onchange="this.form.submit()">
                                <span></span>
                            </label>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- ──────────────────────────────────────────────── Qui de nous 2 ── --}}
<div class="carte" id="qui-deux">
    <h2>Qui de nous 2 ?</h2>
    <p class="carte-aide">
        Touchez le prénom de la bonne réponse. Une question sans réponse n’est pas posée.
        <strong>{{ $repondues }}</strong> question{{ $repondues > 1 ? 's' : '' }} prête{{ $repondues > 1 ? 's' : '' }}
        @if ($repondues < $questionsMin) — il en faut au moins {{ $questionsMin }} pour ouvrir le jeu. @endif
    </p>

    <div class="liste">
        @foreach ($questions as $question)
            <div class="ligne @unless($question->active) inactive @endunless" id="question-{{ $question->id }}">
                <div class="corps">
                    <strong style="white-space:normal">{{ $question->question }}</strong>
                    <span>
                        @if ($question->reponses_count)
                            {{ $question->reponses_count }} réponse{{ $question->reponses_count > 1 ? 's' : '' }} d’invités
                        @elseif (! $question->bonne_reponse)
                            En attente de votre réponse
                        @else
                            Prête
                        @endif
                    </span>
                </div>
                <div class="outils">
                    @foreach ($prenoms as $prenom)
                        <form method="POST" action="{{ route('espace.jeux.questions.repondre', $question->id) }}">
                            @csrf
                            <input type="hidden" name="reponse" value="{{ $prenom }}">
                            <button class="mini choix-reponse {{ $question->bonne_reponse === $prenom ? 'choisi' : '' }}"
                                    aria-pressed="{{ $question->bonne_reponse === $prenom ? 'true' : 'false' }}">{{ $prenom }}</button>
                        </form>
                    @endforeach
                    <form method="POST" action="{{ route('espace.jeux.questions.basculer', $question->id) }}">
                        @csrf
                        <button class="mini" title="{{ $question->active ? 'Ne plus poser cette question' : 'Poser cette question' }}">
                            <i class="fa-solid {{ $question->active ? 'fa-eye' : 'fa-eye-slash' }}" aria-hidden="true"></i>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('espace.jeux.questions.supprimer', $question->id) }}"
                          onsubmit="return confirm('Supprimer cette question ?')">
                        @csrf @method('DELETE')
                        <button class="mini mini--danger" title="Supprimer"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('espace.jeux.questions.ajouter') }}" class="ajout-ligne">
        @csrf
        <input type="text" name="question" placeholder="Ajouter une question : Qui ronfle le plus ?" required maxlength="255">
        <button class="btn btn-primary">Ajouter</button>
    </form>
</div>

{{-- ──────────────────────────────────────────────── Mots croisés ── --}}
<div class="carte" id="mots-croises">
    <h2>Mots croisés</h2>
    <p class="carte-aide">
        Un mot par ligne, au format <code>MOT : définition</code>. La grille se construit toute seule.
        Ajoutez vos prénoms, vos lieux, vos souvenirs. Laissez vide pour revenir aux mots du mariage.
    </p>

    <div class="grille-jeu">
        <form method="POST" action="{{ route('espace.jeux.mots') }}">
            @csrf
            <div class="champ">
                <textarea name="mots" rows="12" spellcheck="false">{{ old('mots', $texteMots) }}</textarea>
            </div>
            <div class="actions"><button class="btn btn-primary">Régénérer la grille</button></div>
        </form>

        <div>
            <p class="carte-aide" style="margin-bottom:.5rem">
                Aperçu : {{ count($grille['mots']) }} mots placés
                @if ($grille['ecartes'])
                    · <strong>non placés</strong> : {{ implode(', ', $grille['ecartes']) }} (trop longs ou sans lettre commune)
                @endif
            </p>
            <div class="apercu-grille" style="--colonnes: {{ max(1, $grille['largeur']) }}">
                @foreach ($grille['cases'] as $ligne)
                    @foreach ($ligne as $lettre)
                        <span class="{{ $lettre ? 'pleine' : '' }}">{{ $lettre }}</span>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ──────────────────────────────────────────────────── Puzzle ── --}}
@php $puzzle = $catalogue['puzzle']; @endphp
<div class="carte" id="puzzle">
    <h2>Puzzle</h2>
    @if (! $puzzle['inclus'])
        <p class="carte-aide" style="margin:0">Le puzzle est inclus dans les formules Célébration et Signature.</p>
    @else
        <p class="carte-aide">
            De 1 à {{ $puzzleMax }} images : chaque partie en tire une au hasard. Format paysage conseillé.
            @if ($imagesPuzzle->isEmpty())
                @if ($photosCouple->isNotEmpty())
                    Sans image déposée, le puzzle utilise votre photo de couverture et celles de « Notre histoire ».
                @else
                    Déposez au moins une image (ou une photo de couverture) pour ouvrir le jeu.
                @endif
            @endif
        </p>

        @if ($imagesPuzzle->isNotEmpty())
            <div class="vignettes" style="margin-bottom:1rem">
                @foreach ($imagesPuzzle as $i => $chemin)
                    <div class="vignette">
                        <img src="{{ Storage::url($chemin) }}" alt="" loading="lazy">
                        <div class="vignette-pied">
                            <form method="POST" action="{{ route('espace.jeux.puzzle.retirer', $i) }}"
                                  onsubmit="return confirm('Retirer cette image du puzzle ?')">
                                @csrf @method('DELETE')
                                <button class="mini mini--danger">Retirer</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($imagesPuzzle->count() < $puzzleMax)
            <form method="POST" action="{{ route('espace.jeux.puzzle.ajouter') }}" enctype="multipart/form-data" class="ajout-ligne">
                @csrf
                <input type="file" name="images[]" accept="image/*" multiple required>
                <button class="btn btn-primary">Ajouter</button>
            </form>
            <p class="champ-aide">Encore {{ $puzzleMax - $imagesPuzzle->count() }} possible{{ $puzzleMax - $imagesPuzzle->count() > 1 ? 's' : '' }} · 8 Mo par image.</p>
        @endif
    @endif
</div>

{{-- ────────────────────────────────────────────────── Chasse photo ── --}}
<div class="carte" id="chasse">
    <h2>Chasse photo</h2>
    <p class="carte-aide">Une mission par ligne. Validez les photos reçues : chaque photo validée rapporte 20 points à son auteur.</p>

    <form method="POST" action="{{ route('espace.jeux.missions') }}">
        @csrf
        <div class="champ"><textarea name="missions" rows="6">{{ $missions }}</textarea></div>
        <div class="actions"><button class="btn btn-primary">Enregistrer les missions</button></div>
    </form>

    @if ($chasse->isNotEmpty())
        <h3 style="font-size:.95rem; margin:1.4rem 0 .8rem">Photos reçues ({{ $chasse->where('valide', false)->count() }} à valider)</h3>
        <div class="vignettes">
            @foreach ($chasse as $photo)
                <div class="vignette">
                    <a href="{{ Storage::url($photo->photo_path) }}" target="_blank" rel="noopener">
                        <img src="{{ Storage::url($photo->photo_path) }}" alt="" loading="lazy">
                    </a>
                    <div class="vignette-pied">
                        <strong>{{ $photo->participant?->prenom }} {{ $photo->participant?->nom }}</strong>
                        <span style="white-space:normal">{{ $photo->indice }}</span>
                        <div style="display:flex; gap:.4rem; margin-top:.5rem; flex-wrap:wrap">
                            <form method="POST" action="{{ route('espace.jeux.chasse.valider', $photo->id) }}">
                                @csrf
                                <button class="mini {{ $photo->valide ? 'choisi' : '' }}">{{ $photo->valide ? 'Validée ✓' : 'Valider' }}</button>
                            </form>
                            <form method="POST" action="{{ route('espace.jeux.chasse.supprimer', $photo->id) }}"
                                  onsubmit="return confirm('Retirer cette photo ?')">
                                @csrf @method('DELETE')
                                <button class="mini mini--danger">Retirer</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- ──────────────────────────────────────────────────── Classement ── --}}
<div class="carte" id="classement">
    <h2>Classement</h2>

    <form method="POST" action="{{ route('espace.jeux.reglages') }}">
        @csrf
        <div class="grille-champs">
            <div class="champ">
                <label for="lot">Lot annoncé au gagnant</label>
                <input type="text" id="lot" name="lot" value="{{ $lot }}" placeholder="Une bouteille de champagne">
            </div>
            <div class="champ" style="align-self:end">
                <label class="champ-bascule">
                    <input type="checkbox" name="classement" value="1" @checked($avecClassement)>
                    Afficher le classement aux invités
                </label>
            </div>
        </div>
        <div class="actions"><button class="btn btn-primary">Enregistrer</button></div>
    </form>

    @if ($classement->isNotEmpty())
        <div class="liste" style="margin-top:1.2rem">
            @foreach ($classement as $ligne)
                <div class="ligne">
                    <span class="icone" style="font-weight:700">{{ $ligne['rang'] }}</span>
                    <div class="corps">
                        <strong>{{ $ligne['prenom'] }} {{ $ligne['nom'] }}</strong>
                        <span>{{ $ligne['jeux'] }} {{ $ligne['jeux'] > 1 ? 'jeux' : 'jeu' }}</span>
                    </div>
                    <div class="outils"><strong>{{ $ligne['points'] }} pts</strong></div>
                </div>
            @endforeach
        </div>
    @else
        <p class="carte-aide" style="margin:1rem 0 0">Personne n’a encore joué.</p>
    @endif
</div>

@endsection
