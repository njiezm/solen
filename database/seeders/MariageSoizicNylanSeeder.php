<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventPart;
use App\Models\Theme;
use App\Solen\CreationMariage;
use App\Solen\CurrentEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Le mariage de Soizic & Nylan, samedi 24 octobre 2026.
 *
 * Préparé par l'équipe avant la remise des clés : thème sur mesure tiré de
 * leur visuel de photobooth, modules choisis, cadre du photobooth posé,
 * jeux cochés. Ce que nous n'avons pas encore (photos, histoire, menu,
 * questions du quiz, lien Leetchi), les mariés et leurs témoins le
 * compléteront depuis leur espace.
 *
 * Rejouable : `php artisan db:seed --class=MariageSoizicNylanSeeder`. Le
 * thème, le cadre, les modules et les jeux, arrêtés avec le couple, sont
 * remis à jour à chaque passage ; les autres réglages de départ ne sont
 * posés qu'à la création, pour ne pas écraser ce qu'il a saisi.
 */
class MariageSoizicNylanSeeder extends Seeder
{
    private const SLUG = 'soizic-nylan';

    private const DOSSIER = 'images/mariagenylan';

    /** Le Diamant, en Martinique. */
    private const FUSEAU = 'America/Martinique';

    /**
     * La liste finale arrêtée avec le couple. Le site invité (« site ») est
     * le socle, il reste toujours allumé.
     */
    private const MODULES = [
        'site', 'compte', 'histoire', 'pratique', 'photobooth', 'mur', 'cagnotte', 'jeux',
    ];

    /**
     * Les moments affichés au programme. Mairie et soirée restent en base,
     * éteints : les rallumer suffit si le couple change d'avis.
     */
    private const MOMENTS = ['ceremonie', 'vin-honneur', 'diner'];

    /** Le « Qui de nous 2 » se fera en animation, pas sur le site. */
    private const JEUX = ['quiz', 'puzzle', 'mots_croises'];

    /**
     * Le thème, relevé sur le visuel du photobooth : gris perle et blanc
     * cassé, lys en trait saumon, cœur orange de la date, prénoms en
     * calligraphie.
     */
    private const THEME = [
        'nom'          => 'Soizic & Nylan — Lys et marguerites',
        'ink'          => '#1C1A19',
        'surface'      => '#FBF8F5',
        'fond'         => '#F4F0EC',
        'accent'       => '#F56B26',
        'secondaire'   => '#E0926F',
        'forme'        => 'doux',
        'caractere'    => 'epure',
        'densite'      => 'aeree',
        'traitement'   => 'chaud',
        'font_display' => "'Great Vibes', cursive",
        'font_body'    => "'Montserrat', sans-serif",
    ];

    private const DECORS = [
        // Le lys extrait du visuel, en attendant des fleurs plus complètes.
        ['image' => self::DOSSIER . '/deco-lys.png',         'emplacement' => 'haut-gauche', 'largeur' => 230, 'opacite' => .55],
        ['image' => self::DOSSIER . '/deco-lys.png',         'emplacement' => 'bas-droite',  'largeur' => 230, 'opacite' => .55, 'miroir' => true],
        ['image' => self::DOSSIER . '/deco-marguerite.svg',  'emplacement' => 'haut-droite', 'largeur' => 130, 'debord' => 28],
        ['image' => self::DOSSIER . '/deco-marguerite.svg',  'emplacement' => 'bas-gauche',  'largeur' => 90,  'debord' => 30],
    ];

    public function run(): void
    {
        $event   = Event::where('slug', self::SLUG)->first();
        $nouveau = ! $event;
        $event ??= $this->creer();

        $theme = Theme::updateOrCreate(
            ['cle' => self::SLUG],
            self::THEME + ['decors' => self::DECORS, 'event_id' => $event->id, 'actif' => true, 'ordre' => 0]
        );

        $event->update(['theme_id' => $theme->id, 'lieu_ville' => 'Le Diamant', 'lieu_pays' => 'Martinique']);
        $this->passerAuFuseau($event);

        app(CurrentEvent::class)->pretend($event, function () use ($event, $nouveau) {
            // Le cadre, les modules et les jeux sont arrêtés avec le couple :
            // toujours réappliqués.
            $this->poserCadre($event);
            $this->choisirModules($event);
            $this->choisirMoments();
            $this->reglerJeux($event);
            // Photos publiées dès l'envoi : le mur vit pendant la fête.
            $event->ecrireReglages('mur', ['moderation' => 'aucune']);
            // Le programme est regroupé dans les infos pratiques.
            $event->ecrireReglages('pratique', ['programme' => true]);

            // Le reste n'est qu'un point de départ : une fois le couple
            // dans son espace, ses choix priment.
            if ($nouveau) {
                $this->reglerPhotobooth($event);
                $this->reglerCagnotte($event);
            }
        });

        $this->command?->info("Mariage « {$event->nom} » prêt : {$event->url()}");
    }

    /**
     * Le mariage a d'abord été créé à l'heure de Paris. Les horaires sont
     * stockés en UTC : changer le fuseau seul décalerait tout de six heures.
     * On garde donc l'heure affichée (15 h reste 15 h, mais au Diamant).
     */
    private function passerAuFuseau(Event $event): void
    {
        $ancien = $event->timezone;

        if ($ancien === self::FUSEAU) {
            return;
        }

        app(CurrentEvent::class)->pretend($event, function () use ($ancien) {
            foreach (EventPart::all() as $partie) {
                foreach (['accueil_at', 'debut_at', 'fin_at'] as $champ) {
                    if ($partie->{$champ}) {
                        $local = $partie->{$champ}->copy()->setTimezone($ancien)->format('Y-m-d H:i:s');
                        $partie->{$champ} = \Illuminate\Support\Carbon::parse($local, self::FUSEAU)->utc();
                    }
                }
                $partie->save();
            }
        });

        $event->update(['timezone' => self::FUSEAU]);
        $this->command?->info("  Fuseau : {$ancien} → " . self::FUSEAU . ', horaires conservés.');
    }

    /** Création initiale, par le même chemin que l'assistant de la console. */
    private function creer(): Event
    {
        $resultat = app(CreationMariage::class)->executer([
            'nom'             => 'Soizic & Nylan',
            'slug'            => self::SLUG,
            'partenaire_1'    => 'Soizic',
            'partenaire_2'    => 'Nylan',
            'date_principale' => '2026-10-24',
            'timezone'        => self::FUSEAU,
            'lieu_ville'      => 'Le Diamant',
            'lieu_pays'       => 'Martinique',
            'plan'            => 'celebration',
            'parties'         => ['mairie', 'ceremonie', 'vin-honneur', 'diner', 'soiree'],
            'type_ceremonie'  => 'catholique',
        ]);

        $this->command?->info('  Mariage créé, avec son programme type.');

        return $resultat['event'];
    }

    /**
     * Active leur liste, éteint le reste. Les réglages des modules éteints
     * sont conservés : les rallumer depuis l'espace suffit.
     */
    private function choisirModules(Event $event): void
    {
        $event->appliquerFormule();

        $modules = DB::table('event_module')
            ->join('modules', 'modules.id', '=', 'event_module.module_id')
            ->where('event_module.event_id', $event->id)
            ->pluck('modules.cle', 'modules.id');

        foreach ($modules as $id => $cle) {
            DB::table('event_module')
                ->where('event_id', $event->id)
                ->where('module_id', $id)
                ->update(['actif' => in_array($cle, self::MODULES, true)]);
        }

        $absents = array_diff(self::MODULES, $modules->all());
        if ($absents) {
            $this->command?->warn('  Modules hors formule : ' . implode(', ', $absents));
        }

        $this->command?->info('  ' . count(self::MODULES) . ' modules actifs.');
    }

    /**
     * Le visuel du couple devient le cadre : prénoms, date et photo
     * autour, la fenêtre transparente reçoit le cliché.
     */
    private function poserCadre(Event $event): void
    {
        $source = public_path(self::DOSSIER . '/cadre-photobooth.png');
        $chemin = "evenements/{$event->id}/photobooth/cadre-soizic-nylan.png";

        if (! is_file($source)) {
            $this->command?->warn('  Cadre introuvable : ' . $source);

            return;
        }

        Storage::disk('public')->put($chemin, file_get_contents($source));
        $event->ecrireReglages('photobooth', ['cadre' => $chemin]);

        $this->command?->info('  Cadre du photobooth posé.');
    }

    /** Leurs prénoms et la date sont déjà sur le cadre : pas de filigrane. */
    private function reglerPhotobooth(Event $event): void
    {
        $event->ecrireReglages('photobooth', [
            'filigrane' => false,
            'consigne'  => 'Souriez, c’est pour Soizic & Nylan !',
        ]);
    }

    private function choisirMoments(): void
    {
        foreach (EventPart::all() as $partie) {
            $partie->update(['actif' => in_array($partie->cle, self::MOMENTS, true)]);
        }

        $this->command?->info('  Programme : ' . implode(', ', self::MOMENTS) . '.');
    }

    private function reglerJeux(Event $event): void
    {
        $event->ecrireReglages('jeux', ['actifs' => self::JEUX]);

        $this->command?->info('  Jeux : ' . implode(', ', self::JEUX) . '.');
    }

    /** Pas de liste de mariage : une urne, tenue sur Leetchi. Son lien viendra du couple. */
    private function reglerCagnotte(Event $event): void
    {
        $event->ecrireReglages('cagnotte', ['titre' => 'Urne']);
    }
}
