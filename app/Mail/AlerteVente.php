<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Prévient l'équipe Solen : vente conclue, ou paiement abandonné.
 *
 * Un paiement échoué vaut souvent la vente : un mot à l'acheteur dans
 * l'heure récupère une bonne partie de ces commandes.
 */
class AlerteVente extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Commande $commande,
        public bool $echec = false,
    ) {
    }

    public function envelope(): Envelope
    {
        $montant = number_format($this->commande->montant_centimes / 100, 0, ',', ' ');

        return new Envelope(
            subject: $this->echec
                ? "Paiement abandonné — {$this->commande->nom} ({$montant} €)"
                : "Vente — {$this->commande->formule()?->nom} · {$montant} € · {$this->commande->nom}",
            replyTo: [$this->commande->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alerte-vente',
            with: [
                'commande' => $this->commande,
                'echec'    => $this->echec,
                'urlFiche' => $this->commande->event
                    ? route('console.mariage', $this->commande->event->slug)
                    : route('console.index'),
            ],
        );
    }
}
