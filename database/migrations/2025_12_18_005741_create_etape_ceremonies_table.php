<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etape_ceremonies', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('icone')->nullable(); // Classe Font Awesome
            $table->integer('ordre')->default(0); // Pour l'ordre d'affichage
            $table->boolean('en_cours')->default(false); // Pour marquer l'étape en cours
            $table->boolean('termine')->default(false); // Pour marquer l'étape comme terminée
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etape_ceremonies');
    }
};