<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remises, codes promo et acomptes.
 *
 * - Un document peut porter une remise (pourcentage ou montant) : le total
 *   devient sous-total moins remise, et les deux figurent sur le PDF.
 * - Les codes promo s'appliquent en console comme à la commande en ligne.
 * - Une facture peut être une facture d'acompte, ou la facture de solde
 *   qui déduit les acomptes déjà versés (nature).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codes_promo', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('libelle')->nullable();
            $table->string('type', 12);                       // pourcentage | montant
            $table->decimal('valeur', 10, 2);
            $table->json('formules')->nullable();            // null : toutes
            $table->date('debut_le')->nullable();
            $table->date('fin_le')->nullable();
            $table->unsignedInteger('utilisations_max')->nullable();
            $table->unsignedInteger('utilisations')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::table('documents_commerciaux', function (Blueprint $table) {
            $table->string('nature', 12)->default('standard');   // standard | acompte | solde
            $table->integer('sous_total_centimes')->default(0);
            $table->string('remise_type', 12)->nullable();       // pourcentage | montant
            $table->decimal('remise_valeur', 10, 2)->nullable();
            $table->string('remise_libelle')->nullable();
            $table->integer('remise_centimes')->default(0);
            $table->foreignId('code_promo_id')->nullable()->constrained('codes_promo')->nullOnDelete();
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->foreignId('code_promo_id')->nullable()->constrained('codes_promo')->nullOnDelete();
            $table->integer('remise_centimes')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('code_promo_id');
            $table->dropColumn('remise_centimes');
        });
        Schema::table('documents_commerciaux', function (Blueprint $table) {
            $table->dropConstrainedForeignId('code_promo_id');
            $table->dropColumn(['nature', 'sous_total_centimes', 'remise_type', 'remise_valeur', 'remise_libelle', 'remise_centimes']);
        });
        Schema::dropIfExists('codes_promo');
    }
};
