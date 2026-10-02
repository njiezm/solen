<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Un morceau de contenu éditable : une étape de votre histoire, un hôtel,
 * un contact, un défunt, un plat, un paragraphe libre.
 *
 * Sa forme est décrite par son `type` dans config/solen_schema.php, ce qui
 * permet à l'espace client d'en générer le formulaire tout seul. C'est ce
 * qui remplace les tableaux PHP écrits en dur dans les contrôleurs.
 */
class ContentBlock extends Model
{
    use BelongsToEvent;

    protected $fillable = [
        'event_id', 'page', 'type', 'donnees', 'ordre', 'actif',
    ];

    protected function casts(): array
    {
        return [
            'donnees' => 'array',
            'actif'   => 'boolean',
            'ordre'   => 'integer',
        ];
    }

    // --- Portées --------------------------------------------------------

    public function scopePourPage(Builder $query, string $page): Builder
    {
        return $query->where('page', $page)->where('actif', true)->orderBy('ordre');
    }

    public function scopeDeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // --- Schéma ---------------------------------------------------------

    /** @return array<string, mixed>|null */
    public function definition(): ?array
    {
        return config("solen_schema.blocs.{$this->type}");
    }

    /** @return list<array<string, mixed>> */
    public function champs(): array
    {
        return $this->definition()['champs'] ?? [];
    }

    public function nomDuType(): string
    {
        return $this->definition()['nom'] ?? $this->type;
    }

    /** L'étiquette du bloc dans les listes de l'administration. */
    public function etiquette(): string
    {
        $cle = $this->definition()['titre_depuis'] ?? null;

        return $cle ? ($this->valeur($cle) ?: $this->nomDuType()) : $this->nomDuType();
    }

    /** Lecture d'un champ, avec repli sur la valeur par défaut du schéma. */
    public function valeur(string $cle, mixed $defaut = null): mixed
    {
        if (array_key_exists($cle, $this->donnees ?? [])) {
            return $this->donnees[$cle];
        }

        foreach ($this->champs() as $champ) {
            if ($champ['cle'] === $cle) {
                return $champ['defaut'] ?? $defaut;
            }
        }

        return $defaut;
    }

    /**
     * Les types de blocs proposés sur une page donnée : ceux qui lui sont
     * dédiés, plus les blocs universels (page à null).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function typesPourPage(string $page): array
    {
        $autorises = config("solen_schema.pages.{$page}.blocs");

        return collect(config('solen_schema.blocs'))
            ->filter(function ($def, $type) use ($page, $autorises) {
                if (is_array($autorises)) {
                    return in_array($type, $autorises, true);
                }

                return ($def['page'] ?? null) === $page || ($def['page'] ?? null) === null;
            })
            ->all();
    }
}
