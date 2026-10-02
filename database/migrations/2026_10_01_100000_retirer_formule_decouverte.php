<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Plus aucune formule gratuite. Les mariages créés en « Découverte » passent
 * en « Essentiel », qui reprend tout ce que la formule offrait.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('events')->where('plan', 'decouverte')->update(['plan' => 'essentiel']);

        $id = DB::table('plans')->where('cle', 'decouverte')->value('id');

        if ($id) {
            DB::table('module_plan')->where('plan_id', $id)->delete();
            DB::table('plans')->where('id', $id)->delete();
        }

        // SQL direct plutôt que ->change() : Laravel y ajoute « drop identity
        // if exists », inconnu des PostgreSQL antérieurs à la version 10.
        DB::statement("ALTER TABLE events ALTER COLUMN plan SET DEFAULT 'essentiel'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE events ALTER COLUMN plan SET DEFAULT 'decouverte'");
    }
};
