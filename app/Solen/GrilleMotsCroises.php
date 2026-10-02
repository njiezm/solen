<?php

namespace App\Solen;

use Illuminate\Support\Str;

/**
 * Construit une grille de mots croisés à partir d'une simple liste de mots.
 *
 * Les mariés n'ont plus à placer chaque mot case par case : ils écrivent
 * « ALLIANCE : Elle se porte à l'annulaire », la grille se fait seule.
 *
 * Méthode gloutonne classique : les mots les plus longs d'abord, chacun
 * placé là où il croise le plus de lettres déjà posées, sans jamais en
 * toucher un autre par le côté. Le tirage est déterministe (graine fixe) :
 * la même liste donne toujours la même grille, ce qui permet de corriger
 * côté serveur sans rien stocker.
 */
final class GrilleMotsCroises
{
    /** Au-delà, les cases deviennent trop petites sur un téléphone. */
    public const COTE_MAX = 13;

    /** @var array<string, string> « x,y » => lettre */
    private array $cases = [];

    /** @var list<array{mot: string, definition: string, x: int, y: int, direction: string}> */
    private array $places = [];

    /**
     * @param  list<array{mot: string, definition: string}>  $entrees
     * @return array{largeur: int, hauteur: int, cases: array<int, array<int, ?string>>, mots: list<array>, ecartes: list<string>}
     */
    public static function construire(array $entrees, int $graine = 1, int $essais = 60): array
    {
        // Plusieurs tirages, on garde le meilleur : le plus de mots placés,
        // puis la grille la plus compacte. Reste déterministe, la suite
        // des graines dérivant de la graine de départ.
        $meilleure = null;

        for ($i = 0; $i < $essais; $i++) {
            $grille = (new self)->generer($entrees, $graine + $i * 7919, $i > 0);
            $note = [count($grille['mots']), -($grille['largeur'] * $grille['hauteur'])];

            if (! $meilleure || $note > $meilleure[0]) {
                $meilleure = [$note, $grille];
            }

            if ($grille['ecartes'] === [] && $i >= 10) {
                break;
            }
        }

        return $meilleure[1] ?? (new self)->exporter([]);
    }

    /**
     * Lit la saisie des mariés : une ligne par mot, « MOT : définition ».
     *
     * @return list<array{mot: string, definition: string}>
     */
    public static function lire(string $texte): array
    {
        return collect(preg_split('/\R/', $texte))
            ->map(function (string $ligne) {
                [$mot, $definition] = array_pad(preg_split('/\s*[:=–—-]\s+|\s*:\s*/u', trim($ligne), 2), 2, '');

                return ['mot' => self::normaliser($mot), 'definition' => trim($definition)];
            })
            ->filter(fn ($e) => mb_strlen($e['mot']) >= 2 && $e['definition'] !== '')
            ->unique('mot')
            ->values()
            ->all();
    }

    /** « Vœux » devient « VOEUX » : une case, une lettre, sans accent. */
    public static function normaliser(string $mot): string
    {
        $mot = str_replace(['œ', 'Œ', 'æ', 'Æ'], ['oe', 'OE', 'ae', 'AE'], $mot);

        return preg_replace('/[^A-Z]/', '', Str::upper(Str::ascii($mot)));
    }

    private function generer(array $entrees, int $graine, bool $varier = false): array
    {
        mt_srand($graine);

        // Les plus longs d'abord ; à longueur égale, un ordre tiré au sort.
        // Pour les essais suivants, l'ordre est bousculé de quelques rangs.
        $entrees = collect($entrees)
            ->map(fn ($e) => $e + ['_poids' => strlen($e['mot']) + ($varier ? mt_rand(0, 400) / 100 : 0), '_alea' => mt_rand()])
            ->sortBy([['_poids', 'desc'], ['_alea', 'asc']])
            ->values()
            ->all();

        $ecartes = [];

        foreach ($entrees as $i => $entree) {
            $mot = $entree['mot'];

            if (strlen($mot) > self::COTE_MAX) {
                $ecartes[] = $mot;
                continue;
            }

            if ($i === 0 || $this->places === []) {
                $this->poser($entree, 0, 0, 'horizontal');
                continue;
            }

            $meilleur = $this->meilleurePlace($mot);

            if ($meilleur) {
                $this->poser($entree, ...$meilleur);
            } else {
                $ecartes[] = $mot;
            }
        }

        mt_srand();

        return $this->exporter($ecartes);
    }

    /** @return array{0: int, 1: int, 2: string}|null */
    private function meilleurePlace(string $mot): ?array
    {
        $candidats = [];

        foreach ($this->cases as $cle => $lettre) {
            [$cx, $cy] = array_map('intval', explode(',', $cle));

            for ($i = 0, $n = strlen($mot); $i < $n; $i++) {
                if ($mot[$i] !== $lettre) {
                    continue;
                }

                foreach (['horizontal' => [$cx - $i, $cy], 'vertical' => [$cx, $cy - $i]] as $sens => [$x, $y]) {
                    $croisements = $this->evaluer($mot, $x, $y, $sens);

                    if ($croisements > 0) {
                        $candidats[] = [$croisements, mt_rand(), $x, $y, $sens];
                    }
                }
            }
        }

        if ($candidats === []) {
            return null;
        }

        // Le plus de croisements, puis le hasard (déterministe) pour départager.
        usort($candidats, fn ($a, $b) => [$b[0], $a[1]] <=> [$a[0], $b[1]]);

        return [$candidats[0][2], $candidats[0][3], $candidats[0][4]];
    }

    /** Nombre de croisements si le mot peut être posé ici, -1 sinon. */
    private function evaluer(string $mot, int $x, int $y, string $sens): int
    {
        [$dx, $dy] = $sens === 'horizontal' ? [1, 0] : [0, 1];
        $n = strlen($mot);

        // Une case vide avant et après le mot : deux mots bout à bout se liraient comme un seul.
        if ($this->lettre($x - $dx, $y - $dy) !== null || $this->lettre($x + $dx * $n, $y + $dy * $n) !== null) {
            return -1;
        }

        if (! $this->tient($x, $y, $x + $dx * ($n - 1), $y + $dy * ($n - 1))) {
            return -1;
        }

        $croisements = 0;

        for ($i = 0; $i < $n; $i++) {
            $cx = $x + $dx * $i;
            $cy = $y + $dy * $i;
            $existante = $this->lettre($cx, $cy);

            if ($existante !== null) {
                if ($existante !== $mot[$i]) {
                    return -1;
                }
                $croisements++;
                continue;
            }

            // Une case neuve ne doit pas toucher de lettre sur ses côtés.
            if ($this->lettre($cx + $dy, $cy + $dx) !== null || $this->lettre($cx - $dy, $cy - $dx) !== null) {
                return -1;
            }
        }

        // Un mot entièrement superposé à un autre n'apporte rien.
        return $croisements === $n ? -1 : $croisements;
    }

    /** La grille reste-t-elle dans le carré maximal une fois le mot posé ? */
    private function tient(int $x1, int $y1, int $x2, int $y2): bool
    {
        [$minX, $minY, $maxX, $maxY] = $this->bornes();

        return max($maxX, $x2) - min($minX, $x1) < self::COTE_MAX
            && max($maxY, $y2) - min($minY, $y1) < self::COTE_MAX;
    }

    private function poser(array $entree, int $x, int $y, string $sens): void
    {
        [$dx, $dy] = $sens === 'horizontal' ? [1, 0] : [0, 1];

        for ($i = 0, $n = strlen($entree['mot']); $i < $n; $i++) {
            $this->cases[($x + $dx * $i) . ',' . ($y + $dy * $i)] = $entree['mot'][$i];
        }

        $this->places[] = [
            'mot'        => $entree['mot'],
            'definition' => $entree['definition'],
            'x'          => $x,
            'y'          => $y,
            'direction'  => $sens,
        ];
    }

    private function lettre(int $x, int $y): ?string
    {
        return $this->cases["{$x},{$y}"] ?? null;
    }

    /** @return array{0: int, 1: int, 2: int, 3: int} */
    private function bornes(): array
    {
        if ($this->cases === []) {
            return [0, 0, 0, 0];
        }

        $xs = $ys = [];
        foreach (array_keys($this->cases) as $cle) {
            [$x, $y] = explode(',', $cle);
            $xs[] = (int) $x;
            $ys[] = (int) $y;
        }

        return [min($xs), min($ys), max($xs), max($ys)];
    }

    public function exporter(array $ecartes): array
    {
        [$minX, $minY, $maxX, $maxY] = $this->bornes();
        $largeur = $this->places ? $maxX - $minX + 1 : 0;
        $hauteur = $this->places ? $maxY - $minY + 1 : 0;

        $cases = [];
        for ($y = 0; $y < $hauteur; $y++) {
            for ($x = 0; $x < $largeur; $x++) {
                $cases[$y][$x] = $this->lettre($x + $minX, $y + $minY);
            }
        }

        // Numérotation de lecture : de haut en bas, de gauche à droite. Deux
        // mots qui partent de la même case partagent le même numéro.
        $mots = collect($this->places)
            ->map(fn ($m) => ['x' => $m['x'] - $minX, 'y' => $m['y'] - $minY] + $m)
            ->sortBy([['y', 'asc'], ['x', 'asc']])
            ->values();

        $numeros = [];
        $mots = $mots->map(function ($m) use (&$numeros) {
            $cle = "{$m['x']},{$m['y']}";
            $numeros[$cle] ??= count($numeros) + 1;

            return $m + ['numero' => $numeros[$cle], 'longueur' => strlen($m['mot'])];
        });

        return [
            'largeur' => $largeur,
            'hauteur' => $hauteur,
            'cases'   => $cases,
            'mots'    => $mots->all(),
            'ecartes' => $ecartes,
        ];
    }
}
