<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\UrneDon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Remerciement à l'invité qui vient de participer à la cagnotte.
 *
 * Stripe envoie déjà un reçu comptable ; celui-ci vient des mariés, avec
 * leurs mots et leurs couleurs. Ce n'est pas un doublon, c'est l'inverse :
 * l'un prouve le paiement, l'autre remercie.
 */
class RecuCagnotte extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UrneDon $don,
        public Event $event,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Merci — ' . $this->event->nom,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recu-cagnotte',
            with: [
                'don'     => $this->don,
                'event'   => $this->event,
                'urlSite' => route('landing', $this->event->slug),
            ],
        );
    }
}
