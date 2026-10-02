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
        Schema::create('urne_dons', function (Blueprint $table) {
        $table->id();
        $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
        $table->decimal('montant', 8, 2);
        $table->string('moyen_paiement'); // stripe, paypal, lyfpay, virement...
        $table->string('statut')->default('en_attente'); // en_attente, payé, échoué
        $table->string('transaction_id')->nullable();
        $table->text('message')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('urne_dons');
    }
};
