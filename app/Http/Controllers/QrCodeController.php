<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use App\Models\QrCodeScan;
use App\Solen\CurrentEvent;
use App\Solen\QrVisuel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;

/**
 * QR codes des mariés et leurs statistiques, anonymes.
 *
 * Un scan est compté côté serveur, puis l'invité est redirigé aussitôt :
 * plus de page intermédiaire qui demandait la géolocalisation et calculait
 * une empreinte de l'appareil. On garde la date, le type d'appareil et
 * une IP tronquée, effacés au bout de 90 jours.
 *
 * Les recherches sont explicites (findOrFail sur un modèle cloisonné) :
 * la liaison implicite des routes ne filtrait pas par mariage.
 */
class QrCodeController extends Controller
{
    public function __construct(private readonly CurrentEvent $courant)
    {
    }

    public function index(): View
    {
        return view('admin.qrcodes.index', [
            'qrCodes' => QrCode::withCount('scans')->orderBy('source')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'source'          => ['required', 'string', 'max:255'],
            'destination_url' => ['required', 'url', 'max:500'],
        ]);

        QrCode::create($donnees + ['is_active' => true]);

        return redirect()->route('admin.qrcodes.index')->with('ok', 'QR code créé.');
    }

    public function stats(int $qrCode): View
    {
        $qr = QrCode::findOrFail($qrCode);

        return view('admin.qrcodes.stats', [
            'qrCode' => $qr,
            'scans'  => $qr->scans()->orderByDesc('scanned_at')->get(),
        ]);
    }

    /** L'adresse portée par le QR code : on compte, on redirige. */
    public function track(Request $request, string $uuid): RedirectResponse
    {
        // Hors mariage : le QR est identifié par son seul uuid.
        $qr = QrCode::tousEvenements()->where('uuid', $uuid)->where('is_active', true)->firstOrFail();

        $compter = $qr->event?->reglage('qr', 'statistiques', true) ?? true;

        if ($compter) {
            QrCodeScan::create([
                'qr_code_id' => $qr->id,
                'ip_address' => QrCodeScan::tronquer($request->ip()),
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
                'appareil'   => QrCodeScan::appareil((string) $request->userAgent()),
                'scanned_at' => now(),
            ]);
        }

        return redirect()->away($qr->destination_url);
    }

    /**
     * Le kit de départ : un QR par usage, plus un par table. Ce qui existe
     * déjà (même nom) n'est pas recréé.
     */
    public const KIT = [
        'invitation' => ['nom' => 'Faire-part',      'route' => 'landing',       'source' => 'faire-part', 'phrase' => 'Toutes les infos de notre mariage'],
        'accueil'    => ['nom' => 'Accueil',         'route' => 'home',          'source' => 'entree',     'phrase' => 'Le programme de la journée'],
        'photobooth' => ['nom' => 'Photobooth',      'route' => 'photobooth',    'source' => 'photobooth', 'phrase' => 'Prenez la pose !', 'module' => 'photobooth'],
        'galerie'    => ['nom' => 'Galerie photos',  'route' => 'galerie.index', 'source' => 'galerie',    'phrase' => 'Partagez vos photos', 'module' => 'mur'],
        'livredor'   => ['nom' => 'Livre d’or',      'route' => 'livreOr.index', 'source' => 'livre-or',   'phrase' => 'Laissez-nous un mot', 'module' => 'livredor'],
        'livret'     => ['nom' => 'Livret',          'route' => 'livret',        'source' => 'eglise',     'phrase' => 'Le livret de la cérémonie', 'module' => 'livret'],
    ];

    /** Les supports imprimables, et combien en tient une feuille A4. */
    public const MODELES = [
        'affiche'   => ['nom' => 'Affiche A4',           'par_page' => 1,  'desc' => 'Une grande affiche par QR, pour l’entrée ou le photobooth.'],
        'cartes'    => ['nom' => 'Cartes de table (A6)', 'par_page' => 4,  'desc' => 'Quatre cartes par feuille, à poser sur chaque table.'],
        'chevalets' => ['nom' => 'Chevalets pliables',   'par_page' => 2,  'desc' => 'À plier en deux : le QR se lit des deux côtés de la table.'],
        'stickers'  => ['nom' => 'Planche de stickers',  'par_page' => 12, 'desc' => 'Douze petits QR par feuille, pour les faire-part ou les verres.'],
    ];

    public function kit(Request $request): RedirectResponse
    {
        $event = $this->courant->get();
        $donnees = $request->validate(['tables' => ['nullable', 'integer', 'min:0', 'max:80']]);
        $crees = 0;

        $ajouter = function (string $nom, string $source, string $url) use (&$crees) {
            if (! QrCode::where('name', $nom)->exists()) {
                QrCode::create(['name' => $nom, 'source' => $source, 'destination_url' => $url, 'is_active' => true]);
                $crees++;
            }
        };

        foreach (self::KIT as $definition) {
            if (! empty($definition['module']) && ! $event->aModule($definition['module'])) {
                continue;
            }
            $ajouter($definition['nom'], $definition['source'], route($definition['route']));
        }

        for ($n = 1; $n <= (int) ($donnees['tables'] ?? 0); $n++) {
            $ajouter("Table {$n}", 'table', route('home'));
        }

        return back()->with('ok', $crees ? "{$crees} QR code(s) créé(s)." : 'Le kit était déjà complet.');
    }

    /** Le QR code en SVG : couleurs du thème ou noir, avec ou sans logo. */
    public function download(Request $request, int $qrCode): Response|RedirectResponse
    {
        $qr    = QrCode::findOrFail($qrCode);
        $event = $this->courant->get();

        $svg = QrVisuel::svg(
            route('qr.track', $qr->uuid),
            $event,
            $request->boolean('logo', true),
            $request->query('couleur') === 'noir' ? 'noir' : 'theme',
            1200,
        );

        return response($svg)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'attachment; filename="qr-' . Str::slug($event->nom . ' ' . $qr->name) . '.svg"');
    }

    /**
     * Planches prêtes à imprimer : affiche, cartes de table, chevalets,
     * stickers. Une page HTML aux dimensions du papier, à imprimer depuis le
     * navigateur ou à enregistrer en PDF : le SVG reste net à toute taille.
     */
    public function impressions(Request $request): View
    {
        $event = $this->courant->get();

        $donnees = $request->validate([
            'modele'  => ['required', Rule::in(array_keys(self::MODELES))],
            'qr'      => ['required', 'array', 'min:1'],
            'qr.*'    => ['integer'],
            'logo'    => ['nullable', 'boolean'],
            'couleur' => ['nullable', Rule::in(['theme', 'noir'])],
            'titre'   => ['nullable', 'string', 'max:80'],
        ], ['qr.required' => 'Cochez au moins un QR code à imprimer.']);

        $avecLogo = $request->boolean('logo');
        $couleur  = $donnees['couleur'] ?? 'theme';
        $phrases  = collect(self::KIT)->pluck('phrase', 'source');

        $qrs = QrCode::whereIn('id', $donnees['qr'])->orderBy('id')->get()->map(fn ($qr) => [
            'nom'    => $qr->name,
            'phrase' => ($donnees['titre'] ?? null) ?: ($phrases[$qr->source] ?? ($qr->source === 'table' ? 'Le programme, les jeux, les photos' : 'Scannez-moi')),
            'svg'    => QrVisuel::svg(route('qr.track', $qr->uuid), $event, $avecLogo, $couleur),
        ]);

        abort_if($qrs->isEmpty(), 404);

        return view('admin.qrcodes.impression', [
            'modele'  => $donnees['modele'],
            'gabarit' => self::MODELES[$donnees['modele']],
            'qrs'     => $qrs,
            'logo'    => $avecLogo ? QrVisuel::logoEnDonnees($event) : null,
            'couleur' => $couleur,
        ]);
    }
}
