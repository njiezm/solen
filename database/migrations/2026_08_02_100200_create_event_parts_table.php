<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les parties d'un mariage : mairie, cérémonie, vin d'honneur, dîner,
 * soirée, brunch du lendemain — et tout ce qu'un couple voudra ajouter.
 *
 * Chaque partie a son lieu, son horaire et son propre déroulé (les étapes
 * de etape_ceremonies s'y rattachent). C'est ce qui permet à un mariage sur
 * deux jours, ou à une mairie séparée de l'église, de fonctionner sans
 * traitement particulier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();

            $table->string('cle');   // mairie|ceremonie|vin-honneur|diner|soiree|brunch|autre
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('icone')->nullable();

            // Type de cérémonie : pilote le gabarit de livret proposé.
            // civil|laique|catholique|evangelique|autre
            $table->string('type_ceremonie')->nullable();

            // Lieu
            $table->string('lieu_nom')->nullable();
            $table->string('lieu_adresse')->nullable();
            $table->decimal('lieu_lat', 10, 7)->nullable();
            $table->decimal('lieu_lng', 10, 7)->nullable();

            // Horaires (stockés en UTC, affichés dans le fuseau de l'événement)
            $table->timestamp('accueil_at')->nullable();
            $table->timestamp('debut_at')->nullable();
            $table->timestamp('fin_at')->nullable();

            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['event_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_parts');
    }
};
