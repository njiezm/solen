<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reponses_qui_deux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions_qui_deux')->cascadeOnDelete();
            $table->string('reponse'); // 'Gilles' ou 'Maëva'
            $table->boolean('correct')->default(false);
            $table->foreignId('session_jeu_id')->nullable()->constrained('sessions_jeu')->cascadeOnDelete(); // Correction ici
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reponses_qui_deux');
    }
};