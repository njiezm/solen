<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Supprime la contrainte CHECK laissée par l'ancien enum.
 *
 * Là encore, propre à PostgreSQL : SQLite ne connaît pas
 * `ALTER TABLE … DROP CONSTRAINT`, et n'a de toute façon rien à supprimer.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE qr_codes DROP CONSTRAINT IF EXISTS qr_codes_source_check');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE qr_codes ADD CONSTRAINT qr_codes_source_check
                       CHECK (source IN ('tableau', 'whatsapp', 'entree', 'eglise', 'autre'))");
    }
};
