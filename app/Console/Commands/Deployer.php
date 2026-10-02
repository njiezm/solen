<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Déploiement en une commande.
 *
 * Reprend pas à pas la procédure de DEPLOIEMENT.md, dans le bon ordre, avec
 * les garde-fous : refus de rejouer les seeders de contenu sur une base déjà
 * peuplée, mise en maintenance pendant les migrations, caches reconstruits
 * à la fin seulement.
 */
class Deployer extends Command
{
    protected $signature = 'solen:deployer
                            {--sans-maintenance : Ne pas couper le site pendant les migrations}
                            {--forcer-contenu   : Rejouer les seeders de contenu (DESTRUCTIF)}
                            {--simulation       : Montrer ce qui serait fait, sans rien exécuter}';

    protected $description = 'Déploie Solen : migrations, catalogue, caches.';

    private bool $simulation = false;

    public function handle(): int
    {
        $this->simulation = (bool) $this->option('simulation');

        $this->titre($this->simulation ? 'Simulation de déploiement' : 'Déploiement de Solen');

        if (! $this->verifierPrerequis()) {
            return self::FAILURE;
        }

        $maintenance = ! $this->option('sans-maintenance') && ! $this->simulation;

        if ($maintenance) {
            $this->etape('Mise en maintenance');
            Artisan::call('down', ['--render' => 'errors::503', '--retry' => 60]);
        }

        try {
            $this->etape('Purge des caches');
            $this->executer('optimize:clear');

            $this->etape('Migrations');
            $this->executer('migrate', ['--force' => true]);

            $this->etape('Catalogue (thèmes, modules, formules)');
            $this->executer('db:seed', ['--class' => 'ThemeSeeder', '--force' => true]);
            $this->executer('db:seed', ['--class' => 'CatalogueSeeder', '--force' => true]);

            $this->seedContenuInitial();

            $this->etape('Lien de stockage');
            $this->executer('storage:link');

            $this->etape('Reconstruction des caches');
            $this->executer('config:cache');
            $this->executer('route:cache');
            $this->executer('view:cache');
        } finally {
            if ($maintenance) {
                $this->etape('Remise en ligne');
                Artisan::call('up');
            }
        }

        $this->nouvelleLigne();
        $this->verifierApres();

        $this->nouvelleLigne();
        $this->info($this->simulation
            ? 'Simulation terminée. Relancez sans --simulation pour appliquer.'
            : 'Déploiement terminé.');

        return self::SUCCESS;
    }

    /** Rien ne doit démarrer si l'environnement n'est pas prêt. */
    private function verifierPrerequis(): bool
    {
        $manques = [];

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $manques[] = 'base de données injoignable : ' . $e->getMessage();
        }

        if (! config('app.key')) {
            $manques[] = 'APP_KEY absente (php artisan key:generate)';
        }

        if (app()->environment('production') && config('app.debug')) {
            $manques[] = 'APP_DEBUG est actif en production';
        }

        if (! config('services.stripe.secret')) {
            $this->avertir('STRIPE_SECRET absente : la cagnotte restera fermée.');
        }

        if (! config('services.stripe.webhook_secret')) {
            $this->avertir('STRIPE_WEBHOOK_SECRET absente : les paiements ne seront pas confirmés automatiquement.');
        }

        foreach ($manques as $manque) {
            $this->error('  ✗ ' . $manque);
        }

        return $manques === [];
    }

    /**
     * Les seeders de contenu ne concernent que le tout premier déploiement.
     * Les rejouer sur une base vivante dupliquerait ou écraserait des données.
     */
    private function seedContenuInitial(): void
    {
        $vide = Schema::hasTable('content_blocks') && DB::table('content_blocks')->count() === 0;

        if (! $vide && ! $this->option('forcer-contenu')) {
            $this->ligne('  · contenu initial ignoré (la base contient déjà des blocs)');

            return;
        }

        $this->etape('Contenu initial');
        $this->executer('db:seed', ['--class' => 'EvenementInitialSeeder', '--force' => true]);
        $this->executer('db:seed', ['--class' => 'ContenuInitialSeeder', '--force' => true]);
        $this->executer('db:seed', ['--class' => 'ContenuMaevaGillesSeeder', '--force' => true]);
    }

    /** Quelques vérifications de bon sens après coup. */
    private function verifierApres(): void
    {
        if ($this->simulation) {
            return;
        }

        $this->titre('Vérifications');

        $controles = [
            'thèmes chargés'    => DB::table('themes')->count() > 0,
            'modules chargés'   => DB::table('modules')->count() > 0,
            'formules chargées' => DB::table('plans')->count() > 0,
            'au moins un mariage' => DB::table('events')->count() > 0,
            'un compte organisateur' => DB::table('users')->count() > 0,
        ];

        foreach ($controles as $libelle => $vrai) {
            $this->ligne(sprintf('  %s %s', $vrai ? '✓' : '✗', $libelle));
        }

        $orphelins = DB::table('events')->whereNull('theme_id')->count();

        if ($orphelins) {
            $this->avertir("{$orphelins} mariage(s) sans thème : ils s'afficheront sans couleurs personnalisées.");
        }
    }

    private function executer(string $commande, array $arguments = []): void
    {
        if ($this->simulation) {
            $this->ligne('  · ' . $commande . ' ' . collect($arguments)
                ->map(fn ($v, $k) => is_bool($v) ? $k : "{$k}={$v}")->implode(' '));

            return;
        }

        Artisan::call($commande, $arguments);

        $sortie = trim(Artisan::output());

        foreach (array_slice(array_filter(explode("\n", $sortie)), -3) as $ligne) {
            $this->ligne('    ' . trim($ligne));
        }
    }

    private function titre(string $texte): void
    {
        $this->newLine();
        $this->line("<options=bold>{$texte}</>");
        $this->line(str_repeat('─', mb_strlen($texte)));
    }

    private function etape(string $texte): void
    {
        $this->line("<fg=cyan>▸</> {$texte}");
    }

    private function ligne(string $texte): void
    {
        $this->line("<fg=gray>{$texte}</>");
    }

    private function avertir(string $texte): void
    {
        $this->line("  <fg=yellow>!</> {$texte}");
    }

    private function nouvelleLigne(): void
    {
        $this->newLine();
    }
}
