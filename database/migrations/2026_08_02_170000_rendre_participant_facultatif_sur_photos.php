<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Une photo peut ne pas avoir d'auteur.
 *
 * Le photobooth accepte les clichés anonymes — c'est même la règle en mode
 * borne, où personne ne saisit son nom devant la file d'attente. La colonne
 * était pourtant obligatoire : tout envoi sans prénom échouait.
 *
 * Découvert par la suite de tests, jamais en production.
 */
return new class extends Migration
{
    public function up(): void
    {
        // La clé étrangère doit tomber avant qu'on touche à la colonne.
        Schema::table('photos', function (Blueprint $table) {
            $table->dropForeign(['participant_id']);
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE photos ALTER COLUMN participant_id DROP NOT NULL');
        } else {
            Schema::table('photos', function (Blueprint $table) {
                $table->unsignedBigInteger('participant_id')->nullable()->change();
            });
        }

        Schema::table('photos', function (Blueprint $table) {
            $table->foreign('participant_id')->references('id')->on('participants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Les photos anonymes doivent partir avant de rendre la colonne
        // obligatoire, sinon la contrainte est irrecevable.
        DB::table('photos')->whereNull('participant_id')->delete();

        Schema::table('photos', function (Blueprint $table) {
            $table->dropForeign(['participant_id']);
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE photos ALTER COLUMN participant_id SET NOT NULL');
        } else {
            Schema::table('photos', function (Blueprint $table) {
                $table->unsignedBigInteger('participant_id')->nullable(false)->change();
            });
        }

        Schema::table('photos', function (Blueprint $table) {
            $table->foreign('participant_id')->references('id')->on('participants')->cascadeOnDelete();
        });
    }
};
