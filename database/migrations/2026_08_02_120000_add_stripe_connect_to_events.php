<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stripe Connect.
 *
 * La cagnotte doit arriver sur le compte des mariés, pas sur celui de la
 * plateforme : encaisser pour le compte d'autrui sans agrément est une
 * activité réglementée. Chaque mariage possède donc son propre compte
 * Stripe Express, et Solen prélève au passage une commission explicite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('stripe_account_id')->nullable()->unique();
            $table->timestamp('stripe_valide_at')->nullable();     // KYC terminé
            $table->boolean('stripe_paiements_actifs')->default(false);
            $table->boolean('stripe_virements_actifs')->default(false);

            // En points de base (100 = 1 %). Zéro par défaut : l'absence de
            // commission sur la cagnotte est un argument commercial.
            $table->unsignedSmallInteger('commission_bps')->default(0);
        });

        Schema::table('urne_dons', function (Blueprint $table) {
            $table->string('devise', 3)->default('EUR');
            $table->unsignedInteger('commission_centimes')->default(0);
            $table->string('stripe_payment_intent')->nullable();
            $table->timestamp('paye_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('urne_dons', function (Blueprint $table) {
            $table->dropColumn(['devise', 'commission_centimes', 'stripe_payment_intent', 'paye_at']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_account_id', 'stripe_valide_at',
                'stripe_paiements_actifs', 'stripe_virements_actifs', 'commission_bps',
            ]);
        });
    }
};
