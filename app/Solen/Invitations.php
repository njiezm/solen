<?php

namespace App\Solen;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\URL;

/**
 * Le lien qui donne accès à un espace : remise des clés aux mariés,
 * invitation d'un témoin ou d'une wedding planner.
 *
 * Signé, valable sept jours, et à usage unique : il embarque une empreinte
 * du mot de passe actuel. Dès que l'invité a choisi le sien, l'empreinte
 * change et le lien ne fonctionne plus, même s'il a circulé sur WhatsApp.
 */
final class Invitations
{
    public const VALIDITE_JOURS = 7;

    public static function lien(User $user, Event $event): string
    {
        return URL::temporarySignedRoute('invitation', now()->addDays(self::VALIDITE_JOURS), [
            'user'  => $user->id,
            'slug'  => $event->slug,
            'cle'   => self::empreinte($user),
        ]);
    }

    public static function empreinte(User $user): string
    {
        return substr(hash_hmac('sha256', (string) $user->password, (string) config('app.key')), 0, 16);
    }

    public static function valide(User $user, string $cle): bool
    {
        return hash_equals(self::empreinte($user), $cle);
    }
}
