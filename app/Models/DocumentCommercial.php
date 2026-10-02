<?php

namespace App\Models;

use App\Solen\Entreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

/**
 * Un devis, une facture ou un avoir de Solen by NJIEZM.FR.
 *
 * Hors cloisonnement par mariage : c'est un document de l'entreprise, qui
 * peut concerner un mariage (event_id) ou un prospect qui n'en a pas encore.
 */
class DocumentCommercial extends Model
{
    protected $table = 'documents_commerciaux';

    public const DEVIS   = 'devis';
    public const FACTURE = 'facture';
    public const AVOIR   = 'avoir';

    public const TYPES = [
        self::DEVIS   => ['nom' => 'Devis',   'prefixe' => 'D'],
        self::FACTURE => ['nom' => 'Facture', 'prefixe' => 'F'],
        self::AVOIR   => ['nom' => 'Avoir',   'prefixe' => 'A'],
    ];

    public const STATUTS = [
        'brouillon' => 'Brouillon',
        'emis'      => 'Émis',
        'paye'      => 'Payé',
        'annule'    => 'Annulé par avoir',
        'accepte'   => 'Accepté',
        'refuse'    => 'Refusé',
        'converti'  => 'Facturé',
    ];

    public const NATURES = [
        'standard' => null,
        'acompte'  => 'Facture d’acompte',
        'solde'    => 'Facture de solde',
    ];

    public const MODES_PAIEMENT = [
        'carte'    => 'Carte bancaire',
        'virement' => 'Virement',
        'especes'  => 'Espèces',
        'cheque'   => 'Chèque',
    ];

    protected $fillable = [
        'type', 'statut', 'event_id', 'commande_id', 'origine_id',
        'client_nom', 'client_email', 'client_telephone', 'client_adresse',
        'objet', 'lignes', 'notes', 'notes_internes',
        'nature', 'remise_type', 'remise_valeur', 'remise_libelle', 'code_promo_id',
        'emis_le', 'echeance_le', 'valide_jusqu_au', 'paye_le', 'mode_paiement',
        'envoye_email_le', 'envoye_whatsapp_le',
    ];

    protected function casts(): array
    {
        return [
            'lignes'             => 'array',
            'vendeur'            => 'array',
            'emis_le'            => 'date',
            'echeance_le'        => 'date',
            'valide_jusqu_au'    => 'date',
            'paye_le'            => 'datetime',
            'envoye_email_le'    => 'datetime',
            'envoye_whatsapp_le' => 'datetime',
            'total_centimes'     => 'integer',
            'sous_total_centimes' => 'integer',
            'remise_centimes'    => 'integer',
            'remise_valeur'      => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $doc) {
            $doc->uuid ??= (string) Str::uuid();
        });

        // Les montants se recalculent toujours depuis les lignes et la
        // remise : ils ne sont jamais saisis.
        static::saving(function (self $doc) {
            if (! $doc->exists || $doc->isDirty(['lignes', 'remise_type', 'remise_valeur'])) {
                [$sousTotal, $remise, $total] = $doc->calculer();
                $doc->sous_total_centimes = $sousTotal;
                $doc->remise_centimes = $remise;
                $doc->total_centimes = $total;
            }
        });

        // Une facture ou un avoir émis est figé. Seuls l'état de paiement et
        // la trace des envois peuvent encore bouger.
        static::updating(function (self $doc) {
            $verrouille = $doc->getOriginal('numero') && $doc->getOriginal('type') !== self::DEVIS;
            $libres = ['statut', 'paye_le', 'mode_paiement', 'envoye_email_le', 'envoye_whatsapp_le', 'notes_internes', 'updated_at'];

            if ($verrouille && array_diff(array_keys($doc->getDirty()), $libres)) {
                throw new LogicException('Un document émis ne peut plus être modifié : annulez-le par un avoir.');
            }
        });

        static::deleting(function (self $doc) {
            if ($doc->numero && $doc->type !== self::DEVIS) {
                throw new LogicException('Une facture ou un avoir émis ne se supprime jamais.');
            }
        });
    }

    // --- Relations -------------------------------------------------------

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class)->withoutGlobalScopes();
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function origine(): BelongsTo
    {
        return $this->belongsTo(self::class, 'origine_id');
    }

    public function derives(): HasMany
    {
        return $this->hasMany(self::class, 'origine_id');
    }

    public function scopeDeType(Builder $q, string $type): Builder
    {
        return $q->where('type', $type);
    }

    // --- Calculs -----------------------------------------------------------

    /**
     * Sous-total, remise et total, en centimes.
     *
     * La remise porte sur les prestations, pas sur les déductions d'acompte
     * d'une facture de solde : sinon 10 % de remise sur un solde déjà
     * diminué des acomptes ne vaudrait plus 10 % du prix.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    public function calculer(): array
    {
        $montant = fn ($l) => (int) round(((float) ($l['quantite'] ?? 1)) * (int) ($l['prix_unitaire_centimes'] ?? 0));
        $lignes  = collect($this->lignes ?? []);

        $prestations = (int) $lignes->reject(fn ($l) => ! empty($l['deduction']))->sum($montant);
        $deductions  = (int) $lignes->filter(fn ($l) => ! empty($l['deduction']))->sum($montant);

        $remise = 0;
        if ($this->remise_type && (float) $this->remise_valeur > 0 && $prestations > 0) {
            $remise = $this->remise_type === 'pourcentage'
                ? (int) round($prestations * ((float) $this->remise_valeur) / 100)
                : (int) round(((float) $this->remise_valeur) * 100);
            $remise = min($remise, $prestations);
        }

        return [$prestations + $deductions, $remise, $prestations - $remise + $deductions];
    }

    public function calculerTotal(): int
    {
        return $this->calculer()[2];
    }

    public function libelleRemise(): ?string
    {
        if (! $this->remise_centimes) {
            return null;
        }

        if ($this->remise_libelle) {
            return $this->remise_libelle;
        }

        return $this->remise_type === 'pourcentage'
            ? 'Remise de ' . rtrim(rtrim(number_format((float) $this->remise_valeur, 2, ',', ''), '0'), ',') . ' %'
            : 'Remise';
    }

    public function nomDuType(): string
    {
        return self::NATURES[$this->nature ?? 'standard'] ?? (self::TYPES[$this->type]['nom'] ?? $this->type);
    }

    public function libelle(): string
    {
        return $this->nomDuType() . ' ' . ($this->numero ?? 'brouillon');
    }

    public function estEmis(): bool
    {
        return (bool) $this->numero;
    }

    public static function euros(int $centimes): string
    {
        return number_format($centimes / 100, 2, ',', ' ') . ' €';
    }

    // --- Cycle de vie ----------------------------------------------------

    /**
     * Émettre : attribuer le numéro suivant, figer l'identité du vendeur.
     * Le verrou de table garantit qu'aucun numéro n'est donné deux fois,
     * même si deux ventes tombent à la même seconde.
     */
    public function emettre(): self
    {
        if ($this->numero) {
            return $this;
        }

        if ($this->type !== self::DEVIS && ! Entreprise::complete()) {
            throw new LogicException('Mentions légales incomplètes : ' . implode(', ', Entreprise::manquants()) . '.');
        }

        return DB::transaction(function () {
            $annee   = now()->year;
            $prefixe = self::TYPES[$this->type]['prefixe'] . '-' . $annee . '-';

            $dernier = self::where('numero', 'like', $prefixe . '%')
                ->lockForUpdate()
                ->orderByDesc('numero')
                ->value('numero');

            $suivant = $dernier ? ((int) substr($dernier, -4)) + 1 : 1;

            $this->forceFill([
                'numero'      => $prefixe . str_pad((string) $suivant, 4, '0', STR_PAD_LEFT),
                'statut'      => $this->statut === 'paye' ? 'paye' : 'emis',
                'emis_le'     => $this->emis_le ?? now(),
                'echeance_le' => $this->type === self::FACTURE
                    ? ($this->echeance_le ?? now()->addDays(Entreprise::get('delai_paiement_jours', 15)))
                    : $this->echeance_le,
                'valide_jusqu_au' => $this->type === self::DEVIS ? ($this->valide_jusqu_au ?? now()->addDays(30)) : null,
                'vendeur'     => Entreprise::tout(),
            ])->saveQuietly();

            return $this;
        });
    }

    /** Annuler une facture : un avoir du même montant, en négatif. */
    public function annulerParAvoir(?string $motif = null): self
    {
        if ($this->type !== self::FACTURE || ! $this->numero) {
            throw new LogicException('Seule une facture émise peut être annulée par un avoir.');
        }

        return DB::transaction(function () use ($motif) {
            $avoir = self::create([
                'type'             => self::AVOIR,
                'event_id'         => $this->event_id,
                'origine_id'       => $this->id,
                'client_nom'       => $this->client_nom,
                'client_email'     => $this->client_email,
                'client_telephone' => $this->client_telephone,
                'client_adresse'   => $this->client_adresse,
                'objet'            => 'Avoir sur la facture ' . $this->numero,
                'lignes'           => collect($this->lignes)->map(fn ($l) => [
                    ...$l, 'prix_unitaire_centimes' => -((int) $l['prix_unitaire_centimes']), 'deduction' => false,
                ])->when($this->remise_centimes > 0, fn ($c) => $c->push([
                    'designation' => 'Annulation de la remise accordée', 'detail' => $this->libelleRemise(),
                    'quantite' => 1, 'prix_unitaire_centimes' => $this->remise_centimes,
                ]))->all(),
                'notes'            => $motif,
            ])->emettre();

            $this->update(['statut' => 'annule']);

            return $avoir;
        });
    }

    /** Un devis accepté devient une facture, ligne pour ligne. */
    public function convertirEnFacture(): self
    {
        if ($this->type !== self::DEVIS) {
            throw new LogicException('Seul un devis se convertit en facture.');
        }

        return DB::transaction(function () {
            $facture = self::create([
                'type'             => self::FACTURE,
                'event_id'         => $this->event_id,
                'origine_id'       => $this->id,
                'client_nom'       => $this->client_nom,
                'client_email'     => $this->client_email,
                'client_telephone' => $this->client_telephone,
                'client_adresse'   => $this->client_adresse,
                'objet'            => $this->objet,
                'lignes'           => $this->lignes,
                'remise_type'      => $this->remise_type,
                'remise_valeur'    => $this->remise_valeur,
                'remise_libelle'   => $this->remise_libelle,
                'code_promo_id'    => $this->code_promo_id,
                'notes'            => $this->numero ? 'Selon devis ' . $this->numero . ' accepté.' : null,
            ]);

            $this->forceFill(['statut' => 'converti'])->saveQuietly();

            return $facture;
        });
    }

    /** Les factures d'acompte émises sur ce devis, et non annulées. */
    public function acomptes()
    {
        return self::where('origine_id', $this->id)->where('nature', 'acompte')
            ->whereNotNull('numero')->where('statut', '!=', 'annule')->orderBy('id')->get();
    }

    /**
     * Une facture d'acompte sur un devis : un pourcentage du total, ou un
     * montant fixe. Elle reste en brouillon, à vérifier puis émettre.
     */
    public function factureAcompte(string $mode, float $valeur): self
    {
        if ($this->type !== self::DEVIS || ! $this->numero) {
            throw new LogicException('Un acompte se facture sur un devis émis.');
        }

        $montant = $mode === 'pourcentage'
            ? (int) round($this->total_centimes * $valeur / 100)
            : (int) round($valeur * 100);

        $dejaFacture = (int) $this->acomptes()->sum('total_centimes');

        if ($montant <= 0 || $montant + $dejaFacture > $this->total_centimes) {
            throw new LogicException('Les acomptes ne peuvent pas dépasser le total du devis (' . self::euros($this->total_centimes) . ').');
        }

        $libelle = $mode === 'pourcentage'
            ? 'Acompte de ' . rtrim(rtrim(number_format($valeur, 2, ',', ''), '0'), ',') . ' %'
            : 'Acompte';

        if ($this->statut === 'emis') {
            $this->forceFill(['statut' => 'accepte'])->saveQuietly();
        }

        return self::create([
            'type'             => self::FACTURE,
            'nature'           => 'acompte',
            'event_id'         => $this->event_id,
            'origine_id'       => $this->id,
            'client_nom'       => $this->client_nom,
            'client_email'     => $this->client_email,
            'client_telephone' => $this->client_telephone,
            'client_adresse'   => $this->client_adresse,
            'objet'            => $this->objet,
            'lignes'           => [[
                'designation' => "{$libelle} sur le devis {$this->numero}",
                'detail'      => 'Montant total du devis : ' . self::euros($this->total_centimes),
                'quantite' => 1, 'prix_unitaire_centimes' => $montant,
            ]],
        ]);
    }

    /**
     * La facture de solde : toutes les prestations du devis, remise
     * comprise, moins chaque acompte déjà facturé.
     */
    public function factureSolde(): self
    {
        if ($this->type !== self::DEVIS || ! $this->numero) {
            throw new LogicException('Le solde se facture sur un devis émis.');
        }

        $deductions = $this->acomptes()->map(fn (self $a) => [
            'designation' => "Acompte versé — facture {$a->numero}",
            'detail'      => 'du ' . $a->emis_le?->translatedFormat('j F Y'),
            'quantite'    => 1,
            'prix_unitaire_centimes' => -$a->total_centimes,
            'deduction'   => true,
        ]);

        $facture = $this->convertirEnFacture();
        $facture->update([
            'nature' => $deductions->isEmpty() ? 'standard' : 'solde',
            'lignes' => collect($facture->lignes)->concat($deductions)->all(),
            'notes'  => 'Selon devis ' . $this->numero . ' accepté.' . ($deductions->isNotEmpty() ? ' Acomptes déduits.' : ''),
        ]);

        return $facture;
    }

    public function marquerPaye(string $mode, $date = null): self
    {
        $this->update(['statut' => 'paye', 'mode_paiement' => $mode, 'paye_le' => $date ?? now()]);

        return $this;
    }
}
