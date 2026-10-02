<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La modération était proposée dans les réglages du livre d'or et du mur
 * photo, sans rien derrière. Une contribution porte désormais son état :
 * publiée d'emblée, ou en attente de validation par les mariés.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['photos', 'livre_ors'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->boolean('publie')->default(true)->index();
            });
        }
    }

    public function down(): void
    {
        foreach (['photos', 'livre_ors'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('publie');
            });
        }
    }
};
