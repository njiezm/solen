<?php

namespace App\Solen;

/**
 * L'identité légale du vendeur, telle qu'elle doit figurer sur les
 * factures et les documents légaux.
 */
final class Entreprise
{
    /** Les mentions sans lesquelles une facture française n'est pas valable. */
    public const REQUIS = [
        'raison_sociale' => 'Nom de l’entreprise',
        'dirigeant'      => 'Nom et prénom de l’entrepreneur',
        'adresse'        => 'Adresse de l’établissement',
        'siret'          => 'Numéro SIRET',
        'email'          => 'Adresse e-mail',
    ];

    public static function get(string $cle, mixed $defaut = null): mixed
    {
        return config("solen.entreprise.{$cle}") ?: $defaut;
    }

    /** @return array<string, mixed> */
    public static function tout(): array
    {
        return (array) config('solen.entreprise');
    }

    /** @return array<string, string> Les mentions obligatoires encore vides. */
    public static function manquants(): array
    {
        return array_filter(self::REQUIS, fn ($libelle, $cle) => blank(self::get($cle)), ARRAY_FILTER_USE_BOTH);
    }

    public static function complete(): bool
    {
        return self::manquants() === [];
    }

    /** « Jean Dupont — EI », la désignation exigée d'un entrepreneur individuel. */
    public static function designation(): string
    {
        return trim(self::get('dirigeant', '') . ' — ' . self::get('raison_sociale', '') . ' (EI)', ' —');
    }
}
