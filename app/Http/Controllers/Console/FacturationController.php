<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\DocumentCommercial as Doc;
use App\Models\Event;
use App\Solen\Documents;
use App\Solen\Entreprise;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use LogicException;

/**
 * Facturation Solen by NJIEZM.FR : devis, factures, avoirs.
 *
 * Réservée à l'équipe Solen. Les règles comptables (numérotation continue,
 * document émis figé, annulation par avoir) sont appliquées par le modèle :
 * cet écran ne peut pas les contourner.
 */
class FacturationController extends Controller
{
    public function __construct(private readonly Documents $documents)
    {
    }

    public function index(Request $request): View
    {
        $type   = $request->query('type');
        $statut = $request->query('statut');

        $docs = Doc::with('event')
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($statut, fn ($q) => $q->where('statut', $statut))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $annee = now()->year;
        $factures = Doc::deType(Doc::FACTURE)->whereNotNull('numero')->whereYear('emis_le', $annee);

        return view('console.facturation.index', [
            'docs'      => $docs,
            'type'      => $type,
            'statut'    => $statut,
            'manquants' => Entreprise::manquants(),
            'chiffres'  => [
                ['nombre' => Doc::euros((int) (clone $factures)->where('statut', 'paye')->sum('total_centimes')
                    + (int) Doc::deType(Doc::AVOIR)->whereYear('emis_le', $annee)->sum('total_centimes')), 'quoi' => "encaissé en {$annee}"],
                ['nombre' => Doc::euros((int) (clone $factures)->where('statut', 'emis')->sum('total_centimes')), 'quoi' => 'en attente de paiement'],
                ['nombre' => (clone $factures)->where('statut', 'emis')->where('echeance_le', '<', now())->count(), 'quoi' => 'en retard'],
                ['nombre' => Doc::deType(Doc::DEVIS)->whereIn('statut', ['brouillon', 'emis'])->count(), 'quoi' => 'devis en cours'],
            ],
        ]);
    }

    public function creer(Request $request): View
    {
        $event = $request->query('mariage') ? Event::where('slug', $request->query('mariage'))->first() : null;
        $proprio = $event?->users()->wherePivot('role', 'proprietaire')->first();

        return view('console.facturation.formulaire', [
            'doc' => new Doc([
                'type'             => $request->query('type', Doc::DEVIS),
                'event_id'         => $event?->id,
                'client_nom'       => $event?->nom,
                'client_email'     => $proprio?->email,
                'objet'            => $event ? 'Mariage ' . $event->nom : null,
                'lignes'           => $event ? [$this->ligneFormule($event->plan)] : [[]],
            ]),
            'mariages' => $this->mariages(),
            'catalogue' => $this->catalogue(),
            'codes'     => \App\Models\CodePromo::where('actif', true)->orderBy('code')->get(),
        ]);
    }

    public function stocker(Request $request): RedirectResponse
    {
        $doc = Doc::create($this->valider($request));

        return redirect()->route('console.facturation.montrer', $doc)->with('ok', $doc->nomDuType() . ' enregistré en brouillon.');
    }

    public function editer(Doc $document): View|RedirectResponse
    {
        if ($document->estEmis() && $document->type !== Doc::DEVIS) {
            return redirect()->route('console.facturation.montrer', $document)->with('erreur', 'Un document émis ne se modifie plus : annulez-le par un avoir.');
        }

        return view('console.facturation.formulaire', [
            'doc'       => $document,
            'mariages'  => $this->mariages(),
            'catalogue' => $this->catalogue(),
            'codes'     => \App\Models\CodePromo::where('actif', true)->orderBy('code')->get(),
        ]);
    }

    public function maj(Request $request, Doc $document): RedirectResponse
    {
        try {
            $document->update($this->valider($request, $document));
        } catch (LogicException $e) {
            return back()->with('erreur', $e->getMessage());
        }

        return redirect()->route('console.facturation.montrer', $document)->with('ok', 'Document mis à jour.');
    }

    public function montrer(Doc $document): View
    {
        $document->load('event', 'origine', 'derives');

        return view('console.facturation.montrer', [
            'doc'       => $document,
            'lien'      => $document->estEmis() ? $this->documents->lienCommercial($document) : null,
            'whatsapp'  => $document->estEmis() && $document->client_telephone
                ? route('console.facturation.whatsapp', $document) : null,
            'manquants' => Entreprise::manquants(),
        ]);
    }

    public function supprimer(Doc $document): RedirectResponse
    {
        try {
            $document->delete();
        } catch (LogicException $e) {
            return back()->with('erreur', $e->getMessage());
        }

        return redirect()->route('console.facturation')->with('ok', 'Brouillon supprimé.');
    }

    // --- Cycle de vie ----------------------------------------------------

    public function emettre(Doc $document): RedirectResponse
    {
        try {
            $document->emettre();
        } catch (LogicException $e) {
            return back()->with('erreur', $e->getMessage());
        }

        if ($document->code_promo_id && $document->type === Doc::FACTURE && $document->nature !== 'acompte') {
            \App\Models\CodePromo::whereKey($document->code_promo_id)->increment('utilisations');
        }

        return back()->with('ok', $document->libelle() . ' émis.');
    }

    public function payer(Request $request, Doc $document): RedirectResponse
    {
        $donnees = $request->validate([
            'mode_paiement' => ['required', Rule::in(array_keys(Doc::MODES_PAIEMENT))],
            'paye_le'       => ['nullable', 'date'],
        ]);

        $document->marquerPaye($donnees['mode_paiement'], $donnees['paye_le'] ?? null);

        return back()->with('ok', 'Paiement enregistré.');
    }

    public function avoir(Request $request, Doc $document): RedirectResponse
    {
        try {
            $avoir = $document->annulerParAvoir($request->input('motif'));
        } catch (LogicException $e) {
            return back()->with('erreur', $e->getMessage());
        }

        return redirect()->route('console.facturation.montrer', $avoir)->with('ok', "Facture annulée par l’avoir {$avoir->numero}.");
    }

    public function convertir(Doc $document): RedirectResponse
    {
        try {
            $facture = $document->convertirEnFacture();
        } catch (LogicException $e) {
            return back()->with('erreur', $e->getMessage());
        }

        return redirect()->route('console.facturation.montrer', $facture)->with('ok', 'Facture créée depuis le devis, en brouillon : vérifiez-la puis émettez-la.');
    }

    public function acompte(Request $request, Doc $document): RedirectResponse
    {
        $donnees = $request->validate([
            'mode'   => ['required', Rule::in(['pourcentage', 'montant'])],
            'valeur' => ['required', 'numeric', 'min:0.01', 'max:99999'],
        ]);

        try {
            $facture = $document->factureAcompte($donnees['mode'], (float) $donnees['valeur']);
        } catch (LogicException $e) {
            return back()->with('erreur', $e->getMessage());
        }

        return redirect()->route('console.facturation.montrer', $facture)->with('ok', 'Facture d’acompte créée en brouillon : vérifiez-la puis émettez-la.');
    }

    public function solde(Doc $document): RedirectResponse
    {
        try {
            $facture = $document->factureSolde();
        } catch (LogicException $e) {
            return back()->with('erreur', $e->getMessage());
        }

        return redirect()->route('console.facturation.montrer', $facture)->with('ok', 'Facture de solde créée en brouillon, acomptes déduits.');
    }

    public function statutDevis(Request $request, Doc $document): RedirectResponse
    {
        abort_unless($document->type === Doc::DEVIS, 404);
        $document->forceFill(['statut' => $request->input('statut') === 'refuse' ? 'refuse' : 'accepte'])->saveQuietly();

        return back()->with('ok', 'Devis marqué ' . Doc::STATUTS[$document->statut] . '.');
    }

    // --- Fichiers et envois -----------------------------------------------

    public function pdf(Request $request, Doc $document): Response
    {
        $pdf = $this->documents->pdfCommercial($document);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => ($request->boolean('voir') ? 'inline' : 'attachment') . '; filename="' . $this->documents->nomFichier($document) . '"',
        ]);
    }

    public function envoyer(Request $request, Doc $document): RedirectResponse
    {
        abort_unless($document->estEmis(), 422);

        $donnees = $request->validate([
            'email'   => ['required', 'email'],
            'message' => ['required', 'string', 'max:3000'],
            'joindre_cgv' => ['nullable', 'boolean'],
        ]);

        $pieces = [$this->documents->nomFichier($document) => $this->documents->pdfCommercial($document)];

        if ($request->boolean('joindre_cgv') && $document->event) {
            $pieces['conditions-generales-de-vente.pdf'] = $this->documents->pdfKit($document->event, 'cgv');
        }

        try {
            $this->documents->envoyer($donnees['email'], $document->libelle() . ' — ' . config('solen.entreprise.nom_commercial'), $donnees['message'], $pieces, $document->event);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('erreur', 'L’e-mail n’a pas pu partir : ' . $e->getMessage());
        }

        $document->update(['envoye_email_le' => now()]);

        return back()->with('ok', "Envoyé à {$donnees['email']}.");
    }

    /** Ouvre WhatsApp avec le message et le lien de téléchargement, et trace l'envoi. */
    public function whatsapp(Doc $document): RedirectResponse
    {
        abort_unless($document->estEmis() && $document->client_telephone, 422);

        $document->update(['envoye_whatsapp_le' => now()]);

        $texte = "Bonjour {$document->client_nom},\n\n"
            . "Voici votre {$document->libelle()} d’un montant de " . Doc::euros($document->total_centimes) . " :\n"
            . $this->documents->lienCommercial($document) . "\n\n"
            . 'Le lien reste valable ' . Documents::VALIDITE_JOURS . " jours.\n" . config('solen.entreprise.nom_commercial');

        return redirect()->away($this->documents->whatsapp($document->client_telephone, $texte));
    }

    // --- Outils -------------------------------------------------------------

    private function valider(Request $request, ?Doc $doc = null): array
    {
        $donnees = $request->validate([
            'type'               => [$doc ? 'prohibited' : 'required', Rule::in([Doc::DEVIS, Doc::FACTURE])],
            'event_id'           => ['nullable', 'exists:events,id'],
            'client_nom'         => ['required', 'string', 'max:160'],
            'client_email'       => ['nullable', 'email', 'max:255'],
            'client_telephone'   => ['nullable', 'string', 'max:40'],
            'client_adresse'     => ['nullable', 'string', 'max:500'],
            'objet'              => ['nullable', 'string', 'max:255'],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'notes_internes'     => ['nullable', 'string', 'max:5000'],
            'lignes'             => ['required', 'array', 'min:1'],
            'lignes.*.designation' => ['required', 'string', 'max:255'],
            'lignes.*.detail'      => ['nullable', 'string', 'max:500'],
            'lignes.*.quantite'    => ['required', 'numeric', 'min:0.01', 'max:9999'],
            'lignes.*.prix'        => ['required', 'numeric', 'min:-99999', 'max:99999'],
            'lignes.*.deduction'   => ['nullable', 'boolean'],
            'remise_type'          => ['nullable', Rule::in(['pourcentage', 'montant'])],
            'remise_valeur'        => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'remise_libelle'       => ['nullable', 'string', 'max:120'],
            'code_promo'           => ['nullable', 'string', 'max:40'],
            'remise_choix'         => ['nullable', 'string', 'regex:/^(pourcentage|montant|code):[0-9.]+$/'],
        ], [], ['client_nom' => 'nom du client', 'lignes.*.designation' => 'désignation', 'lignes.*.prix' => 'prix']);

        // La réduction se choisit dans une liste : « pourcentage:10 »,
        // « montant:50 » ou « code:3 ». Un code promo en fixe le type, la
        // valeur et le libellé.
        $choix = $donnees['remise_choix'] ?? null;
        $code  = $donnees['code_promo'] ?? null;
        unset($donnees['code_promo'], $donnees['remise_choix']);

        if ($choix && ! str_starts_with($choix, 'code:')) {
            [$type, $valeur] = explode(':', $choix);
            $donnees['remise_type'] = $type;
            $donnees['remise_valeur'] = (float) $valeur;
            $donnees['remise_libelle'] = null;
            $donnees['code_promo_id'] = null;
            $code = null;
        } elseif ($choix) {
            $code = \App\Models\CodePromo::find((int) substr($choix, 5))?->code ?? '—';
        } elseif (! $code) {
            $donnees['remise_type'] = null;
        }

        if ($code) {
            $promo = \App\Models\CodePromo::trouver($code);
            $refus = $promo ? $promo->refus() : 'Ce code promo n’existe pas.';

            if ($refus) {
                throw \Illuminate\Validation\ValidationException::withMessages(['code_promo' => $refus]);
            }

            $donnees['remise_type'] = $promo->type;
            $donnees['remise_valeur'] = $promo->valeur;
            $donnees['remise_libelle'] = $promo->description();
            $donnees['code_promo_id'] = $promo->id;
        } elseif (empty($donnees['remise_type']) || empty($donnees['remise_valeur'])) {
            $donnees['remise_type'] = $donnees['remise_valeur'] = $donnees['remise_libelle'] = null;
            $donnees['code_promo_id'] = null;
        }

        // Seule une déduction d'acompte (facture de solde) peut être négative.
        foreach ($donnees['lignes'] as $i => $l) {
            if ((float) $l['prix'] < 0 && empty($l['deduction'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(["lignes.{$i}.prix" => 'Un prix ne peut pas être négatif : utilisez une réduction.']);
            }
        }

        $donnees['lignes'] = collect($donnees['lignes'])->map(fn ($l) => array_filter([
            'designation'            => $l['designation'],
            'detail'                 => $l['detail'] ?? null,
            'quantite'               => (float) $l['quantite'],
            'prix_unitaire_centimes' => (int) round(((float) $l['prix']) * 100),
            'deduction'              => ! empty($l['deduction']) ?: null,
        ], fn ($v) => $v !== null))->values()->all();

        return $donnees;
    }

    /** Les lignes types : formules et options du catalogue. */
    private function catalogue(): array
    {
        return collect(config('solen.plans'))->map(fn ($p) => [
            'designation' => "Formule {$p['nom']}",
            'detail'      => $p['desc'],
            'prix'        => $p['prix'],
        ])->concat([
            ['designation' => 'Formule Clé en main', 'detail' => 'Site et jour J préparés entièrement par l’équipe Solen, thème sur mesure', 'prix' => 890],
            ['designation' => 'Thème personnalisé', 'detail' => 'Création d’un univers graphique aux couleurs du mariage', 'prix' => 150],
            ['designation' => 'Mise en page du livret de cérémonie', 'detail' => 'Livret PDF prêt à feuilleter et à imprimer', 'prix' => 90],
            ['designation' => 'Pack impressions QR livré', 'detail' => 'Cartes de table, chevalets et affiches imprimés', 'prix' => 49],
            ['designation' => 'Nom de domaine personnalisé', 'detail' => 'Un an', 'prix' => 25],
            ['designation' => 'Assistance sur place le jour J', 'detail' => 'Présence d’un membre de l’équipe Solen', 'prix' => 149],
        ])->values()->all();
    }

    /**
     * Les mariages, avec de quoi pré-remplir le client d'un seul choix :
     * nom du couple, e-mail du propriétaire, formule, date.
     */
    private function mariages()
    {
        return Event::with(['users' => fn ($q) => $q->wherePivot('role', 'proprietaire')])
            ->orderBy('nom')->get()
            ->map(fn (Event $e) => (object) [
                'id'    => $e->id,
                'nom'   => $e->nom,
                'slug'  => $e->slug,
                'remplissage' => [
                    'client_nom'   => $e->nom,
                    'client_email' => $e->users->first()?->email,
                    'objet'        => 'Mariage ' . $e->nom . ($e->dateLocale() ? ' — ' . $e->dateLocale()->translatedFormat('j F Y') : ''),
                    'formule'      => $this->ligneFormule($e->plan) ? [
                        'designation' => $this->ligneFormule($e->plan)['designation'],
                        'detail'      => $this->ligneFormule($e->plan)['detail'],
                        'prix'        => $this->ligneFormule($e->plan)['prix_unitaire_centimes'] / 100,
                    ] : null,
                ],
            ]);
    }

    private function ligneFormule(?string $plan): array
    {
        $p = collect(config('solen.plans'))->firstWhere('key', $plan);

        return $p ? ['designation' => "Formule {$p['nom']}", 'detail' => $p['desc'], 'quantite' => 1, 'prix_unitaire_centimes' => $p['prix'] * 100] : [];
    }
}
