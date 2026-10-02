<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_qr_code_scans_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_code_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_code_id')->constrained()->onDelete('cascade');
            $table->string('ip_address');
            $table->text('user_agent'); // Navigateur, OS...
            $table->json('location_data')->nullable(); // Pour stocker pays, ville, etc.
            $table->timestamp('scanned_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_code_scans');
    }
};