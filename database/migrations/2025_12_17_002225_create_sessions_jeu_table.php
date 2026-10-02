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
        Schema::create('sessions_jeu', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->enum('type_jeu', ['qui_deux', 'chasse_photo', 'mots_croises', 'memory', 'autre'])->default('qui_deux'); // Ajoutez les nouveaux types ici
            $table->boolean('actif')->default(false);
            $table->timestamp('debut')->nullable();
            $table->timestamp('fin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions_jeu');  // Correction ici : était 'jeu_qui_deuxes'
    }
};

