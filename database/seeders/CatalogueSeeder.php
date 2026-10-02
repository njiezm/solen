<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Module;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Synchronise le catalogue depuis les fichiers de configuration.
 *
 * config/solen.php décrit les modules et les formules, config/solen_schema.php
 * décrit leurs réglages et leur composition. Ce seeder les reverse en base.
 *
 * Idempotent : le rejouer après avoir modifié la configuration met le
 * catalogue à jour en production, sans migration ni déploiement de code
 * supplémentaire.
 */
class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $this->synchroniserModules();
        $this->synchroniserFormules();
        $this->activerLesNouveautes();
    }

    /**
     * Active chez les mariages les modules qui viennent d'être livrés.
     *
     * Un module rattaché alors qu'il était encore en développement l'a été
     * en position désactivée ; le passer en « live » dans la configuration
     * ne suffit donc pas à le rendre visible. On le rattrape ici.
     *
     * Contrepartie assumée : un module que le couple aurait délibérément
     * désactivé serait réactivé. Le cas ne se présente qu'au moment précis
     * où une brique passe en production, et le couple peut le redésactiver
     * d'un clic.
     */
    /**
     * Chaque mariage reçoit les modules de sa formule qui lui manquent :
     * un module ajouté au catalogue après la vente n'était jamais rattaché
     * (le livre souvenir d'un mariage Signature répondait 404).
     *
     * Rien n'est réactivé : un module que le couple a désactivé le reste.
     * L'ancienne version les rallumait tous à chaque passage du seeder.
     */
    private function activerLesNouveautes(): void
    {
        $ajoutes = Event::withoutGlobalScopes()->get()->sum(fn (Event $event) => $event->appliquerFormule());

        if ($ajoutes) {
            $this->command?->info("  {$ajoutes} module(s) rattaché(s) aux mariages existants.");
        }
    }

    private function synchroniserModules(): void
    {
        $schemas = config('solen_schema.modules', []);

        foreach (config('solen.modules') as $ordre => $module) {
            Module::updateOrCreate(
                ['cle' => $module['key']],
                [
                    'nom'         => $module['nom'],
                    'description' => $module['desc'],
                    'phase'       => $module['phase'],
                    'icone'       => $module['icon'],
                    'statut'      => $module['statut'],
                    'vedette'     => $module['star'] ?? false,
                    'ordre'       => $ordre,
                    'champs'      => $schemas[$module['key']] ?? null,
                ]
            );
        }

        // Un module retiré de la configuration doit disparaître de la base,
        // sinon il resterait proposé aux mariages indéfiniment. Les pivots
        // event_module et module_plan tombent en cascade.
        $connus  = collect(config('solen.modules'))->pluck('key');
        $retires = Module::whereNotIn('cle', $connus)->pluck('nom', 'cle');

        if ($retires->isNotEmpty()) {
            Module::whereIn('cle', $retires->keys())->delete();
            $this->command?->warn('  Retirés du catalogue : ' . $retires->implode(', '));
        }

        $reglables = Module::whereNotNull('champs')->count();

        $this->command?->info(
            Module::count() . ' modules synchronisés, dont ' . $reglables . ' avec des réglages.'
        );
    }

    private function synchroniserFormules(): void
    {
        $composition = config('solen_schema.formule_modules', []);
        $cumul       = [];

        foreach (config('solen.plans') as $ordre => $plan) {
            $formule = Plan::updateOrCreate(
                ['cle' => $plan['key']],
                [
                    'nom'         => $plan['nom'],
                    'accroche'    => $plan['accroche'],
                    'description' => $plan['desc'],
                    'prix'        => $plan['prix'],
                    'populaire'   => $plan['populaire'] ?? false,
                    'actif'       => true,
                    'ordre'       => $ordre,
                    'limites'     => $this->limites($plan['key']),
                ]
            );

            // Cumulatif : chaque formule reprend tout ce que contient la précédente.
            $cumul = array_unique(array_merge($cumul, $composition[$plan['key']] ?? []));

            $ids = Module::whereIn('cle', $cumul)->pluck('id');
            $formule->modules()->sync($ids);

            $this->command?->info("  {$formule->nom} : {$ids->count()} modules.");
        }
    }

    /**
     * Les plafonds de chaque formule. `null` vaut « sans limite ».
     *
     * @return array<string, int|null>
     */
    private function limites(string $cle): array
    {
        return match ($cle) {
            'essentiel'   => ['invites' => null, 'photos' => 500,  'archive_mois' => 6],
            'celebration' => ['invites' => null, 'photos' => null, 'archive_mois' => 24],
            'signature'   => ['invites' => null, 'photos' => null, 'archive_mois' => null],
            default       => [],
        };
    }
}
