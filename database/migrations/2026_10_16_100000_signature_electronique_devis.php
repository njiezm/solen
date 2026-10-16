<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Signature électronique des devis.
 *
 * Le client signe depuis un lien secret, après avoir reçu un code à usage
 * unique par e-mail : c'est ce code qui rattache la signature à la maîtrise
 * d'une boîte mail. La preuve (signataire, adresse, horodatage, IP,
 * empreinte du contenu signé) est conservée avec le document.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents_commerciaux', function (Blueprint $table) {
            $table->string('signature_token', 64)->nullable()->unique()->after('statut');
            $table->timestamp('signe_le')->nullable()->after('signature_token');
            $table->json('signature')->nullable()->after('signe_le');       // preuve, figée à la signature
            $table->json('signature_code')->nullable()->after('signature'); // code en cours : empreinte, échéance, essais
        });
    }

    public function down(): void
    {
        Schema::table('documents_commerciaux', function (Blueprint $table) {
            $table->dropUnique(['signature_token']);
            $table->dropColumn(['signature_token', 'signe_le', 'signature', 'signature_code']);
        });
    }
};
