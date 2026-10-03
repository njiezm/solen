<?php

namespace Database\Seeders;

use App\Models\Event;
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
 * thème et le cadre sont remis à jour à chaque passage ; les modules et
 * réglages de départ ne sont posés qu'à la création, pour ne jamais
 * écraser ce que le couple a choisi depuis son espace.
 */
class MariageSoizicNylanSeeder extends Seeder
{
    private const SLUG = 'soizic-nylan';

    private const DOSSIER = 'images/mariagenylan';

    /** Leur liste, plus le compte à rebours et les QR codes. */
    private const MODULES = [
        'site', 'compte', 'qr',
        'histoire', 'pratique', 'menu', 'hommage',
        'livret', 'photobooth', 'mur', 'cagnotte', 'jeux',
    ];

    /** Le « Qui de nous 2 » se fera en animation, pas sur le site. */
    private const JEUX = ['quiz', 'puzzle', 'mots_croises', 'memory'];

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

        $event->update(['theme_id' => $theme->id]);

        app(CurrentEvent::class)->pretend($event, function () use ($event, $nouveau) {
            // Le cadre est notre travail : toujours remis à jour.
            $this->poserCadre($event);

            // Le reste n'est qu'un point de départ : une fois le couple
            // dans son espace, ses choix priment.
            if ($nouveau) {
                $this->choisirModules($event);
                $this->reglerPhotobooth($event);
                $this->reglerJeux($event);
                $this->reglerCagnotte($event);
            }
        });

        $this->command?->info("Mariage « {$event->nom} » prêt : {$event->url()}");
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
            // À confirmer avec le couple : le lieu n'est pas encore connu.
            'timezone'        => 'Europe/Paris',
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
