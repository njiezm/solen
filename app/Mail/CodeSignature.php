<?php

namespace App\Mail;

use App\Models\DocumentCommercial;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Le code à six chiffres qui permet de signer un devis en ligne. */
class CodeSignature extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DocumentCommercial $devis,
        public string $code,
        public int $minutes,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Votre code de signature : {$this->code}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.code-signature');
    }
}
