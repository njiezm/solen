<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les jeux deviennent automatiques.
 *
 * - Un score par invité et par jeu, pour le classement général : jusqu'ici
 *   seul « Qui de nous 2 » gardait une trace, le memory et les mots croisés
 *   affichaient un score puis l'oubliaient.
 * - Une question du « Qui de nous 2 » peut exister sans réponse : les
 *   questions types sont créées à l'achat, les mariés n'ont plus qu'à
 *   désigner qui de vous deux, d'un geste.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scores_jeu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->string('type_jeu', 30);
            $table->unsignedInteger('points')->default(0);
            $table->json('detail')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'participant_id', 'type_jeu']);
            $table->index(['event_id', 'points']);
        });

        Schema::table('questions_qui_deux', function (Blueprint $table) {
            $table->string('bonne_reponse')->nullable()->change();
            $table->unsignedSmallInteger('ordre')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores_jeu');

        Schema::table('questions_qui_deux', function (Blueprint $table) {
            $table->dropColumn('ordre');
        });
    }
};
