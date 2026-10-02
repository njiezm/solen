<?php

namespace App\Solen;

use App\Models\Event;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Storage;

/**
 * Dessine un QR code en SVG, aux couleurs du mariage, avec ou sans le logo
 * des mariés au centre.
 *
 * Le SVG est produit sans Imagick (le « merge » de la bibliothèque ne
 * fonctionne qu'en PNG) : le logo est inséré à la main, sur une pastille
 * blanche. La correction d'erreur au niveau H permet de masquer jusqu'à
 * 30 % du code : le logo en couvre moins d'un quart.
 */
final class QrVisuel
{
    /** Part de la largeur occupée par la pastille du logo. */
    private const PART_LOGO = 0.24;

    /**
     * @param  'theme'|'noir'  $couleur
     */
    public static function svg(string $contenu, Event $event, bool $avecLogo = true, string $couleur = 'theme', int $taille = 600): string
    {
        $encre = $couleur === 'noir'
            ? '#111111'
            : ($event->jetons()['--c-fort'] ?? '#1B1B2F');

        [$r, $g, $b] = Contraste::rgb($encre);

        $rendu = new ImageRenderer(
            new RendererStyle($taille, 2, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb($r, $g, $b))),
            new SvgImageBackEnd()
        );

        $svg = (new Writer($rendu))->writeString($contenu, 'ISO-8859-1', ErrorCorrectionLevel::H());
        $svg = preg_replace('/^<\?xml[^>]*>\s*/', '', $svg);

        $logo = $avecLogo ? self::logoEnDonnees($event) : null;

        if ($logo) {
            $cote   = $taille * self::PART_LOGO;
            $marge  = $cote * 0.12;
            $origine = ($taille - $cote) / 2;

            $svg = str_replace('</svg>', sprintf(
                '<rect x="%1$.1f" y="%1$.1f" width="%2$.1f" height="%2$.1f" rx="%3$.1f" fill="#FFFFFF"/>'
                . '<image href="%4$s" x="%5$.1f" y="%5$.1f" width="%6$.1f" height="%6$.1f" preserveAspectRatio="xMidYMid meet"/></svg>',
                $origine, $cote, $cote * 0.2, $logo, $origine + $marge, $cote - 2 * $marge
            ), $svg);
        }

        return $svg;
    }

    /** Le logo des mariés, en data URI, pour qu'un SVG téléchargé reste autonome. */
    public static function logoEnDonnees(Event $event): ?string
    {
        if (! $event->logo || ! Storage::disk('public')->exists($event->logo)) {
            return null;
        }

        $chemin = Storage::disk('public')->path($event->logo);
        $type   = mime_content_type($chemin) ?: 'image/png';

        return 'data:' . $type . ';base64,' . base64_encode((string) file_get_contents($chemin));
    }
}
