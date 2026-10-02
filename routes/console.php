<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tâches planifiées
|--------------------------------------------------------------------------
|
| Une seule ligne à poser sur le serveur pour que tout ceci tourne :
|
|   * * * * * cd /chemin/vers/solen && php artisan schedule:run >> /dev/null 2>&1
|
*/

// Le point du jour, avant l'ouverture des bureaux.
Schedule::command('solen:resume')
    ->dailyAt('07:30')
    ->timezone('Europe/Paris')
    ->onOneServer()
    ->withoutOverlapping();

// Relance des sites restés en brouillon. En milieu de matinée : un rappel
// reçu à trois heures du matin fait mauvais effet.
Schedule::command('solen:relancer-brouillons')
    ->dailyAt('10:00')
    ->timezone('Europe/Paris')
    ->onOneServer()
    ->withoutOverlapping();

// Purge des commandes abandonnées de longue date : elles n'ont plus aucune
// valeur commerciale et contiennent des données personnelles.
Schedule::call(function () {
    App\Models\Commande::where('statut', App\Models\Commande::ANNULEE)
        ->where('updated_at', '<', now()->subMonths(6))
        ->delete();
})->weeklyOn(1, '04:00')->name('purge-commandes-abandonnees')->onOneServer();

// Les scans de QR codes ne sont gardés que 90 jours (voir QrCodeScan).
Schedule::command('model:prune', ['--model' => [App\Models\QrCodeScan::class]])
    ->dailyAt('04:30')->name('purge-scans-qr')->onOneServer();
