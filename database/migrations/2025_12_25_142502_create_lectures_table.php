<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lectures', function (Blueprint $table) {
    $table->id();
    $table->string('titre'); // ex: "Première Lecture"
    $table->text('reference'); // ex: "GENÈSE 1,26-2831a"
    $table->longText('contenu'); // Le texte complet de la lecture
    $table->string('auteur')->nullable(); // ex: "Lecture du Livre de la Genèse"
    $table->integer('ordre')->default(0);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lectures');
    }
};
