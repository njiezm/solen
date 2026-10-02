<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accès organisateurs.
 *
 * Deux niveaux : le rôle global (super_admin pour l'équipe Solen,
 * organisateur pour les clients) et le rôle par événement (proprietaire
 * pour les mariés, collaborateur pour un témoin ou un wedding planner).
 *
 * Les invités ne sont PAS des users : ils restent dans `participants` et
 * s'identifieront par lien magique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('organisateur')->after('email'); // super_admin|organisateur
            $table->string('telephone')->nullable()->after('role');
            $table->timestamp('derniere_connexion_at')->nullable();
        });

        Schema::create('event_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('proprietaire'); // proprietaire|collaborateur
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'telephone', 'derniere_connexion_at']);
        });
    }
};
