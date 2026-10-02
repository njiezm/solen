<?php

namespace App\Solen;

use App\Mail\DocumentsTransmis;
use App\Models\DocumentCommercial;
use App\Models\Event;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Tout ce que Solen transmet à un client : factures, devis, avoirs, kit de
 * bienvenue, planche de QR codes, documents légaux.
 *
 * Chaque document existe en PDF, se télécharge par un lien signé et
 * temporaire (pour WhatsApp, où l'on ne joint pas de fichier), et peut
 * partir par e-mail en pièce jointe.
 */
class Documents
{
    /** Durée de validité d'un lien de téléchargement partagé. */
    public const VALIDITE_JOURS = 30;

    /** Les documents transmissibles à un mariage, hors factures. */
    public const KIT = [
        'bienvenue'       => 'Kit de bienvenue',
        'qr'              => 'Planche de QR codes',
        'cgv'             => 'Conditions générales de vente',
        'confidentialite' => 'Politique de confidentialité',
        'mentions'        => 'Mentions légales',
    ];

    private function options(): array
    {
        return ['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true, 'defaultFont' => 'DejaVu Sans', 'chroot' => public_path()];
    }

    // --- Devis, factures, avoirs ----------------------------------------

    public function pdfCommercial(DocumentCommercial $doc): string
    {
        return Pdf::loadView('pdf.document-commercial', ['doc' => $doc])
            ->setPaper('a4')->setOptions($this->options())->output();
    }

    public function nomFichier(DocumentCommercial $doc): string
    {
        return Str::slug($doc->nomDuType() . ' ' . ($doc->numero ?? 'brouillon') . ' ' . $doc->client_nom) . '.pdf';
    }

    /** Lien de téléchargement sans connexion, signé et limité dans le temps. */
    public function lienCommercial(DocumentCommercial $doc): string
    {
        return URL::temporarySignedRoute('documents.commercial', now()->addDays(self::VALIDITE_JOURS), ['uuid' => $doc->uuid]);
    }

    // --- Kit d'un mariage ---------------------------------------------------

    public function pdfKit(Event $event, string $piece): string
    {
        $vue = match ($piece) {
            'bienvenue' => 'pdf.kit-bienvenue',
            'qr'        => 'pdf.kit-qr',
            'cgv', 'confidentialite', 'mentions' => 'pdf.legal',
            default     => abort(404),
        };

        return app(CurrentEvent::class)->pretend($event, fn () => Pdf::loadView($vue, [
            'event'   => $event,
            'piece'   => $piece,
            'titre'   => self::KIT[$piece],
            'qrs'     => $piece === 'qr' ? $this->qrsDuMariage($event) : collect(),
        ])->setPaper('a4')->setOptions($this->options())->output());
    }

    public function lienKit(Event $event, string $piece): string
    {
        return URL::temporarySignedRoute('documents.kit', now()->addDays(self::VALIDITE_JOURS), ['slug' => $event->slug, 'piece' => $piece]);
    }

    /** Les QR codes du mariage, dessinés en SVG pour DomPDF. */
    private function qrsDuMariage(Event $event)
    {
        return \App\Models\QrCode::pourEvenement($event)->orderBy('id')->get()->map(fn ($qr) => [
            'nom' => $qr->name,
            'svg' => 'data:image/svg+xml;base64,' . base64_encode(QrVisuel::svg(route('qr.track', $qr->uuid), $event, true, 'theme', 400)),
        ]);
    }

    // --- Envois ---------------------------------------------------------

    /**
     * Envoie des documents par e-mail, en pièces jointes.
     *
     * @param  array<string, string>  $pieces  nom de fichier => contenu PDF
     */
    public function envoyer(string $email, string $sujet, string $message, array $pieces, ?Event $event = null): void
    {
        Mail::to($email)->send(new DocumentsTransmis($sujet, $message, $pieces, $event));
    }

    /**
     * Un lien WhatsApp prêt à l'emploi : le message et les liens de
     * téléchargement, pré-remplis. Ouvert depuis la console, il lance la
     * conversation avec le client ; il ne reste qu'à appuyer sur Envoyer.
     */
    public function whatsapp(?string $telephone, string $texte): string
    {
        return 'https://wa.me/' . self::numeroInternational($telephone) . '?text=' . rawurlencode($texte);
    }

    /**
     * Un numéro saisi à la française, mis au format international.
     *
     * Les mobiles d'outre-mer ont leur propre indicatif : un 0690 de
     * Guadeloupe devient 590 690…, pas 33 690… — sans quoi WhatsApp ouvre
     * une conversation avec un numéro qui n'existe pas.
     */
    public static function numeroInternational(?string $telephone): string
    {
        $numero = preg_replace('/\D+/', '', (string) $telephone);

        if (str_starts_with($numero, '00')) {
            return substr($numero, 2);
        }

        if (! (str_starts_with($numero, '0') && strlen($numero) === 10)) {
            return $numero;   // déjà international
        }

        $indicatifs = [
            '0690' => '590', '0691' => '590',   // Guadeloupe, Saint-Martin, Saint-Barthélemy
            '0696' => '596', '0697' => '596',   // Martinique
            '0694' => '594',                    // Guyane
            '0692' => '262', '0693' => '262',   // La Réunion
            '0639' => '262',                    // Mayotte
            '0590' => '590', '0596' => '596', '0594' => '594', '0262' => '262', '0269' => '262',
        ];

        $prefixe = substr($numero, 0, 4);

        return ($indicatifs[$prefixe] ?? '33') . substr($numero, 1);
    }
}
