<?php

namespace App\Solen;

/**
 * Calculs de contraste, selon la définition WCAG 2.1.
 *
 * Un thème déclare quelques couleurs ; tout texte posé sur l'une d'elles
 * reçoit ici une couleur garantie lisible. C'est ce qui permet au couple de
 * choisir n'importe quel accent sans jamais produire un texte invisible.
 */
final class Contraste
{
    /** Texte courant : seuil AA. */
    public const TEXTE = 4.5;

    /** Gros titres, icônes, filets : seuil AA grands caractères. */
    public const GRAPHIQUE = 3.0;

    /** @return array{0: int, 1: int, 2: int} */
    public static function rgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (! preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            return [0, 0, 0];
        }

        return array_map('hexdec', str_split($hex, 2));
    }

    public static function hex(array $rgb): string
    {
        return sprintf('#%02X%02X%02X', ...array_map(
            fn ($c) => max(0, min(255, (int) round($c))),
            $rgb
        ));
    }

    public static function estHex(?string $valeur): bool
    {
        return is_string($valeur) && preg_match('/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i', trim($valeur));
    }

    /** Luminance relative, entre 0 (noir) et 1 (blanc). */
    public static function luminance(string $hex): float
    {
        $canaux = array_map(function ($c) {
            $c /= 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, self::rgb($hex));

        return 0.2126 * $canaux[0] + 0.7152 * $canaux[1] + 0.0722 * $canaux[2];
    }

    /** Rapport de contraste, entre 1 et 21. */
    public static function rapport(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    public static function estSombre(string $hex): bool
    {
        return self::luminance($hex) < 0.18;
    }

    /** Mélange linéaire : $part de $b dans $a. */
    public static function melanger(string $a, string $b, float $part): string
    {
        $ca = self::rgb($a);
        $cb = self::rgb($b);

        return self::hex([
            $ca[0] + ($cb[0] - $ca[0]) * $part,
            $ca[1] + ($cb[1] - $ca[1]) * $part,
            $ca[2] + ($cb[2] - $ca[2]) * $part,
        ]);
    }

    /**
     * Le texte à poser sur un fond : l'encre proposée si elle suffit, sinon
     * le blanc ou le quasi-noir, selon ce qui contraste le plus.
     */
    public static function texteSur(string $fond, ?string $prefere = null, float $seuil = self::TEXTE): string
    {
        if ($prefere && self::rapport($prefere, $fond) >= $seuil) {
            return self::hex(self::rgb($prefere));
        }

        $clair  = '#FFFFFF';
        $sombre = '#14141A';

        return self::rapport($clair, $fond) >= self::rapport($sombre, $fond) ? $clair : $sombre;
    }

    /**
     * Garde la teinte d'une couleur, mais l'éclaircit ou l'assombrit juste
     * assez pour atteindre le seuil sur le fond donné. Un accent doré sur
     * fond ivoire devient un bronze lisible, au lieu d'un jaune invisible.
     */
    public static function ajuster(string $couleur, string $fond, float $seuil = self::TEXTE): string
    {
        if (self::rapport($couleur, $fond) >= $seuil) {
            return self::hex(self::rgb($couleur));
        }

        $cible = self::estSombre($fond) ? '#FFFFFF' : '#000000';

        for ($part = 0.05; $part <= 1.0; $part += 0.05) {
            $essai = self::melanger($couleur, $cible, $part);

            if (self::rapport($essai, $fond) >= $seuil) {
                return $essai;
            }
        }

        return $cible;
    }
}
