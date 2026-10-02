<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un scan de QR code, anonyme : la date, le type d'appareil et une IP
 * tronquée qui ne permet pas d'identifier quelqu'un. Effacé après 90 jours.
 */
class QrCodeScan extends Model
{
    use Prunable;

    public const RETENTION_JOURS = 90;

    public $timestamps = false;

    protected $fillable = ['qr_code_id', 'ip_address', 'user_agent', 'appareil', 'scanned_at'];

    protected $casts = ['scanned_at' => 'datetime'];

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }

    /** Purge quotidienne : `php artisan model:prune`, planifiée. */
    public function prunable(): Builder
    {
        return static::where('scanned_at', '<', now()->subDays(self::RETENTION_JOURS));
    }

    /** 192.168.1.42 devient 192.168.1.0 ; une IPv6 garde ses 48 premiers bits. */
    public static function tronquer(?string $ip): ?string
    {
        if (! $ip) {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $blocs = explode(':', inet_ntop(inet_pton($ip)));

            return implode(':', array_slice(array_pad($blocs, 3, '0'), 0, 3)) . '::';
        }

        return null;
    }

    public static function appareil(string $userAgent): string
    {
        return match (true) {
            (bool) preg_match('/iPad|Tablet|Android(?!.*Mobile)/i', $userAgent) => 'tablette',
            (bool) preg_match('/Mobile|iPhone|Android/i', $userAgent)          => 'mobile',
            $userAgent === ''                                                    => 'inconnu',
            default                                                              => 'ordinateur',
        };
    }
}
