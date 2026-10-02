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
       Schema::create('prieres', function (Blueprint $table) {
    $table->id();
    $table->string('titre'); // ex: "Prière des Époux"
    $table->longText('contenu'); // Le texte de la prière
    $table->string('auteur')->nullable(); // ex: "Texte des époux"
    $table->integer('ordre')->default(0);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prieres');
    }
};
