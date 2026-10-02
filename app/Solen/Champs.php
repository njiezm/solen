<?php

namespace App\Solen;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * Le traducteur du schéma déclaratif.
 *
 * À partir d'une liste de champs décrite dans config/solen_schema.php, il
 * produit les règles de validation, les intitulés d'erreur, et les valeurs
 * normalisées prêtes à être stockées.
 *
 * C'est ce qui permet d'ajouter un réglage ou un type de bloc sans écrire
 * ni contrôleur ni formulaire.
 */
class Champs
{
    /**
     * Règles de validation déduites du schéma.
     *
     * @param  list<array<string, mixed>>  $champs
     * @param  string                      $prefixe  Espace de nommage des entrées du formulaire.
     * @return array<string, mixed>
     */
    public function regles(array $champs, string $prefixe = 'donnees'): array
    {
        $regles = [];

        foreach ($champs as $champ) {
            $cle  = "{$prefixe}.{$champ['cle']}";
            $base = empty($champ['requis']) ? ['nullable'] : ['required'];

            $regles[$cle] = array_merge($base, $this->reglesDuType($champ));

            if (($champ['type'] ?? null) === 'choix_multiple') {
                $regles["{$cle}.*"] = ['string', 'in:' . implode(',', array_keys($champ['options'] ?? []))];
            }
        }

        return $regles;
    }

    /** @return list<string> */
    private function reglesDuType(array $champ): array
    {
        $type = $champ['type'] ?? 'texte';

        return match ($type) {
            'texte', 'telephone'   => ['string', 'max:255'],
            'texte_long', 'riche'  => ['string', 'max:5000'],
            'nombre'               => array_filter([
                                          'integer',
                                          isset($champ['min']) ? 'min:' . $champ['min'] : null,
                                          isset($champ['max']) ? 'max:' . $champ['max'] : null,
                                      ]),
            'montant'              => ['numeric', 'min:0'],
            'date'                 => ['date'],
            'heure'                => ['date_format:H:i'],
            'datetime'             => ['date'],
            'image'                => ['file', 'image', 'max:5120'],
            // Un fichier peut déclarer ses formats et son poids (en Ko) :
            // un livret de cérémonie en PDF dépasse vite 5 Mo.
            'fichier'              => array_filter([
                                          'file',
                                          isset($champ['formats']) ? 'mimes:' . $champ['formats'] : null,
                                          'max:' . ($champ['poids_max'] ?? 5120),
                                      ]),
            'images'               => ['array'],
            'couleur'              => ['regex:/^#[0-9A-Fa-f]{6}$/'],
            'choix'                => ['string', 'in:' . implode(',', array_keys($champ['options'] ?? []))],
            'choix_multiple'       => ['array'],
            'booleen'              => ['boolean'],
            'lien'                 => ['url', 'max:500'],
            'email'                => ['email', 'max:255'],
            default                => ['string', 'max:1000'],
        };
    }

    /**
     * Intitulés lisibles, pour que les messages d'erreur parlent au client.
     *
     * @param  list<array<string, mixed>>  $champs
     * @return array<string, string>
     */
    public function intitules(array $champs, string $prefixe = 'donnees'): array
    {
        return collect($champs)
            ->mapWithKeys(function ($champ) use ($prefixe) {
                $cle = $prefixe ? "{$prefixe}.{$champ['cle']}" : $champ['cle'];

                // Le libellé est repris tel quel : « Le champ Nom du plat est
                // obligatoire » se lit mieux que sa version en minuscules.
                return [$cle => $champ['label'] ?? $champ['cle']];
            })
            ->all();
    }

    /**
     * Transforme les entrées du formulaire en valeurs stockables : cases à
     * cocher converties en booléens, fichiers téléversés et remplacés par
     * leur chemin, champs vides écartés.
     *
     * @param  list<array<string, mixed>>  $champs
     * @param  array<string, mixed>        $existant  Valeurs actuelles, pour ne pas
     *                                                perdre une image non remplacée.
     * @return array<string, mixed>
     */
    public function normaliser(array $champs, Request $request, string $prefixe = 'donnees', array $existant = []): array
    {
        $saisi   = $request->input($prefixe, []);
        $valeurs = [];

        foreach ($champs as $champ) {
            $cle  = $champ['cle'];
            $type = $champ['type'] ?? 'texte';

            if (in_array($type, ['image', 'fichier'], true)) {
                $valeurs[$cle] = $this->fichier($request, $prefixe, $cle, $existant[$cle] ?? null);

                continue;
            }

            if ($type === 'booleen') {
                // Une case décochée n'est pas envoyée par le navigateur.
                $valeurs[$cle] = (bool) Arr::get($saisi, $cle, false);

                continue;
            }

            if ($type === 'choix_multiple') {
                $valeurs[$cle] = array_values(Arr::get($saisi, $cle, []) ?: []);

                continue;
            }

            $valeur = Arr::get($saisi, $cle);

            if ($type === 'nombre' && $valeur !== null && $valeur !== '') {
                $valeur = (int) $valeur;
            }

            if ($type === 'montant' && $valeur !== null && $valeur !== '') {
                $valeur = (float) $valeur;
            }

            $valeurs[$cle] = $valeur === '' ? null : $valeur;
        }

        return $valeurs;
    }

    /**
     * Téléversement d'un fichier. Trois cas : un nouveau fichier remplace
     * l'ancien, une case « supprimer » vide la valeur, sinon on conserve.
     */
    private function fichier(Request $request, string $prefixe, string $cle, ?string $actuel): ?string
    {
        if ($request->boolean("supprimer.{$prefixe}.{$cle}")) {
            $this->oublier($actuel);

            return null;
        }

        $fichier = $request->file("{$prefixe}.{$cle}");

        if (! $fichier instanceof UploadedFile) {
            return $actuel;
        }

        $dossier = 'evenements/' . (app(CurrentEvent::class)->id() ?? 'commun');
        $chemin  = $fichier->store($dossier, 'public');

        $this->oublier($actuel);

        return $chemin;
    }

    /** Ne supprime que ce que nous avons nous-mêmes stocké. */
    private function oublier(?string $chemin): void
    {
        if ($chemin && Storage::disk('public')->exists($chemin)) {
            Storage::disk('public')->delete($chemin);
        }
    }

    /**
     * Le formulaire contient-il au moins un champ de type fichier ?
     * Détermine l'attribut enctype du formulaire.
     *
     * @param  list<array<string, mixed>>  $champs
     */
    public function contientUnFichier(array $champs): bool
    {
        return collect($champs)->contains(
            fn ($champ) => in_array($champ['type'] ?? '', ['image', 'images', 'fichier'], true)
        );
    }
}
