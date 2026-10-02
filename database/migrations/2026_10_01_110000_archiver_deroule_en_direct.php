<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Le déroulé en direct est archivé : il exigeait que quelqu'un marque les
 * étapes pendant la cérémonie. Le module reste en base, réglages compris,
 * mais n'est plus proposé ni affiché. Le livret PDF prend le relais.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('modules')->where('cle', 'deroule')->update(['disponible' => false, 'statut' => 'archive']);
    }

    public function down(): void
    {
        DB::table('modules')->where('cle', 'deroule')->update(['disponible' => true, 'statut' => 'live']);
    }
};
