<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_advanced_data_to_qr_code_scans_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_code_scans', function (Blueprint $table) {
            $table->string('fingerprint_id')->nullable()->after('user_agent'); // Empreinte numérique unique
            $table->string('screen_resolution')->nullable()->after('fingerprint_id'); // ex: 1920x1080
            $table->json('precise_location')->nullable()->after('screen_resolution'); // {lat, lon, accuracy}
            $table->string('location_permission_status')->nullable()->after('precise_location'); // granted, denied, prompt
        });
    }

    public function down(): void
    {
        Schema::table('qr_code_scans', function (Blueprint $table) {
            $table->dropColumn([
                'fingerprint_id',
                'screen_resolution',
                'precise_location',
                'location_permission_status'
            ]);
        });
    }
};