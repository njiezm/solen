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
        Schema::create('remerciements', function (Blueprint $table) {
    $table->id();
    $table->string('titre'); // ex: "Remerciements"
    $table->longText('contenu'); // Le texte des remerciements
    $table->string('signatures')->nullable(); // ex: "Maëva & Gilles"
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remerciements');
    }
};
