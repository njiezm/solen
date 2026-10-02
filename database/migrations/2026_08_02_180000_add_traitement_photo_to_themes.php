<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le traitement photographique du thème.
 *
 * C'est ce qui distingue le plus deux univers de mariage — bien plus que
 * l'arrondi des cartes. Les mêmes photos en noir et blanc ou en couleurs
 * saturées racontent deux histoires différentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            // naturel | argentique | noir_blanc | chaud | doux
            $table->string('traitement')->default('naturel')->after('densite');

            /*
             * Le fond de page était déduit d'un assombrissement de l'encre.
             * La formule ne tient plus dès qu'un thème est sombre : l'encre
             * y est claire, et le fond deviendrait clair lui aussi. On le
             * déclare donc explicitement, avec repli sur l'ancien calcul.
             */
            $table->string('fond')->nullable()->after('surface');
        });
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn('traitement');
        });
    }
};
