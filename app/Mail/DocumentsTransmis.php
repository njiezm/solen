<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Des documents Solen envoyés à un client : facture, devis, avoir, kit de
 * bienvenue, QR codes, documents légaux. Toujours en pièces jointes PDF.
 */
class DocumentsTransmis extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string, string> $pieces nom de fichier => contenu PDF */
    public function __construct(
        public string $sujet,
        public string $texte,
        public array $pieces,
        public ?Event $event = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->sujet);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.documents-transmis', with: [
            'texte'  => $this->texte,
            'noms'   => array_keys($this->pieces),
            'event'  => $this->event,
        ]);
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return collect($this->pieces)
            ->map(fn (string $pdf, string $nom) => Attachment::fromData(fn () => $pdf, $nom)->withMime('application/pdf'))
            ->values()
            ->all();
    }
}
