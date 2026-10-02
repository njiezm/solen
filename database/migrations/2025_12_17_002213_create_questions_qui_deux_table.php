<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions_qui_deux', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->string('bonne_reponse'); // 'Gilles' ou 'Maëva'
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions_qui_deux');
    }
};