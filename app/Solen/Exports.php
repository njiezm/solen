<?php

namespace App\Solen;

use App\Models\ContentBlock;
use App\Models\Event;
use App\Models\LivreOr;
use App\Models\Photo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Les exports d'un mariage, aux couleurs de son thème : album photo,
 * album du photobooth, livre d'or, livre souvenir, résultats des jeux, et
 * l'archive des photos d'origine.
 *
 * Écrit pour DomPDF : les photos sont réduites et calées dans leur case
 * avant d'être intégrées (une photo de téléphone brute pèse dix fois trop
 * et fait caler le moteur), la police des titres est fournie en fichier.
 */
class Exports
{
    /** Les exports proposés, dans l'ordre de la console. */
    public const TYPES = [
        'album'      => ['Album photo', 'Les photos partagées par les invités', 'fa-images'],
        'photobooth' => ['Album du photobooth', 'Les clichés du photobooth, dans leur cadre', 'fa-camera-retro'],
        'livre-or'   => ['Livre d’or', 'Tous les messages des invités', 'fa-feather-pointed'],
        'souvenir'   => ['Livre souvenir', 'Notre histoire, le livre d’or et les photos, réunis', 'fa-book'],
        'jeux'       => ['Résultats des jeux', 'Le classement général et la participation', 'fa-trophy'],
    ];

    /** Polices de titres disponibles en fichier pour DomPDF. */
    private const POLICES = [
        'Great Vibes' => 'GreatVibes-Regular.ttf',
    ];

    public function __construct(private readonly CurrentEvent $courant)
    {
    }

    // --- Contenus -------------------------------------------------------------

    /** @return array<string, int> le nombre d'éléments de chaque export */
    public function compter(Event $event): array
    {
        return $this->courant->pretend($event, fn () => [
            'album'      => Photo::publies()->where('path', 'not like', '%/photobooth/%')->count(),
            'photobooth' => Photo::publies()->duPhotobooth()->count(),
            'livre-or'   => LivreOr::publies()->count(),
            'souvenir'   => ContentBlock::where('page', 'histoire')->count() + LivreOr::publies()->count() + Photo::publies()->count(),
            'jeux'       => \App\Models\ScoreJeu::distinct()->count('participant_id'),
        ]);
    }

    /** Le PDF d'un export, en octets. */
    public function pdf(Event $event, string $type): string
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '1024M');

        $donnees = $this->courant->pretend($event, fn () => match ($type) {
            'album'      => ['photos' => $this->photos(Photo::publies()->where('path', 'not like', '%/photobooth/%'))],
            'photobooth' => ['photos' => $this->photos(Photo::publies()->duPhotobooth(), contenir: true)],
            'livre-or'   => ['messages' => LivreOr::publies()->with('participant')->oldest()->get()],
            'souvenir'   => [
                'histoire' => $this->histoire(),
                'messages' => LivreOr::publies()->with('participant')->oldest()->get(),
                'photos'   => $this->photos(Photo::publies()),
            ],
            'jeux'       => ['classement' => app(Jeux::class)->classement(PHP_INT_MAX), 'parJeu' => $this->participationJeux($event)],
        });

        return Pdf::loadView('pdf.export', $donnees + [
            'event' => $event,
            'type'  => $type,
            'titre' => self::TYPES[$type][0],
            'theme' => $this->theme($event),
        ])->setPaper('a4')->setOptions([
            'isRemoteEnabled'      => false,
            'isHtml5ParserEnabled' => true,
            'chroot'               => base_path(),
            'fontDir'              => storage_path('fonts'),
            'fontCache'            => storage_path('fonts'),
            'defaultFont'          => 'DejaVu Sans',
        ])->output();
    }

    public function nomFichier(Event $event, string $type): string
    {
        return Str::slug($event->nom) . '-' . $type . '.pdf';
    }

    /** Toutes les photos d'origine, rangées par provenance, dans un ZIP. */
    public function zip(Event $event): string
    {
        $photos = $this->courant->pretend($event, fn () => Photo::publies()->oldest()->get());
        $fichier = tempnam(sys_get_temp_dir(), 'solen-zip');

        $zip = new ZipArchive();
        $zip->open($fichier, ZipArchive::OVERWRITE);

        foreach ($photos as $i => $photo) {
            if (! Storage::disk('public')->exists($photo->path)) {
                continue;
            }
            $dossier = str_contains($photo->path, '/photobooth/') ? 'photobooth' : 'galerie';
            $nom = sprintf('%s/%03d-%s.%s', $dossier, $i + 1, Str::slug($photo->participant?->prenom ?: 'invite'),
                pathinfo($photo->path, PATHINFO_EXTENSION) ?: 'jpg');
            $zip->addFromString($nom, Storage::disk('public')->get($photo->path));
        }

        $zip->close();
        $octets = (string) file_get_contents($fichier);
        @unlink($fichier);

        return $octets;
    }

    // --- Thème ------------------------------------------------------------------

    /**
     * Ce que les PDF reprennent du thème : couleurs, police des titres,
     * prénoms calligraphiés et fleurs (en PNG, seul format d'image que
     * DomPDF rend sans surprise).
     *
     * @return array<string, mixed>
     */
    public function theme(Event $event): array
    {
        $j = $event->jetons();
        $display = (string) ($j['--theme-display'] ?? '');
        $police = collect(self::POLICES)->keys()->first(fn ($nom) => str_contains($display, $nom));

        $fichier = fn (?string $chemin) => $chemin && is_file(public_path($chemin)) ? $this->dataUri(public_path($chemin)) : null;

        $theme = $event->theme;
        $fleurs = collect($theme?->decorsValides() ?? [])
            ->map(fn ($d) => $d['image'])
            ->filter(fn ($i) => Str::endsWith($i, '.png'))
            ->unique()->values();

        return [
            'encre'   => $j['--c-sur-page'] ?? '#1B1B2F',
            'doux'    => $j['--c-sur-page-doux'] ?? '#6E6E85',
            'page'    => $j['--c-page'] ?? '#FCFAF7',
            'carte'   => $j['--c-carte'] ?? '#FFFFFF',
            'accent'  => $j['--c-accent-texte'] ?? ($j['--theme-accent'] ?? '#A87C46'),
            'pale'    => $j['--c-accent-pale'] ?? '#F3EBDD',
            'bord'    => $j['--c-bord'] ?? '#E7E1D7',
            'police'  => $police,
            'policeFichier' => $police ? resource_path('fonts/' . self::POLICES[$police]) : null,
            'prenoms' => $fichier($theme?->visuel('titre')),
            'fleur'   => $fichier($fleurs->first()),
        ];
    }

    // --- Images ----------------------------------------------------------------

    /**
     * Les photos prêtes pour la page : réduites, et calées dans une case au
     * format 3:4. Recadrées par défaut ; contenues (sans rien couper) pour
     * le photobooth, dont le cadre doit rester entier.
     *
     * @return Collection<int, object{image: ?string, auteur: ?string}>
     */
    private function photos($requete, bool $contenir = false): Collection
    {
        return $requete->with('participant')->oldest()->get()
            ->map(fn (Photo $p) => (object) [
                'image'  => $this->vignette($p->path, 900, 1200, $contenir),
                'auteur' => trim(($p->participant?->prenom ?? '') . ' ' . ($p->participant?->nom ?? '')) ?: null,
            ])
            ->filter(fn ($p) => $p->image)
            ->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function histoire(): Collection
    {
        return ContentBlock::where('page', 'histoire')->where('type', 'etape_histoire')->orderBy('ordre')->get()
            ->map(fn ($b) => [
                'date'  => $b->donnees['date'] ?? null,
                'titre' => $b->donnees['titre'] ?? '',
                'texte' => $b->donnees['texte'] ?? '',
                'image' => ! empty($b->donnees['image']) ? $this->vignette($b->donnees['image'], 1200, 800) : null,
            ]);
    }

    /** @return array<string, int> joueurs par jeu */
    private function participationJeux(Event $event): array
    {
        return \App\Models\ScoreJeu::selectRaw('type_jeu, count(*) as n')->groupBy('type_jeu')->pluck('n', 'type_jeu')
            ->mapWithKeys(fn ($n, $cle) => [config("solen_schema.jeux.{$cle}.nom", $cle) => (int) $n])
            ->all();
    }

    /**
     * Une image du disque public, ajustée à L × H et rendue en JPEG data URI.
     * Recadrée au centre, ou contenue sur fond blanc.
     */
    private function vignette(string $chemin, int $l, int $h, bool $contenir = false): ?string
    {
        if (! Storage::disk('public')->exists($chemin)) {
            return null;
        }

        $source = @imagecreatefromstring(Storage::disk('public')->get($chemin));
        if (! $source) {
            return null;
        }

        [$sl, $sh] = [imagesx($source), imagesy($source)];
        $toile = imagecreatetruecolor($l, $h);
        imagefill($toile, 0, 0, imagecolorallocate($toile, 255, 255, 255));

        if ($contenir) {
            $r = min($l / $sl, $h / $sh);
            [$dl, $dh] = [(int) round($sl * $r), (int) round($sh * $r)];
            imagecopyresampled($toile, $source, (int) (($l - $dl) / 2), (int) (($h - $dh) / 2), 0, 0, $dl, $dh, $sl, $sh);
        } else {
            $r = max($l / $sl, $h / $sh);
            [$cl, $ch] = [(int) round($l / $r), (int) round($h / $r)];
            imagecopyresampled($toile, $source, 0, 0, (int) (($sl - $cl) / 2), (int) (($sh - $ch) / 2), $l, $h, $cl, $ch);
        }

        ob_start();
        imagejpeg($toile, null, 78);
        $jpeg = ob_get_clean();
        imagedestroy($source);
        imagedestroy($toile);

        return 'data:image/jpeg;base64,' . base64_encode($jpeg);
    }

    private function dataUri(string $fichier): string
    {
        return 'data:' . (mime_content_type($fichier) ?: 'image/png') . ';base64,' . base64_encode((string) file_get_contents($fichier));
    }
}
