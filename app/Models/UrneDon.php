<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;

class UrneDon extends Model
{
    use BelongsToEvent;

    protected $fillable = [
        'event_id',
        'participant_id',
        'montant',
        'devise',
        'message',
        'moyen_paiement',
        'statut',
        'transaction_id',
        'stripe_payment_intent',
        'commission_centimes',
        'paye_at',
    ];

    protected function casts(): array
    {
        return [
            'montant'             => 'decimal:2',
            'commission_centimes' => 'integer',
            'paye_at'             => 'datetime',
        ];
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }
}