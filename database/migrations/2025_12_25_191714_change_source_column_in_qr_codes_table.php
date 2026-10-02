<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Libère la colonne `source` de son type énuméré.
 *
 * Le SQL est propre à PostgreSQL. Sous SQLite — le moteur de la suite de
 * tests — la colonne est déjà un simple varchar : il n'y a rien à faire, et
 * sans cette garde aucun test ne peut s'exécuter.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE qr_codes ALTER COLUMN source TYPE VARCHAR(255) USING source::text');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE qr_codes ALTER COLUMN source TYPE text USING source::text');
        DB::statement("ALTER TABLE qr_codes ADD CONSTRAINT qr_codes_source_check
                       CHECK (source IN ('tableau', 'whatsapp', 'entree', 'eglise', 'autre'))");
    }
};
