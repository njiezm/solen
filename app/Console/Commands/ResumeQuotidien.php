<?php

namespace App\Console\Commands;

use App\Models\Commande;
use App\Models\Event;
use App\Models\LivreOr;
use App\Models\Photo;
use App\Models\UrneDon;
use Illuminate\Console\Command;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Mail;

/**
 * Le point du jour, envoyé à l'équipe Solen.
 *
 * Un seul e-mail par jour plutôt qu'une alerte par événement : on reste
 * informé sans être interrompu.
 */
class ResumeQuotidien extends Command
{
    protected $signature = 'solen:resume {--afficher : Écrire dans la console au lieu d’envoyer}';

    protected $description = 'Envoie le résumé quotidien à l’équipe Solen.';

    public function handle(): int
    {
        $hier = now()->subDay();

        $chiffres = [
            'ventes'      => Commande::where('statut', Commande::HONOREE)->where('updated_at', '>=', $hier)->count(),
            'recettes'    => Commande::where('statut', Commande::HONOREE)->where('updated_at', '>=', $hier)->sum('montant_centimes') / 100,
            'abandons'    => Commande::where('statut', Commande::ANNULEE)->where('updated_at', '>=', $hier)->count(),
            'mariages'    => Event::where('created_at', '>=', $hier)->count(),
            'publies'     => Event::where('publie_at', '>=', $hier)->count(),
            'brouillons'  => Event::where('statut', Event::STATUT_BROUILLON)->where('est_demo', false)->count(),
            // Les modèles cloisonnés doivent sortir de leur scope ici.
            'photos'      => Photo::tousEvenements()->where('created_at', '>=', $hier)->count(),
            'messages'    => LivreOr::tousEvenements()->where('created_at', '>=', $hier)->count(),
            'cagnottes'   => UrneDon::tousEvenements()->where('statut', 'payé')->where('paye_at', '>=', $hier)->sum('montant'),
        ];

        $prochains = Event::query()
            ->whereNotNull('date_principale')
            ->whereBetween('date_principale', [now()->toDateString(), now()->addDays(14)->toDateString()])
            ->orderBy('date_principale')
            ->get(['nom', 'slug', 'date_principale', 'statut']);

        if ($this->option('afficher')) {
            foreach ($chiffres as $cle => $valeur) {
                $this->line(sprintf('  %-12s %s', $cle, $valeur));
            }
            $this->line('  mariages sous 14 jours : ' . $prochains->count());

            return self::SUCCESS;
        }

        // Rien à raconter un jour creux : on n'écrit pas pour ne rien dire.
        $activite = $chiffres['ventes'] + $chiffres['abandons'] + $chiffres['mariages']
            + $chiffres['photos'] + $chiffres['messages'];

        if ($activite === 0 && $prochains->isEmpty()) {
            $this->info('Journée sans activité : aucun résumé envoyé.');

            return self::SUCCESS;
        }

        Mail::to(config('solen.brand.email'))->send(
            new class ($chiffres, $prochains) extends Mailable {
                public function __construct(
                    public array $chiffres,
                    public $prochains,
                ) {
                }

                public function envelope(): Envelope
                {
                    return new Envelope(subject: 'Solen — le point du ' . now()->translatedFormat('j F'));
                }

                public function content(): Content
                {
                    return new Content(view: 'emails.resume-quotidien', with: [
                        'chiffres'  => $this->chiffres,
                        'prochains' => $this->prochains,
                    ]);
                }
            }
        );

        $this->info('Résumé envoyé.');

        return self::SUCCESS;
    }
}
