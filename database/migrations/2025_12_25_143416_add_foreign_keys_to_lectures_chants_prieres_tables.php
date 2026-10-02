<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lectures', function (Blueprint $table) {
            // Ajoute la colonne après 'id'
            $table->unsignedBigInteger('etape_ceremonie_id')->after('id')->nullable();
            
            // Crée la contrainte de clé étrangère
            $table->foreign('etape_ceremonie_id')
                  ->references('id')
                  ->on('etape_ceremonies')
                  ->onDelete('cascade'); // Si une étape est supprimée, ses lectures le sont aussi
        });

        Schema::table('chants', function (Blueprint $table) {
            $table->unsignedBigInteger('etape_ceremonie_id')->after('id')->nullable();
            
            $table->foreign('etape_ceremonie_id')
                  ->references('id')
                  ->on('etape_ceremonies')
                  ->onDelete('cascade');
        });

        Schema::table('prieres', function (Blueprint $table) {
            $table->unsignedBigInteger('etape_ceremonie_id')->after('id')->nullable();
            
            $table->foreign('etape_ceremonie_id')
                  ->references('id')
                  ->on('etape_ceremonies')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lectures', function (Blueprint $table) {
            $table->dropForeign(['etape_ceremonie_id']);
            $table->dropColumn('etape_ceremonie_id');
        });

        Schema::table('chants', function (Blueprint $table) {
            $table->dropForeign(['etape_ceremonie_id']);
            $table->dropColumn('etape_ceremonie_id');
        });

        Schema::table('prieres', function (Blueprint $table) {
            $table->dropForeign(['etape_ceremonie_id']);
            $table->dropColumn('etape_ceremonie_id');
        });
    }
};