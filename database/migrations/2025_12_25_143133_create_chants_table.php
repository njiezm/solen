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
        Schema::create('chants', function (Blueprint $table) {
    $table->id();
    $table->string('titre'); // ex: "Que tes œuvres sont belles"
    $table->longText('paroles'); // Les paroles du chant
    $table->string('auteur')->nullable(); // ex: "Auteur de la musique"
    $table->integer('ordre')->default(0);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chants');
    }
};
