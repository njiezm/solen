<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_qr_codes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique(); // Identifiant unique pour l'URL de suivi
            $table->string('name'); // Nom ex: "Table 1", "Lien WhatsApp"
            $table->enum('source', ['tableau', 'whatsapp', 'entree', 'eglise', 'autre']);
            $table->text('destination_url'); // Lien final vers lequel l'utilisateur est redirigé
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};