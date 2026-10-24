<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le quiz en direct : une réponse par invité et par question, donnée
 * pendant que l'animateur a la question à l'écran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_reponses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('question');   // rang de la question dans le quiz
            $table->unsignedTinyInteger('choix');        // indice de la proposition choisie
            $table->boolean('juste');
            $table->timestamps();

            $table->unique(['event_id', 'participant_id', 'question']);
            $table->index(['event_id', 'question']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_reponses');
    }
};
