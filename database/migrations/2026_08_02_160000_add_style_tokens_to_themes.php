<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Des thèmes qui font plus que changer trois couleurs.
 *
 * Deux mariages avec la même palette mais des formes et un caractère
 * différents ne se ressemblent pas. On ajoute donc l'arrondi des éléments,
 * le caractère typographique et la densité, qui suffisent à obtenir des
 * univers réellement distincts sans écrire une feuille de style par client.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            // droit | doux | rond | pilule
            $table->string('forme')->default('doux')->after('accent');

            // epure | classique | festif — pilote ornements, capitales, interlettrage
            $table->string('caractere')->default('classique')->after('forme');

            // compacte | confortable | aeree
            $table->string('densite')->default('confortable')->after('caractere');

            // Une couleur secondaire, pour les fonds de section et les nuances.
            $table->string('secondaire')->nullable()->after('accent');
        });
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn(['forme', 'caractere', 'densite', 'secondaire']);
        });
    }
};
