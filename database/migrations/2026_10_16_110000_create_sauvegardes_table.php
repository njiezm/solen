<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La corbeille des remises à zéro.
 *
 * Vider les jeux, la galerie ou le photobooth d'un mariage met d'abord tout
 * de côté : les lignes effacées, telles quelles, et les fichiers déplacés
 * hors du disque public. Un clic les restaure, pendant trente jours.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sauvegardes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('quoi', 20);              // jeux | galerie | photobooth
            $table->string('libelle');
            $table->json('lignes');                  // table => lignes effacées, dans l'ordre de restauration
            $table->json('fichiers')->nullable();    // chemins d'origine sur le disque public
            $table->unsignedInteger('nombre')->default(0);
            $table->timestamp('restauree_le')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sauvegardes');
    }
};
