<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

/**
 * Charge les thèmes depuis config/solen.php.
 *
 * Idempotent : relancer le seeder met à jour les thèmes existants au lieu
 * de les dupliquer, ce qui permet d'ajuster une couleur dans la config puis
 * de rejouer `db:seed --class=ThemeSeeder` en production.
 */
class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('solen.themes') as $ordre => $theme) {
            Theme::updateOrCreate(
                ['cle' => $theme['key']],
                [
                    'nom'          => $theme['nom'],
                    'ink'          => $theme['ink'],
                    'surface'      => $theme['surface'],
                    'fond'         => $theme['fond']         ?? null,
                    'accent'       => $theme['accent'],
                    'secondaire'   => $theme['secondaire']   ?? null,
                    'forme'        => $theme['forme']        ?? 'doux',
                    'caractere'    => $theme['caractere']    ?? 'classique',
                    'densite'      => $theme['densite']      ?? 'confortable',
                    'traitement'   => $theme['traitement']   ?? 'naturel',
                    'font_display' => $theme['font_display'] ?? "'Fraunces', Georgia, serif",
                    'font_body'    => $theme['font_body']    ?? "'Inter', sans-serif",
                    'actif'        => true,
                    'ordre'        => $ordre,
                ]
            );
        }

        $this->command?->info(count(config('solen.themes')) . ' thèmes chargés.');
    }
}
