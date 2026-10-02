<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le locataire de Solen : un mariage.
 *
 * Toute donnée de l'application est rattachée à un event. Le locataire est
 * résolu par sous-domaine (slug) ou par domaine personnalisé, avec repli sur
 * l'événement par défaut en développement local.
 *
 * Pas d'enum() : sous PostgreSQL, Laravel les traduit en contraintes CHECK
 * qu'il faut ensuite supprimer à la main pour ajouter une valeur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Adressage
            $table->string('slug')->unique();               // maeva-gilles → maeva-gilles.solen.app
            $table->string('domaine')->nullable()->unique(); // domaine personnalisé éventuel

            // Identité
            $table->string('nom');                           // « Maëva & Gilles »
            $table->string('partenaire_1')->nullable();
            $table->string('partenaire_2')->nullable();
            $table->string('hashtag')->nullable();

            // Temps et lieu
            $table->date('date_principale')->nullable();
            $table->string('timezone')->default('Europe/Paris'); // America/Martinique pour les Antilles
            $table->string('lieu_ville')->nullable();
            $table->string('lieu_pays')->default('France');

            // Apparence
            $table->foreignId('theme_id')->nullable()->constrained('themes')->nullOnDelete();
            $table->json('couleurs')->nullable();            // surcharge ponctuelle du thème

            // Commercial
            $table->string('plan')->default('decouverte');   // decouverte|essentiel|celebration|signature
            $table->string('statut')->default('brouillon');  // brouillon|publie|archive
            $table->boolean('est_demo')->default(false);

            // Accès invités
            $table->string('code_acces')->nullable();        // code court optionnel à l'entrée du site

            $table->timestamp('publie_at')->nullable();
            $table->timestamp('archive_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['statut', 'date_principale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
