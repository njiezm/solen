<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Devis, factures et avoirs émis par Solen.
 *
 * Règles comptables appliquées par le modèle, pas seulement par l'écran :
 *  - une facture émise ne se modifie ni ne se supprime : on l'annule par un
 *    avoir, qui la référence ;
 *  - la numérotation est continue, sans trou, par type et par année
 *    (F-2026-0001, A-2026-0001, D-2026-0001), attribuée à l'émission ;
 *  - l'identité du vendeur et du client est figée au moment de l'émission :
 *    changer d'adresse plus tard ne réécrit pas une facture passée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents_commerciaux', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 10);                     // devis | facture | avoir
            $table->string('numero', 20)->nullable()->unique();
            $table->string('statut', 15)->default('brouillon');
            // brouillon | emis | paye | annule | accepte | refuse | converti

            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('commande_id')->nullable()->constrained('commandes')->nullOnDelete();
            $table->foreignId('origine_id')->nullable()->constrained('documents_commerciaux')->nullOnDelete();

            $table->string('client_nom');
            $table->string('client_email')->nullable();
            $table->string('client_telephone', 40)->nullable();
            $table->text('client_adresse')->nullable();

            $table->string('objet')->nullable();
            $table->json('lignes');                          // [{designation, detail, quantite, prix_unitaire_centimes}]
            $table->integer('total_centimes')->default(0);   // négatif pour un avoir
            $table->json('vendeur')->nullable();             // identité figée à l'émission
            $table->text('notes')->nullable();               // imprimées sur le document
            $table->text('notes_internes')->nullable();      // jamais imprimées

            $table->date('emis_le')->nullable();
            $table->date('echeance_le')->nullable();
            $table->date('valide_jusqu_au')->nullable();     // devis
            $table->timestamp('paye_le')->nullable();
            $table->string('mode_paiement', 30)->nullable(); // carte | virement | especes | cheque
            $table->timestamp('envoye_email_le')->nullable();
            $table->timestamp('envoye_whatsapp_le')->nullable();

            $table->timestamps();

            $table->index(['type', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents_commerciaux');
    }
};
