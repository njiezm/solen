<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chasse_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_jeu_id')->nullable()->constrained('sessions_jeu')->cascadeOnDelete(); // Correction ici
            $table->string('indice'); // Ex: "Mission 1 : L'objet le plus ancien"
            $table->string('photo_path'); // Chemin vers le fichier image
            $table->boolean('valide')->default(false); // Pour validation par l'admin
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chasse_photos');
    }
};