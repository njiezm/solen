<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Rappel amical aux couples dont le site dort en brouillon.
 *
 * Le ton compte : ce n'est pas une injonction, c'est un coup de main. On
 * montre où ils en sont et les deux ou trois choses qui restent.
 */
class RelanceBrouillon extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string, mixed> $avancement */
    public function __construct(
        public Event $event,
        public array $avancement,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre site de mariage vous attend',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.relance-brouillon',
            with: [
                'event'      => $this->event,
                'avancement' => $this->avancement,
                // On ne liste que ce qui manque, jamais la liste complète.
                'restantes'  => collect($this->avancement['etapes'])
                    ->where('fait', false)
                    ->take(3),
                'urlEspace'  => route('espace.index', $this->event->slug),
            ],
        );
    }
}
