<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        Schema::table('events', function ($table) {
            $table->string('plan')->default('essentiel')->change();
        });
    }

    public function down(): void
    {
        Schema::table('events', function ($table) {
            $table->string('plan')->default('decouverte')->change();
        });
    }
};
