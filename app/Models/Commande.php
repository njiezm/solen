<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * L'achat d'une formule. Le chiffre d'affaires de Solen, par opposition
 * à urne_dons qui est l'argent des invités destiné aux mariés.
 */
class Commande extends Model
{
    public const EN_ATTENTE = 'en_attente';
    public const PAYEE      = 'payee';
    public const HONOREE    = 'honoree';   // le mariage a été créé
    public const ANNULEE    = 'annulee';

    protected $fillable = [
        'uuid', 'plan', 'nom', 'partenaire_1', 'partenaire_2', 'email',
        'date_principale', 'timezone', 'lieu_ville', 'type_ceremonie', 'parties', 'theme_id',
        'statut', 'montant_centimes', 'stripe_session_id', 'stripe_payment_intent',
        'paye_at', 'event_id', 'code_promo_id', 'remise_centimes', 'type', 'plan_origine',
    ];

    public function estMontee(): bool
    {
        return $this->type === 'montee';
    }

    protected function casts(): array
    {
        return [
            'parties'          => 'array',
            'date_principale'  => 'date',
            'montant_centimes' => 'integer',
            'paye_at'          => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $commande) => $commande->uuid ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function formule(): ?Plan
    {
        return Plan::where('cle', $this->plan)->first();
    }

    public function estGratuite(): bool
    {
        return $this->montant_centimes === 0;
    }

    /** Le mariage a-t-il déjà été créé pour cette commande ? */
    public function honoree(): bool
    {
        return $this->statut === self::HONOREE && $this->event_id !== null;
    }

    /**
     * Les réponses attendues par CreationMariage.
     *
     * @return array<string, mixed>
     */
    public function versAssistant(): array
    {
        return [
            'nom'             => $this->nom,
            'partenaire_1'    => $this->partenaire_1,
            'partenaire_2'    => $this->partenaire_2,
            'date_principale' => $this->date_principale?->format('Y-m-d'),
            'timezone'        => $this->timezone,
            'lieu_ville'      => $this->lieu_ville,
            'lieu_pays'       => 'France',
            'parties'         => $this->parties ?: ['ceremonie', 'vin-honneur', 'diner'],
            'type_ceremonie'  => $this->type_ceremonie,
            'plan'            => $this->plan,
            // Le thème choisi à la commande ; repli sur le premier actif si
            // la commande date d'avant l'ajout du sélecteur.
            'theme_id'        => $this->theme_id
                ?: Theme::publics()->value('id'),
            'email'           => $this->email,
        ];
    }
}
