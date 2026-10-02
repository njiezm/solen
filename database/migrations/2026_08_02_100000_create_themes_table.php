<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thèmes visuels de Solen.
 *
 * Chaque thème est un jeu de jetons de design injecté dans le site invité
 * sous forme de variables CSS. Les valeurs de référence vivent dans
 * config/solen.php et sont chargées par le ThemeSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('cle')->unique();          // ivoire, sapin, terracotta...
            $table->string('nom');
            $table->string('ink');                    // couleur de texte / fond sombre
            $table->string('surface');                // fond clair
            $table->string('accent');                 // couleur d'accent
            $table->string('font_display')->default("'Fraunces', Georgia, serif");
            $table->string('font_body')->default("'Inter', sans-serif");
            $table->boolean('actif')->default(true);
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
