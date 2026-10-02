<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mots_croise_id')->constrained()->cascadeOnDelete();  // Assurez-vous que c'est bien mots_croise_id
            $table->string('mot');
            $table->text('definition');
            $table->integer('position_x');
            $table->integer('position_y');
            $table->enum('direction', ['horizontal', 'vertical']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mots');
    }
};