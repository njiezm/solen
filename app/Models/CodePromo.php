<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Un code promo : en console comme à la commande en ligne. */
class CodePromo extends Model
{
    protected $table = 'codes_promo';

    protected $fillable = [
        'code', 'libelle', 'type', 'valeur', 'formules', 'debut_le', 'fin_le',
        'utilisations_max', 'utilisations', 'actif',
    ];

    protected function casts(): array
    {
        return [
            'valeur'   => 'decimal:2',
            'formules' => 'array',
            'debut_le' => 'date',
            'fin_le'   => 'date',
            'actif'    => 'boolean',
        ];
    }

    public static function trouver(?string $code): ?self
    {
        $code = strtoupper(trim((string) $code));

        return $code === '' ? null : static::where('code', $code)->first();
    }

    /** Pourquoi ce code ne s'applique pas, ou null s'il est valable. */
    public function refus(?string $formule = null): ?string
    {
        return match (true) {
            ! $this->actif                                              => 'Ce code n’est plus actif.',
            $this->debut_le && $this->debut_le->isFuture()             => 'Ce code n’est pas encore valable.',
            $this->fin_le && $this->fin_le->endOfDay()->isPast()       => 'Ce code a expiré.',
            $this->utilisations_max !== null && $this->utilisations >= $this->utilisations_max => 'Ce code a atteint son nombre d’utilisations.',
            $formule && $this->formules && ! in_array($formule, $this->formules, true) => 'Ce code ne s’applique pas à cette formule.',
            default                                                     => null,
        };
    }

    /** Montant de la remise, en centimes, plafonné au montant remisé. */
    public function remise(int $centimes): int
    {
        $remise = $this->type === 'pourcentage'
            ? (int) round($centimes * ((float) $this->valeur) / 100)
            : (int) round(((float) $this->valeur) * 100);

        return max(0, min($remise, $centimes));
    }

    public function description(): string
    {
        $valeur = $this->type === 'pourcentage'
            ? rtrim(rtrim(number_format((float) $this->valeur, 2, ',', ''), '0'), ',') . ' %'
            : DocumentCommercial::euros((int) round($this->valeur * 100));

        return "Code {$this->code} : −{$valeur}" . ($this->libelle ? " ({$this->libelle})" : '');
    }
}
