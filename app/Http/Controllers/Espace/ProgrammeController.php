<?php

namespace App\Http\Controllers\Espace;

use App\Http\Controllers\Controller;
use App\Models\EtapeCeremonie;
use App\Models\EventPart;
use App\Solen\CurrentEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Le programme de la journée : les moments (mairie, cérémonie, vin
 * d'honneur…) et le déroulé de chacun.
 *
 * Jusqu'ici les mariés ne pouvaient modifier ni un lieu ni un horaire :
 * tout passait par la console Solen. Et une étape ajoutée n'était
 * rattachée à aucun moment, donc affichée nulle part.
 *
 * Les heures sont saisies et affichées dans le fuseau du mariage, et
 * stockées en UTC.
 */
class ProgrammeController extends Controller
{
    public function __construct(private readonly CurrentEvent $courant)
    {
    }

    public function index(): View
    {
        $event = $this->courant->get();
        $parts = EventPart::with('etapes')->orderBy('ordre')->get();

        return view('espace.programme', [
            'parts'      => $parts,
            'catalogue'  => collect(config('solen_schema.parties'))->except($parts->pluck('cle')->all()),
            'cultes'     => EventPart::TYPES_CEREMONIE,
            'local'      => fn (?Carbon $date) => $date ? $event->enHeureLocale($date)->format('Y-m-d\TH:i') : null,
            'jour'       => $event->dateLocale()?->format('Y-m-d'),
        ]);
    }

    // --- Moments ----------------------------------------------------------

    public function ajouterMoment(Request $request): RedirectResponse
    {
        $catalogue = config('solen_schema.parties');

        $donnees = $request->validate([
            'cle' => ['required', 'string', Rule::in(array_merge(array_keys($catalogue), ['autre']))],
            'nom' => ['nullable', 'required_if:cle,autre', 'string', 'max:80'],
        ]);

        $definition = $catalogue[$donnees['cle']] ?? ['nom' => $donnees['nom'], 'icone' => 'fa-calendar-day', 'ceremonie' => false];

        // Un moment « autre » reçoit une clé unique tirée de son nom.
        $cle = $donnees['cle'] === 'autre' ? $this->cleLibre(Str::slug($donnees['nom']) ?: 'moment') : $donnees['cle'];

        abort_if(EventPart::where('cle', $cle)->exists(), 422, 'Ce moment existe déjà.');

        $part = EventPart::create([
            'cle'            => $cle,
            'nom'            => $donnees['cle'] === 'autre' ? $donnees['nom'] : $definition['nom'],
            'icone'          => $definition['icone'],
            'type_ceremonie' => $definition['ceremonie'] ? ($cle === 'mairie' ? 'civil' : 'laique') : null,
            'ordre'          => (int) EventPart::max('ordre') + 1,
            'actif'          => true,
        ]);

        return redirect()->route('espace.programme')->with('ok', "« {$part->nom} » a été ajouté. Renseignez son lieu et son horaire.")
            ->withFragment('moment-' . $part->id);
    }

    public function majMoment(Request $request, int $id): RedirectResponse
    {
        $part  = EventPart::findOrFail($id);
        $event = $this->courant->get();

        $donnees = $request->validate([
            'nom'            => ['required', 'string', 'max:80'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'lieu_nom'       => ['nullable', 'string', 'max:160'],
            'lieu_adresse'   => ['nullable', 'string', 'max:255'],
            'accueil_at'     => ['nullable', 'date'],
            'debut_at'       => ['nullable', 'date'],
            'type_ceremonie' => ['nullable', Rule::in(array_keys(EventPart::TYPES_CEREMONIE))],
        ], [], [
            'lieu_nom' => 'nom du lieu', 'lieu_adresse' => 'adresse',
            'accueil_at' => 'heure d’accueil', 'debut_at' => 'heure de début',
        ]);

        // L'heure saisie est celle du lieu du mariage, pas celle du serveur.
        foreach (['accueil_at', 'debut_at'] as $champ) {
            $donnees[$champ] = ! empty($donnees[$champ])
                ? Carbon::parse($donnees[$champ], $event->timezone)->utc()
                : null;
        }

        $part->update($donnees + ['actif' => $request->boolean('actif')]);

        return back()->with('ok', "« {$part->nom} » a été enregistré.")->withFragment('moment-' . $part->id);
    }

    public function deplacerMoment(int $id, string $sens): RedirectResponse
    {
        $this->echanger(EventPart::orderBy('ordre')->orderBy('id')->get(), $id, $sens);

        return back()->withFragment('moment-' . $id);
    }

    public function supprimerMoment(int $id): RedirectResponse
    {
        $part = EventPart::findOrFail($id);
        $nom  = $part->nom;
        $part->etapes()->delete();
        $part->delete();

        return redirect()->route('espace.programme')->with('ok', "« {$nom} » a été retiré du programme.");
    }

    // --- Étapes d'un moment --------------------------------------------

    public function ajouterEtape(Request $request, int $id): RedirectResponse
    {
        $part = EventPart::findOrFail($id);

        $donnees = $request->validate([
            'titre'       => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        EtapeCeremonie::create($donnees + [
            'event_part_id' => $part->id,
            'ordre'         => (int) $part->etapes()->max('ordre') + 1,
            'en_cours'      => false,
            'termine'       => false,
        ]);

        return back()->withFragment('moment-' . $part->id);
    }

    public function majEtape(Request $request, int $id): RedirectResponse
    {
        $etape = EtapeCeremonie::findOrFail($id);

        $etape->update($request->validate([
            'titre'       => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]));

        return back()->withFragment('moment-' . $etape->event_part_id);
    }

    public function deplacerEtape(int $id, string $sens): RedirectResponse
    {
        $etape = EtapeCeremonie::findOrFail($id);

        $this->echanger(
            EtapeCeremonie::where('event_part_id', $etape->event_part_id)->orderBy('ordre')->orderBy('id')->get(),
            $id,
            $sens
        );

        return back()->withFragment('moment-' . $etape->event_part_id);
    }

    public function supprimerEtape(int $id): RedirectResponse
    {
        $etape = EtapeCeremonie::findOrFail($id);
        $part  = $etape->event_part_id;
        $etape->delete();

        return back()->withFragment('moment-' . $part);
    }

    // --- Outils -------------------------------------------------------------

    /** Échange un élément avec son voisin, puis renumérote proprement. */
    private function echanger($liste, int $id, string $sens): void
    {
        $liste = $liste->values();
        $i = $liste->search(fn ($e) => $e->id === $id);

        abort_if($i === false, 404);

        $j = $sens === 'monter' ? $i - 1 : $i + 1;

        if ($j >= 0 && $j < $liste->count()) {
            [$liste[$i], $liste[$j]] = [$liste[$j], $liste[$i]];
        }

        $liste->each(fn ($e, $n) => $e->ordre === $n + 1 ?: $e->update(['ordre' => $n + 1]));
    }

    private function cleLibre(string $base): string
    {
        $cle = $base;
        $n = 1;

        while (EventPart::where('cle', $cle)->exists() || array_key_exists($cle, config('solen_schema.parties'))) {
            $cle = $base . '-' . (++$n);
        }

        return $cle;
    }
}
