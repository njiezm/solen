<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Envoie son cliché à l'invité qui a laissé son adresse au photobooth.
 *
 * La photo est jointe au message plutôt que liée : un invité qui reçoit
 * l'e-mail six mois plus tard aura toujours son image, même si le site du
 * mariage a été archivé entre-temps.
 */
class PhotoPhotobooth extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public string $chemin,
        public ?string $prenom = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre photo — ' . $this->event->nom,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.photo-photobooth',
            with: [
                'event'   => $this->event,
                'prenom'  => $this->prenom,
                'urlSite' => route('galerie.index', $this->event->slug),
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('public', $this->chemin)
                ->as('photo-' . $this->event->slug . '.jpg')
                ->withMime('image/jpeg'),
        ];
    }
}
