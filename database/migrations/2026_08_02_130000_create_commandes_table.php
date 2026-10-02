<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les commandes de formules.
 *
 * Une commande précède le mariage : on recueille les informations, on
 * encaisse, et le mariage n'est créé qu'une fois le paiement confirmé.
 * Cette table conserve la trace de la vente, y compris quand l'acheteur
 * abandonne en cours de route.
 *
 * À ne pas confondre avec urne_dons, qui concerne l'argent des invités et
 * transite par Stripe Connect vers le compte des mariés. Ici, c'est le
 * chiffre d'affaires de Solen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Ce que l'acheteur a demandé
            $table->string('plan');
            $table->string('nom');
            $table->string('partenaire_1')->nullable();
            $table->string('partenaire_2')->nullable();
            $table->string('email');
            $table->date('date_principale')->nullable();
            $table->string('timezone')->default('Europe/Paris');
            $table->string('lieu_ville')->nullable();
            $table->string('type_ceremonie')->default('civil');
            $table->json('parties')->nullable();

            // Où en est la vente
            $table->string('statut')->default('en_attente'); // en_attente|payee|honoree|annulee
            $table->unsignedInteger('montant_centimes')->default(0);
            $table->string('stripe_session_id')->nullable()->index();
            $table->string('stripe_payment_intent')->nullable();
            $table->timestamp('paye_at')->nullable();

            // Le mariage créé au bout du compte
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            $table->index(['statut', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
