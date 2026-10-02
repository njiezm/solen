<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Le mariage est créé : voici l'adresse du site et les identifiants.
 *
 * C'est l'e-mail le plus important de la plateforme. Le mot de passe n'est
 * affiché qu'une fois à l'écran ; sans cet envoi, un client qui ferme son
 * onglet perd l'accès à ce qu'il vient d'acheter.
 *
 * Il n'est PAS mis en file d'attente : une confirmation d'achat doit partir
 * immédiatement, même si le worker de queue est arrêté.
 */
class CommandeConfirmee extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Commande $commande,
        public ?string $motDePasse = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre mariage est en ligne — ' . $this->commande->nom,
        );
    }

    public function content(): Content
    {
        $event = $this->commande->event;

        return new Content(
            markdown: null,
            view: 'emails.commande-confirmee',
            with: [
                'commande'   => $this->commande,
                'event'      => $event,
                'motDePasse' => $this->motDePasse,
                'urlSite'    => route('landing', $event->slug),
                'urlEspace'  => route('espace.index', $event->slug),
                'urlOubli'   => route('mot-de-passe.demande'),
            ],
        );
    }
}
