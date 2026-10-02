<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La liste d'invités et leurs réponses (RSVP).
 *
 * Une ligne par foyer invité : « Famille Dupont, 4 places ». Chaque foyer a
 * un code personnel : son lien de réponse, à envoyer par WhatsApp ou
 * e-mail, sans compte ni mot de passe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('code', 12)->unique();

            $table->string('nom');                         // « Famille Dupont », « Léa Martin »
            $table->string('email')->nullable();
            $table->string('telephone', 40)->nullable();
            $table->string('groupe', 60)->nullable();      // famille de la mariée, amis, collègues…
            $table->unsignedSmallInteger('places')->default(1);

            $table->string('reponse', 12)->default('attente');   // attente | oui | non
            $table->unsignedSmallInteger('presents')->default(0);
            $table->json('noms_presents')->nullable();
            $table->json('moments')->nullable();           // clés des parties auxquelles ils viennent
            $table->text('regimes')->nullable();           // allergies, régimes
            $table->text('message')->nullable();
            $table->timestamp('repondu_le')->nullable();
            $table->timestamp('relance_le')->nullable();
            $table->string('source', 12)->default('liste'); // liste | libre (réponse spontanée)

            $table->timestamps();
            $table->index(['event_id', 'reponse']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invites');
    }
};
