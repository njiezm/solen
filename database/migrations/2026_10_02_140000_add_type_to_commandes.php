<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une commande crée un mariage, ou fait monter de formule un mariage
 * existant : le couple ne paie alors que la différence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->string('type', 12)->default('creation');   // creation | montee
            $table->string('plan_origine')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn(['type', 'plan_origine']);
        });
    }
};
