<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les décors d'un thème sur mesure : fleurs, feuillages, motifs tirés du
 * faire-part du couple, posés en marge des pages du site invité.
 *
 * Une liste d'images et de positions plutôt qu'une feuille de style : le
 * thème reste une donnée, et le gabarit décide seul de la mise en page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->json('decors')->nullable()->after('traitement');
        });
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn('decors');
        });
    }
};
