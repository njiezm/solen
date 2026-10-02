<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le thème choisi à la commande.
 *
 * Il manquait : faute de le recueillir, tous les mariages achetés héritaient
 * du premier thème actif, quel que soit le goût de l'acheteur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->foreignId('theme_id')->nullable()->after('type_ceremonie')
                ->constrained('themes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('theme_id');
        });
    }
};
