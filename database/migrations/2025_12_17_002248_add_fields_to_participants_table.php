<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->string('equipe')->nullable(); // Pour les jeux en équipe
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn(['email', 'telephone', 'equipe']);
        });
    }
};