<?php

namespace Database\Seeders;

use App\Models\ContentBlock;
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

    /**
     * Notre histoire : le poème du couple, une strophe par étape de la
     * frise. Le texte est repris tel quel ; titres et dates sont les nôtres.
     */
    private const HISTOIRE = [
        [
            'date'  => '2018',
            'titre' => 'Un stage, un bureau',
            'texte' => "Deux mille dix-huit, un stage, un bureau, une page,
Elle travaillait, absorbée, sérieuse et sage.
Lui l’avait remarquée, sans qu’elle n’en sache rien,
Elle n’en savait qu’un nom, qui revenait de loin.",
        ],
        [
            'date'  => '2022',
            'titre' => 'Un premier message',
            'texte' => "Quatre ans ont filé, puis, sans un mot de trop,
« Je veux faire ta connaissance », écrit-il aussitôt.
Pas même un bonjour, pas le moindre détour…
Mais son cœur était pris : ce n’était pas leur tour.",
        ],
        [
            'date'  => '2023',
            'titre' => 'La route enfin ouverte',
            'texte' => "Vint deux mille vingt-trois, la route enfin ouverte ;
Sans savoir que pour lui la porte était offerte,
Il écrivit encore, et cette fois les mots
Ont trouvé leur écho, sincères, doux et beaux.",
        ],
        [
            'date'  => 'Décembre 2023',
            'titre' => 'Officiel, puis promis',
            'texte' => "Quelques mois ont passé, puis vint le premier jour,
Et décembre a rendu officiel leur amour.
L’automne suivant, un pacs, une promesse,
Puis une demande, un oui plein de tendresse.",
        ],
        [
            'date'  => 'Juin 2026',
            'titre' => 'Swann',
            'texte' => "Un mois plus tard, la vie leur glissa un secret :
Sous le cœur de Soizic, un autre cœur battait.
En juin deux mille vingt-six, Swann a ouvert les yeux,
Et le monde, d’un coup, s’est fait plus lumineux.",
        ],
        [
            'date'  => '24 octobre 2026',
            'titre' => 'Le oui',
            'texte' => "Et le vingt-quatre octobre, entourés de vous tous,
Ils se diront le oui le plus tendre et plus doux.",
        ],
    ];

    /**
     * Les photos du couple (public/images/mariagenylan/photos-web, allégées
     * à 1600 px). Rang de l'étape d'histoire => photo ; les étapes sans
     * photo de l'époque restent sans image.
     */
    private const PHOTOS_HISTOIRE = [
        3 => 'photo-02.jpg',   // 2023, le selfie
        4 => 'photo-09.jpg',   // Décembre 2023, à deux dans l'eau
        5 => 'photo-01.jpg',   // Swann
        6 => 'photo-04.jpg',   // Le oui : en tenue, sous les guirlandes
    ];

    private const PHOTOS_PUZZLE = ['photo-04.jpg', 'photo-08.jpg'];

    /** Infos pratiques confirmées. Le nombre de places du parking reste à venir. */
    private const INFOS_PRATIQUES = [
        ['icone' => 'fa-square-parking', 'titre' => 'Parking',
         'contenu' => 'Un parking sécurisé est prévu sur place.'],
    ];

    /** Le contact du jour J. */
    private const CONTACT_JOUR = ['contact_nom' => 'Alvee', 'contact_telephone' => '+596 696 35 29 85'];

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
        // Les fleurs du faire-part, fournies par le couple.
        ['image' => self::DOSSIER . '/deco-tiare.svg',           'emplacement' => 'haut-gauche', 'largeur' => 200, 'opacite' => .85, 'debord' => 22],
        ['image' => self::DOSSIER . '/deco-oiseau-paradis.svg',  'emplacement' => 'bas-droite',  'largeur' => 220, 'opacite' => .8,  'debord' => 10],
        ['image' => self::DOSSIER . '/deco-marguerite.png',      'emplacement' => 'haut-droite', 'largeur' => 130, 'debord' => 28],
        ['image' => self::DOSSIER . '/deco-marguerite.png',      'emplacement' => 'bas-gauche',  'largeur' => 95,  'debord' => 30],
        // Les prénoms et la date, à la place du texte en couverture.
        ['image' => self::DOSSIER . '/prenoms.png', 'emplacement' => 'titre'],
        ['image' => self::DOSSIER . '/date.svg',    'emplacement' => 'date'],
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
            $this->ecrireHistoire($event);
            $this->poserPhotos($event);
            $this->ecrirePratique($event);
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

    /**
     * Une étape par strophe, retrouvée par son rang. Le texte est remis à
     * jour ; une photo déposée depuis l'espace est conservée.
     */
    private function ecrireHistoire(Event $event): void
    {
        foreach (self::HISTOIRE as $i => $etape) {
            $bloc = ContentBlock::where('page', 'histoire')
                ->where('type', 'etape_histoire')
                ->where('ordre', $i + 1)
                ->first();

            // La photo du couple prime ; à défaut, la nôtre.
            $actuelle = $bloc?->donnees['image'] ?? null;
            $notre    = isset(self::PHOTOS_HISTOIRE[$i + 1]) ? $this->deposer($event, self::PHOTOS_HISTOIRE[$i + 1]) : null;
            $image    = $actuelle && ! $this->estNotre($event, $actuelle) ? $actuelle : $notre;

            $donnees = $etape + ['image' => $image];

            $bloc
                ? $bloc->update(['donnees' => $donnees])
                : ContentBlock::create(['page' => 'histoire', 'type' => 'etape_histoire', 'donnees' => $donnees, 'ordre' => $i + 1, 'actif' => true]);
        }

        $this->command?->info('  Notre histoire : ' . count(self::HISTOIRE) . ' étapes.');
    }

    /**
     * Le puzzle et l'aperçu du lien partagé. Ce que le couple a déposé
     * lui-même depuis son espace n'est jamais remplacé.
     */
    private function poserPhotos(Event $event): void
    {
        $puzzle = collect((array) $event->reglage('jeux', 'puzzle_images', []));

        if ($puzzle->every(fn ($c) => $this->estNotre($event, $c))) {
            $event->ecrireReglages('jeux', [
                'puzzle_images' => array_map(fn ($f) => $this->deposer($event, $f), self::PHOTOS_PUZZLE),
            ]);
        }

        $apercu = $event->reglage('site', 'image_partage');

        if (! $apercu || $this->estNotre($event, $apercu)) {
            $event->ecrireReglages('site', ['image_partage' => $this->deposer($event, 'apercu-lien.jpg')]);
        }

        $this->command?->info('  Photos : histoire, puzzle (' . count(self::PHOTOS_PUZZLE) . '), aperçu du lien.');
    }

    /**
     * Les blocs d'infos pratiques, retrouvés par leur titre : le texte est
     * remis à jour, les blocs ajoutés par le couple ne sont pas touchés.
     */
    private function ecrirePratique(Event $event): void
    {
        foreach (self::INFOS_PRATIQUES as $i => $info) {
            $bloc = ContentBlock::where('page', 'pratique')->where('type', 'info')->get()
                ->first(fn ($b) => ($b->donnees['titre'] ?? null) === $info['titre']);

            $bloc
                ? $bloc->update(['donnees' => $info])
                : ContentBlock::create(['page' => 'pratique', 'type' => 'info', 'donnees' => $info, 'ordre' => 50 + $i, 'actif' => true]);
        }

        $event->ecrireReglages('pratique', self::CONTACT_JOUR);

        $this->command?->info('  Infos pratiques : parking, contact du jour J.');
    }

    /** Copie une photo dans le stockage du mariage, comme un dépôt du couple. */
    private function deposer(Event $event, string $fichier): string
    {
        $chemin = "evenements/{$event->id}/contenu/{$fichier}";

        if (! Storage::disk('public')->exists($chemin)) {
            Storage::disk('public')->put($chemin, file_get_contents(public_path(self::DOSSIER . "/photos-web/{$fichier}")));
        }

        return $chemin;
    }

    /** Ce chemin vient-il de ce seeder (et non d'un dépôt du couple) ? */
    private function estNotre(Event $event, ?string $chemin): bool
    {
        return $chemin !== null && str_starts_with($chemin, "evenements/{$event->id}/contenu/");
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
