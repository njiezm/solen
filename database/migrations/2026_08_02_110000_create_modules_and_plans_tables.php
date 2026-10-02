<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le moteur de modularité.
 *
 * La définition des modules et des formules vit dans config/solen.php et
 * est synchronisée en base par un seeder : le catalogue reste versionné,
 * mais l'état (quel mariage a quoi, avec quels réglages) est en base et
 * pilotable depuis la console, sans toucher au code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('cle')->unique();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('phase');                    // socle|avant|pendant|apres|pro
            $table->string('icone')->nullable();
            $table->string('statut')->default('live');  // live|build|next
            $table->boolean('vedette')->default(false);
            $table->boolean('disponible')->default(true); // coupe-circuit global
            $table->unsignedSmallInteger('ordre')->default(0);

            // Description des réglages du module. C'est ce schéma qui permet
            // à l'administration de générer le formulaire toute seule.
            $table->json('champs')->nullable();

            $table->timestamps();
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('cle')->unique();
            $table->string('nom');
            $table->string('accroche')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('prix')->default(0);   // en euros, paiement unique
            $table->json('limites')->nullable();           // {photos: 500, invites: 30, archive_mois: 6}
            $table->boolean('populaire')->default(false);
            $table->boolean('actif')->default(true);
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();
        });

        // Nom imposé par la convention Eloquent : les deux modèles dans
        // l'ordre alphabétique, au singulier.
        Schema::create('module_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->unique(['plan_id', 'module_id']);
        });

        Schema::create('event_module', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->boolean('actif')->default(true);
            $table->json('config')->nullable();            // valeurs saisies pour les `champs`
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();

            $table->unique(['event_id', 'module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_module');
        Schema::dropIfExists('module_plan');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('modules');
    }
};
