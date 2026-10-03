<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les photos de la galerie passent de 5 à 10 Mo : celles des téléphones
 * récents dépassent souvent 5 Mo, et l'invité refusé ne réessaie pas.
 *
 * Seuls les mariages restés sur l'ancien défaut sont relevés. Un poids
 * choisi par le couple, plus haut ou plus bas, est conservé.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->remplacer(5, 10);
    }

    public function down(): void
    {
        $this->remplacer(10, 5);
    }

    private function remplacer(int $avant, int $apres): void
    {
        $mur = DB::table('modules')->where('cle', 'mur')->value('id');

        if (! $mur) {
            return;
        }

        DB::table('event_module')->where('module_id', $mur)->get()->each(function ($ligne) use ($avant, $apres) {
            $config = json_decode((string) $ligne->config, true) ?: [];

            if ((int) ($config['taille_max_mo'] ?? 0) !== $avant) {
                return;
            }

            $config['taille_max_mo'] = $apres;

            DB::table('event_module')
                ->where('event_id', $ligne->event_id)
                ->where('module_id', $ligne->module_id)
                ->update(['config' => json_encode($config)]);
        });
    }
};
