<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les mariés n'administrent pas tout seuls.
 *
 * - accompagnement : ce que l'équipe Solen prend en charge pour eux ;
 * - notes_internes : le carnet de l'équipe, jamais montré au couple ;
 * - logo           : leur monogramme, posé sur les QR codes et les impressions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('accompagnement')->nullable();
            $table->text('notes_internes')->nullable();
            $table->string('logo')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['accompagnement', 'notes_internes', 'logo']);
        });
    }
};
