<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventPart;
use App\Models\Plan;
use App\Models\Theme;
use App\Solen\CreationMariage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Solen\Invitations;
use App\Solen\Documents;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Création et gestion des mariages depuis la console.
 */
class MariageController extends Controller
{
    public function creer(): View
    {
        return view('console.assistant', $this->referentiels());
    }

    public function stocker(Request $request, CreationMariage $creation): RedirectResponse
    {
        $reponses = $request->validate([
            'nom'             => ['required', 'string', 'max:120'],
            'partenaire_1'    => ['nullable', 'string', 'max:60'],
            'partenaire_2'    => ['nullable', 'string', 'max:60'],
            'slug'            => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/'],
            'date_principale' => ['nullable', 'date'],
            'timezone'        => ['required', Rule::in(array_keys($this->fuseaux()))],
            'lieu_ville'      => ['nullable', 'string', 'max:120'],
            'lieu_pays'       => ['nullable', 'string', 'max:80'],
            'parties'         => ['required', 'array', 'min:1'],
            'parties.*'       => [Rule::in(array_keys(config('solen_schema.parties')))],
            'type_ceremonie'  => ['required', Rule::in(array_keys(config('solen_schema.deroules')))],
            'plan'            => ['required', 'exists:plans,cle'],
            'theme_id'        => ['required', 'exists:themes,id'],
            'email'           => ['nullable', 'email', 'max:255'],
            'est_demo'        => ['nullable', 'boolean'],
        ], [], [
            'nom'            => 'nom du mariage',
            'timezone'       => 'fuseau horaire',
            'parties'        => 'moments de la journée',
            'type_ceremonie' => 'type de cérémonie',
            'plan'           => 'formule',
            'theme_id'       => 'thème',
            'email'          => 'adresse e-mail des mariés',
        ]);

        $resultat = $creation->executer($reponses);

        // Le mot de passe généré n'est montré qu'une fois, jamais stocké en clair.
        return redirect()
            ->route('console.mariage', $resultat['event']->slug)
            ->with('ok', "Le mariage « {$resultat['event']->nom} » a été créé.")
            ->with('identifiants', $resultat['motDePasse'] ? [
                'email'      => $resultat['user']->email,
                'motDePasse' => $resultat['motDePasse'],
            ] : null);
    }

    /** Changements rapides depuis la fiche : formule, statut, commission. */
    public function maj(Request $request, string $slug): RedirectResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        $donnees = $request->validate([
            'plan'           => ['required', 'exists:plans,cle'],
            'statut'         => ['required', Rule::in(['brouillon', 'publie', 'archive'])],
            'commission_bps' => ['required', 'integer', 'min:0', 'max:2000'],
            'est_demo'       => ['nullable', 'boolean'],
        ], [], [
            'plan'           => 'formule',
            'commission_bps' => 'commission',
        ]);

        $ancienneFormule = $event->plan;

        $event->update([
            'plan'           => $donnees['plan'],
            'statut'         => $donnees['statut'],
            'commission_bps' => $donnees['commission_bps'],
            'est_demo'       => $request->boolean('est_demo'),
            'publie_at'      => $donnees['statut'] === 'publie' ? ($event->publie_at ?? now()) : $event->publie_at,
        ]);

        $message = 'Le mariage a été mis à jour.';

        // Monter de formule doit débloquer les modules correspondants.
        if ($ancienneFormule !== $donnees['plan']) {
            $ajoutes = $event->appliquerFormule();
            $message .= $ajoutes
                ? " {$ajoutes} module(s) débloqué(s) par la nouvelle formule."
                : ' Aucun module supplémentaire à débloquer.';
        }

        return back()->with('ok', $message);
    }

    /**
     * Suppression réversible : les données restent en base trente jours,
     * le temps qu'un client change d'avis ou signale une erreur.
     */
    public function supprimer(Request $request, string $slug): RedirectResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        $request->validate(
            ['confirmation' => ['required', Rule::in([$event->slug])]],
            ['confirmation.in' => 'Recopiez exactement l’identifiant du mariage pour confirmer.']
        );

        $event->delete();

        return redirect()
            ->route('console.index')
            ->with('ok', "« {$event->nom} » a été archivé. Les données restent récupérables.");
    }

    /** Proposition de slug pendant la saisie, sans appeler le serveur deux fois. */
    public function slug(Request $request): array
    {
        $base = Str::slug((string) $request->query('nom')) ?: 'mariage';

        return ['slug' => $base, 'libre' => ! Event::withTrashed()->where('slug', $base)->exists()];
    }

    /** @return array<string, mixed> */
    private function referentiels(): array
    {
        return [
            'parties'  => config('solen_schema.parties'),

            // Seuls les cultes pour lesquels une trame de déroulé existe :
            // proposer « autre » ici créerait une cérémonie vide.
            'cultes'   => collect(config('solen_schema.deroules'))
                ->keys()
                ->mapWithKeys(fn ($cle) => [$cle => EventPart::TYPES_CEREMONIE[$cle] ?? $cle])
                ->all(),

            // count() et non ->map->count() : les trames sont des tableaux,
            // pas des collections.
            'deroules' => collect(config('solen_schema.deroules'))->map(fn (array $t) => count($t)),
            'formules' => Plan::actifs()->get(),
            'themes'   => Theme::where('actif', true)->orderByRaw('event_id is not null')->orderBy('ordre')->get(),
            'fuseaux'  => $this->fuseaux(),
        ];
    }

    /** @return array<string, string> */
    private function fuseaux(): array
    {
        return [
            'Europe/Paris'       => 'France métropolitaine',
            'America/Martinique' => 'Martinique',
            'America/Guadeloupe' => 'Guadeloupe',
            'America/Cayenne'    => 'Guyane',
            'Indian/Reunion'     => 'La Réunion',
            'Europe/Brussels'    => 'Belgique',
            'Europe/Zurich'      => 'Suisse',
            'America/Toronto'    => 'Canada (Est)',
        ];
    }

    /**
     * Ce que Solen prend en charge pour ce couple, le carnet de l'équipe et
     * le logo des mariés (QR codes, impressions).
     */
    public function accompagnement(Request $request, string $slug): RedirectResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        $donnees = $request->validate([
            'accompagnement'   => ['nullable', 'array'],
            'accompagnement.*' => [Rule::in(array_keys(Event::ACCOMPAGNEMENTS))],
            'notes_internes'   => ['nullable', 'string', 'max:10000'],
            'logo'             => ['nullable', 'image', 'max:4096'],
        ]);

        $logo = $event->logo;

        if ($request->boolean('retirer_logo') && $logo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($logo);
            $logo = null;
        }

        if ($request->hasFile('logo')) {
            $logo && \Illuminate\Support\Facades\Storage::disk('public')->delete($logo);
            $logo = $request->file('logo')->store("mariages/{$event->id}", 'public');
        }

        $event->update([
            'accompagnement' => array_values($donnees['accompagnement'] ?? []),
            'notes_internes' => $donnees['notes_internes'] ?? null,
            'logo'           => $logo,
        ]);

        return back()->with('ok', 'L’accompagnement a été mis à jour.');
    }

    /**
     * Remise des clés : le mariage, préparé par l'équipe, est confié au
     * couple. Son compte est créé ou rattaché, il reçoit un lien pour
     * choisir son mot de passe, et les documents choisis en pièces jointes.
     * Le même message est proposé sur WhatsApp.
     */
    public function remettre(Request $request, string $slug): RedirectResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        $donnees = $request->validate([
            'name'      => ['required', 'string', 'max:120'],
            'email'     => ['required', 'email', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:40'],
            'message'   => ['required', 'string', 'max:3000'],
            'pieces'    => ['nullable', 'array'],
            'pieces.*'  => [Rule::in(array_keys(Documents::KIT))],
            'publier'   => ['nullable', 'boolean'],
        ], [], ['name' => 'nom', 'email' => 'adresse e-mail']);

        $user = User::where('email', $donnees['email'])->first();

        if ($user?->estSuperAdmin()) {
            return back()->with('erreur', 'Cette adresse appartient à l’équipe Solen.');
        }

        $user ??= User::create([
            'name'     => $donnees['name'],
            'email'    => $donnees['email'],
            'password' => Hash::make(Str::random(40)),
            'role'     => User::ROLE_ORGANISATEUR,
        ]);

        $event->users()->syncWithoutDetaching([$user->id => ['role' => 'proprietaire']]);

        if ($request->boolean('publier') && ! $event->estPublie()) {
            $event->update(['statut' => Event::STATUT_PUBLIE, 'publie_at' => $event->publie_at ?? now()]);
        }

        $lien = Invitations::lien($user, $event);
        $documents = app(Documents::class);

        $pieces = [];
        foreach ($donnees['pieces'] ?? [] as $piece) {
            $pieces[Str::slug(Documents::KIT[$piece] . ' ' . $event->nom) . '.pdf'] = $documents->pdfKit($event, $piece);
        }

        $texte = trim($donnees['message']) . "\n\nPour entrer dans votre espace, choisissez votre mot de passe ici (lien valable "
            . Invitations::VALIDITE_JOURS . " jours) :\n{$lien}";

        try {
            $documents->envoyer($user->email, 'Votre espace ' . $event->nom . ' est prêt', $texte, $pieces, $event);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('erreur', 'Le compte est prêt, mais l’e-mail n’est pas parti : ' . $e->getMessage())
                ->with('remise', ['lien' => $lien, 'whatsapp' => null]);
        }

        $whatsapp = null;
        if (! empty($donnees['telephone'])) {
            $liens = collect($donnees['pieces'] ?? [])
                ->map(fn ($p) => '• ' . Documents::KIT[$p] . ' : ' . $documents->lienKit($event, $p))
                ->implode("\n");
            $whatsapp = $documents->whatsapp($donnees['telephone'], $texte . ($liens ? "\n\nVos documents :\n{$liens}" : ''));
        }

        return back()
            ->with('ok', "Les clés sont remises : {$user->email} a reçu son lien d’accès" . ($pieces ? ' et ' . count($pieces) . ' document(s)' : '') . '.')
            ->with('remise', ['lien' => $lien, 'whatsapp' => $whatsapp]);
    }
}
