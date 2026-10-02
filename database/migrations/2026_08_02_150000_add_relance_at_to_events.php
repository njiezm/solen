<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trace la relance envoyée aux mariages restés en brouillon.
 *
 * Sans cette colonne, la tâche planifiée renverrait le même rappel chaque
 * nuit : le meilleur moyen d'être signalé comme indésirable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('relance_at')->nullable()->after('publie_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('relance_at');
        });
    }
};
