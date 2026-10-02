<?php

namespace App\Solen;

use App\Models\ContentBlock;
use App\Models\Event;
use App\Models\Participant;
use App\Models\QuestionQuiDeux;
use App\Models\ScoreJeu;
use App\Models\SessionJeu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Le moteur des jeux.
 *
 * Plus de « session » à lancer à la main : un jeu coché dans les réglages
 * est ouvert dès que son contenu est prêt, et ce contenu est préparé
 * d'office à l'achat. Les mariés n'ont qu'à personnaliser.
 *
 * Un jeu est « prêt » quand il a de quoi être joué :
 *   - Qui de nous 2 : au moins trois questions dont la réponse est désignée ;
 *   - Mots croisés  : une grille d'au moins quatre mots (défaut fourni) ;
 *   - Memory        : toujours (photos du couple, ou pictogrammes) ;
 *   - Puzzle        : au moins une image (déposée, ou photo du couple) ;
 *   - Chasse photo  : au moins une mission (défaut fourni).
 */
class Jeux
{
    public const QUESTIONS_MIN = 3;

    /** Pictogrammes du memory quand le couple n'a pas assez de photos. */
    private const PICTOS = [
        'fa-ring', 'fa-heart', 'fa-cake-candles', 'fa-champagne-glasses',
        'fa-camera-retro', 'fa-music', 'fa-dove', 'fa-gift', 'fa-car', 'fa-church',
    ];

    public function __construct(private readonly CurrentEvent $courant)
    {
    }

    // --- Disponibilité ----------------------------------------------------

    /** Le jeu est-il inclus dans la formule du mariage ? */
    public function inclus(Event $event, string $cle): bool
    {
        $formules = config("solen_schema.jeux.{$cle}.formules");

        return ! $formules || in_array($event->plan, $formules, true);
    }

    /** Le jeu est-il coché par les mariés et inclus dans leur formule ? */
    public function active(Event $event, string $cle): bool
    {
        return $event->aModule('jeux')
            && $this->inclus($event, $cle)
            && in_array($cle, (array) $event->reglage('jeux', 'actifs', []), true);
    }

    public function pret(Event $event, string $cle): bool
    {
        return match ($cle) {
            'qui_deux'     => QuestionQuiDeux::pretes()->count() >= self::QUESTIONS_MIN,
            'mots_croises' => count($this->grille($event)['mots']) >= 4,
            'memory'       => true,
            'puzzle'       => $this->imagesPuzzle($event)->isNotEmpty(),
            'chasse_photo' => $this->missions($event)->isNotEmpty(),
            default        => false,
        };
    }

    public function ouvert(Event $event, string $cle): bool
    {
        return $this->active($event, $cle) && $this->pret($event, $cle);
    }

    /**
     * Les jeux que voient les invités, avec leur fiche de navigation.
     *
     * @return Collection<string, array>
     */
    public function ouverts(Event $event): Collection
    {
        return collect(config('solen_schema.jeux'))
            ->filter(fn ($jeu, $cle) => $this->ouvert($event, $cle));
    }

    /**
     * La session technique d'un jeu : les réponses et les photos de la
     * chasse y sont rattachées. Créée à la volée, jamais à la main.
     */
    public function session(string $type): SessionJeu
    {
        return SessionJeu::firstOrCreate(
            ['type_jeu' => $type, 'actif' => true],
            ['nom' => config("solen_schema.jeux.{$type}.nom", $type), 'debut' => now()]
        );
    }

    // --- Contenu ------------------------------------------------------------

    /** Contenu de départ, posé à l'achat. Ne remplace jamais l'existant. */
    public function preparer(Event $event): void
    {
        $this->courant->pretend($event, function () {
            if (QuestionQuiDeux::count() === 0) {
                foreach (config('solen_schema.questions_defaut') as $i => $question) {
                    QuestionQuiDeux::create([
                        'question'      => $question,
                        'bonne_reponse' => null,
                        'active'        => true,
                        'ordre'         => $i + 1,
                    ]);
                }
            }
        });
    }

    /** @return array{0: string, 1: string} */
    public function prenoms(Event $event): array
    {
        return [$event->partenaire_1 ?: 'Elle', $event->partenaire_2 ?: 'Lui'];
    }

    /** Le texte des mots croisés, tel que les mariés l'ont saisi ou par défaut. */
    public function texteMots(Event $event): string
    {
        return (string) ($event->reglage('jeux', 'mots')
            ?: implode("\n", config('solen_schema.mots_croises_defaut')));
    }

    /** La grille du mariage. Même liste, même grille : rien à stocker. */
    public function grille(Event $event): array
    {
        $texte = $this->texteMots($event);

        return GrilleMotsCroises::construire(
            GrilleMotsCroises::lire($texte),
            crc32($event->id . '|' . $texte)
        );
    }

    /** @return Collection<int, string> */
    public function missions(Event $event): Collection
    {
        $texte = $event->reglage('jeux', 'missions')
            ?: implode("\n", config('solen_schema.missions_defaut'));

        return collect(preg_split('/\R/', (string) $texte))
            ->map(fn ($m) => trim($m))
            ->filter()
            ->values();
    }

    /**
     * Les photos du couple, d'où qu'elles viennent : couverture et frise de
     * « Notre histoire ». Sert au memory et au puzzle par défaut.
     *
     * @return Collection<int, string> URL publiques
     */
    public function photosDuCouple(Event $event): Collection
    {
        $chemins = collect([$event->reglage('site', 'photo_couverture')]);

        $chemins = $chemins->concat(
            ContentBlock::pourPage('histoire')
                ->deType('etape_histoire')
                ->get()
                ->map(fn ($bloc) => $bloc->donnees['image'] ?? null)
        );

        return $chemins
            ->filter()
            ->unique()
            ->map(fn ($c) => Str::startsWith($c, ['http', '/']) ? $c : Storage::url($c))
            ->values();
    }

    /** @return Collection<int, string> chemins sur le disque public */
    public function imagesPuzzleDeposees(Event $event): Collection
    {
        return collect((array) $event->reglage('jeux', 'puzzle_images', []))->filter()->values();
    }

    /** Les images du puzzle : celles déposées, sinon les photos du couple. */
    public function imagesPuzzle(Event $event): Collection
    {
        $deposees = $this->imagesPuzzleDeposees($event)->map(fn ($c) => Storage::url($c));

        return ($deposees->isNotEmpty() ? $deposees : $this->photosDuCouple($event)->take(5))->values();
    }

    /**
     * Les cartes du memory : huit paires, en photos si le couple en a assez,
     * complétées de pictogrammes sinon. Mélangées à chaque partie.
     *
     * @return Collection<int, array{paire: int, image: ?string, picto: ?string}>
     */
    public function cartesMemory(Event $event, int $paires = 8): Collection
    {
        $photos = $this->photosDuCouple($event)->take($paires);

        $faces = $photos->map(fn ($url) => ['image' => $url, 'picto' => null])
            ->concat(collect(self::PICTOS)->take($paires - $photos->count())
                ->map(fn ($p) => ['image' => null, 'picto' => $p]))
            ->values();

        return $faces
            ->flatMap(fn ($face, $i) => [$face + ['paire' => $i], $face + ['paire' => $i]])
            ->shuffle()
            ->values();
    }

    // --- Joueurs et scores -------------------------------------------------

    /**
     * L'invité qui joue : retrouvé par son nom, et retenu en session pour
     * ne pas lui redemander à chaque jeu.
     */
    public function joueur(string $prenom, string $nom): Participant
    {
        $participant = Participant::firstOrCreate([
            'prenom' => trim($prenom),
            'nom'    => trim($nom),
        ]);

        session(['joueur' => [
            'id'     => $participant->id,
            'prenom' => $participant->prenom,
            'nom'    => $participant->nom,
        ]]);

        return $participant;
    }

    /** @return array{id?: int, prenom?: string, nom?: string} */
    public function joueurConnu(): array
    {
        return (array) session('joueur', []);
    }

    /** Ne garde que le meilleur score de chaque invité à chaque jeu. */
    public function enregistrer(Participant $participant, string $type, int $points, array $detail = []): ScoreJeu
    {
        $score = ScoreJeu::firstOrNew([
            'participant_id' => $participant->id,
            'type_jeu'       => $type,
        ]);

        if (! $score->exists || $points > $score->points) {
            $score->fill(['points' => $points, 'detail' => $detail])->save();
        }

        return $score;
    }

    /**
     * Le classement général : la somme des meilleurs scores de chacun.
     *
     * @return Collection<int, array{rang: int, prenom: string, nom: string, points: int, jeux: int, participant_id: int}>
     */
    public function classement(int $limite = 20): Collection
    {
        return ScoreJeu::with('participant')
            ->get()
            ->groupBy('participant_id')
            ->map(fn ($scores) => [
                'participant_id' => $scores->first()->participant_id,
                'prenom'         => $scores->first()->participant?->prenom,
                'nom'            => $scores->first()->participant?->nom,
                'points'         => $scores->sum('points'),
                'jeux'           => $scores->count(),
            ])
            ->sortByDesc('points')
            ->values()
            ->take($limite)
            ->map(fn ($ligne, $i) => $ligne + ['rang' => $i + 1]);
    }

    /** Le rang d'un invité au classement, ou null s'il n'a pas joué. */
    public function rang(int $participantId): ?int
    {
        return $this->classement(PHP_INT_MAX)->firstWhere('participant_id', $participantId)['rang'] ?? null;
    }
}
