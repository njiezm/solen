<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les statistiques de scan deviennent anonymes, comme décidé : plus de
 * géolocalisation précise, plus d'empreinte d'appareil, plus d'IP
 * complète. On garde la date, le type d'appareil et une IP tronquée,
 * effacés au bout de 90 jours.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_code_scans', function (Blueprint $table) {
            $table->string('appareil', 20)->nullable();
        });

        // Anonymisation de l'existant : dernier octet (IPv4) ou fin (IPv6) effacés.
        foreach (DB::table('qr_code_scans')->whereNotNull('ip_address')->get(['id', 'ip_address', 'user_agent']) as $scan) {
            DB::table('qr_code_scans')->where('id', $scan->id)->update([
                'ip_address' => \App\Models\QrCodeScan::tronquer($scan->ip_address),
                'appareil'   => \App\Models\QrCodeScan::appareil((string) $scan->user_agent),
            ]);
        }

        Schema::table('qr_code_scans', function (Blueprint $table) {
            foreach (['location_data', 'fingerprint_id', 'screen_resolution', 'precise_location', 'location_permission_status'] as $colonne) {
                if (Schema::hasColumn('qr_code_scans', $colonne)) {
                    $table->dropColumn($colonne);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('qr_code_scans', function (Blueprint $table) {
            $table->dropColumn('appareil');
        });
    }
};
