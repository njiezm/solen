<?php

namespace App\Console\Commands;

use App\Mail\RelanceBrouillon;
use App\Models\Event;
use App\Solen\AvancementMariage;
use App\Solen\CurrentEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Relance les couples dont le site n'est toujours pas publié.
 *
 * Une seule relance, cinq jours après la création : passé ce délai, un
 * rappel de plus devient du harcèlement et fait signaler nos e-mails comme
 * indésirables — ce qui abîme la délivrabilité pour tous les autres.
 */
class RelancerBrouillons extends Command
{
    protected $signature = 'solen:relancer-brouillons
                            {--jours=5    : Ancienneté à partir de laquelle relancer}
                            {--simulation : Lister sans envoyer}';

    protected $description = 'Relance une fois les mariages restés en brouillon.';

    public function handle(AvancementMariage $avancement, CurrentEvent $courant): int
    {
        $jours = (int) $this->option('jours');

        $mariages = Event::query()
            ->where('statut', Event::STATUT_BROUILLON)
            ->where('est_demo', false)
            ->whereNull('relance_at')
            ->where('created_at', '<=', now()->subDays($jours))
            ->whereHas('organisateurs')
            ->with('organisateurs')
            ->get();

        if ($mariages->isEmpty()) {
            $this->info('Aucun mariage à relancer.');

            return self::SUCCESS;
        }

        foreach ($mariages as $event) {
            $destinataire = $event->organisateurs->first();

            if (! $destinataire) {
                continue;
            }

            // L'avancement est cloisonné : sans contexte, il compterait
            // le contenu de tous les mariages à la fois.
            $etat = $courant->pretend($event, fn () => $avancement->pour($event));

            $this->line(sprintf(
                '  %-22s %3d%%  → %s',
                $event->slug,
                $etat['pourcentage'],
                $destinataire->email
            ));

            if ($this->option('simulation')) {
                continue;
            }

            try {
                Mail::to($destinataire->email)->send(new RelanceBrouillon($event, $etat));
                $event->forceFill(['relance_at' => now()])->save();
            } catch (\Throwable $e) {
                report($e);
                $this->error("    échec pour {$event->slug} : {$e->getMessage()}");
            }
        }

        $this->info($this->option('simulation')
            ? $mariages->count() . ' mariage(s) seraient relancés.'
            : $mariages->count() . ' relance(s) envoyée(s).');

        return self::SUCCESS;
    }
}
