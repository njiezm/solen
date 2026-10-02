<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les blocs de contenu : ce qui remplace le contenu écrit en dur.
 *
 * Aujourd'hui la liste des hôtels vit dans PratiqueController et les défunts
 * dans PageController — impossible à changer sans éditer du PHP. Chaque bloc
 * devient une ligne de cette table, éditable depuis l'espace client, avec un
 * type qui décrit ses champs et permet de générer le formulaire tout seul.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();

            $table->string('page');   // histoire|pratique|hommage|menu|accueil|ceremonie
            $table->string('type');   // texte|etape_histoire|hebergement|contact|defunt|plat|faq|transport

            // Les valeurs saisies, dont la forme est décrite par le type.
            $table->json('donnees');

            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['event_id', 'page', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_blocks');
    }
};
