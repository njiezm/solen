<?php

namespace App\Http\Controllers;

use App\Mail\AlerteVente;
use App\Mail\CommandeConfirmee;
use App\Models\Commande;
use App\Models\EventPart;
use App\Models\Plan;
use App\Solen\CreationMariage;
use App\Solen\StripeConnect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

/**
 * Achat d'une formule depuis la vitrine.
 *
 * Le mariage n'est créé qu'après encaissement : tant que le paiement n'est
 * pas confirmé, il n'existe qu'une commande. Aucune formule n'est gratuite :
 * tout mariage passe par Stripe.
 */
class CommandeController extends Controller
{
    public function __construct(
        private readonly StripeConnect $stripe,
        private readonly CreationMariage $creation,
    ) {
    }

    /** Le mariage vitrine, s'il existe : la landing y renvoie. */
    public static function demo(): ?\App\Models\Event
    {
        return \App\Models\Event::demos()
            ->where('statut', \App\Models\Event::STATUT_PUBLIE)
            ->orderByDesc('id')
            ->first();
    }

    /** Vérifie un code promo pour l'aperçu du total, avant paiement. */
    public function verifierCode(Request $request): \Illuminate\Http\JsonResponse
    {
        $formule = Plan::actifs()->where('cle', (string) $request->query('plan'))->first();
        $promo   = \App\Models\CodePromo::trouver($request->query('code'));
        $refus   = ! $formule ? 'Formule inconnue.' : ($promo ? $promo->refus($formule->cle) : 'Ce code promo n’existe pas.');

        if ($refus) {
            return response()->json(['valide' => false, 'message' => $refus]);
        }

        $prix = $formule->prix * 100;
        $remise = $promo->remise($prix);

        return response()->json([
            'valide'  => true,
            'message' => $promo->description(),
            'remise'  => $remise / 100,
            'total'   => ($prix - $remise) / 100,
        ]);
    }

    /** Formulaire de commande, pré-réglé sur la formule cliquée. */
    public function formulaire(string $plan): View
    {
        $formule = Plan::actifs()->where('cle', $plan)->firstOrFail();

        $formules = Plan::actifs()->get();

        // Les avantages sont décrits dans config/solen.php ; on les rapproche
        // des formules en base pour que le récapitulatif suive le choix de
        // l'acheteur sans recharger la page.
        $avantages = collect(config('solen.plans'))->keyBy('key');

        return view('solen.commander', [
            'formule'  => $formule,
            'formules' => $formules,
            'details'  => $formules->mapWithKeys(fn (Plan $p) => [$p->cle => [
                'nom'      => $p->nom,
                'prix'     => $p->prix,
                'accroche' => $p->accroche,
                'desc'     => $p->description,
                'features' => $avantages[$p->cle]['features'] ?? [],
                'url'      => route('commander', $p->cle),
            ]]),
            'cultes'   => collect(config('solen_schema.deroules'))
                ->keys()
                ->mapWithKeys(fn ($c) => [$c => EventPart::TYPES_CEREMONIE[$c] ?? $c])
                ->all(),
            'parties'  => config('solen_schema.parties'),
            'themes'   => \App\Models\Theme::publics()->get(),
            'fuseaux'  => [
                'Europe/Paris'       => 'France métropolitaine',
                'America/Martinique' => 'Martinique',
                'America/Guadeloupe' => 'Guadeloupe',
                'America/Cayenne'    => 'Guyane',
                'Indian/Reunion'     => 'La Réunion',
            ],
        ]);
    }

    public function payer(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'plan'            => ['required', 'exists:plans,cle'],
            'nom'             => ['required', 'string', 'max:120'],
            'partenaire_1'    => ['nullable', 'string', 'max:60'],
            'partenaire_2'    => ['nullable', 'string', 'max:60'],
            'email'           => ['required', 'email', 'max:255'],
            'date_principale' => ['nullable', 'date', 'after:today'],
            'timezone'        => ['required', 'timezone'],
            'lieu_ville'      => ['nullable', 'string', 'max:120'],
            'type_ceremonie'  => ['required', Rule::in(array_keys(config('solen_schema.deroules')))],
            'parties'         => ['required', 'array', 'min:1'],
            'parties.*'       => [Rule::in(array_keys(config('solen_schema.parties')))],
            'theme_id'        => ['required', 'exists:themes,id'],
            'cgv'             => ['accepted'],
            'code_promo'      => ['nullable', 'string', 'max:40'],
        ], [
            'cgv.accepted'          => 'Vous devez accepter les conditions pour continuer.',
            'date_principale.after' => 'La date du mariage doit être à venir.',
        ], [
            'nom'            => 'nom du mariage',
            'email'          => 'adresse e-mail',
            'timezone'       => 'fuseau horaire',
            'type_ceremonie' => 'type de cérémonie',
            'parties'        => 'moments de la journée',
        ]);

        $formule = Plan::actifs()->where('cle', $donnees['plan'])->firstOrFail();

        // Le code promo est revérifié ici : l'aperçu du navigateur n'engage rien.
        $promo = null;
        if ($code = $donnees['code_promo'] ?? null) {
            $promo = \App\Models\CodePromo::trouver($code);
            $refus = $promo ? $promo->refus($formule->cle) : 'Ce code promo n’existe pas.';

            if ($refus) {
                return back()->withInput()->withErrors(['code_promo' => $refus]);
            }
        }
        unset($donnees['code_promo']);

        $prix   = $formule->prix * 100;
        $remise = $promo?->remise($prix) ?? 0;

        $commande = Commande::create([
            ...$donnees,
            'montant_centimes' => $prix - $remise,
            'remise_centimes'  => $remise,
            'code_promo_id'    => $promo?->id,
            'statut'           => Commande::EN_ATTENTE,
        ]);

        if (! $this->stripe->configure()) {
            return back()->withInput()->with('erreur',
                'Le paiement est momentanément indisponible. Écrivez-nous, nous prendrons le relais.');
        }

        try {
            $session = $this->stripe->client()->checkout->sessions->create([
                'mode'                 => 'payment',
                'customer_email'       => $commande->email,
                'client_reference_id'  => $commande->uuid,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'quantity'   => 1,
                    'price_data' => [
                        'currency'    => 'eur',
                        'unit_amount' => $commande->montant_centimes,
                        'product_data' => [
                            'name'        => 'Solen — formule ' . $formule->nom,
                            'description' => $formule->description,
                        ],
                    ],
                ]],
                'metadata'    => ['type' => 'commande', 'commande' => $commande->uuid],
                'success_url' => route('commande.merci', $commande->uuid) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('commande.annulee', $commande->uuid),
            ]);
        } catch (ApiErrorException $e) {
            report($e);
            $commande->update(['statut' => Commande::ANNULEE]);

            return back()->withInput()->with('erreur', 'Le paiement n’a pas pu être préparé. Merci de réessayer.');
        }

        $commande->update(['stripe_session_id' => $session->id]);

        return redirect()->away($session->url);
    }

    /** Retour de Stripe après paiement. */
    public function merci(Request $request, string $uuid): View|RedirectResponse
    {
        $commande = Commande::where('uuid', $uuid)->firstOrFail();

        // Le webhook fait foi, mais il peut arriver après le navigateur.
        if ($commande->statut === Commande::EN_ATTENTE && $id = $request->query('session_id')) {
            try {
                $session = $this->stripe->client()->checkout->sessions->retrieve($id);

                if ($session->payment_status === 'paid') {
                    $commande->update([
                        'statut'                => Commande::PAYEE,
                        'paye_at'               => now(),
                        'stripe_payment_intent' => $session->payment_intent,
                    ]);
                }
            } catch (ApiErrorException $e) {
                report($e);
            }
        }

        if ($commande->estMontee()) {
            if ($commande->statut === Commande::PAYEE) {
                self::appliquerMontee($commande);
            }

            return redirect()->route('espace.formule', $commande->event->slug)->with(
                $commande->fresh()->honoree() ? 'ok' : 'erreur',
                $commande->fresh()->honoree()
                    ? 'Votre formule est passée en ' . ($commande->formule()?->nom ?? $commande->plan) . ' : les nouveaux modules sont activés.'
                    : 'Le paiement n’a pas encore été confirmé. Rechargez la page dans un instant.'
            );
        }

        if ($commande->statut === Commande::PAYEE) {
            return $this->honorer($commande);
        }

        if ($commande->honoree()) {
            return view('solen.merci', [
                'commande'     => $commande,
                'identifiants' => null,
            ]);
        }

        return view('solen.merci', ['commande' => $commande, 'identifiants' => null]);
    }

    public function annulee(string $uuid): View
    {
        $commande = Commande::where('uuid', $uuid)->firstOrFail();

        if ($commande->statut === Commande::EN_ATTENTE) {
            $commande->update(['statut' => Commande::ANNULEE]);
            $this->prevenirEchec($commande);
        }

        return view('solen.commande-annulee', ['commande' => $commande]);
    }

    /**
     * Crée le mariage et affiche les identifiants. Idempotent : une commande
     * déjà honorée ne recrée rien, même si Stripe rejoue son webhook.
     */
    private function honorer(Commande $commande): View
    {
        if ($commande->honoree()) {
            return view('solen.merci', ['commande' => $commande, 'identifiants' => null]);
        }

        $resultat = $this->creation->executer($commande->versAssistant());

        $commande->update([
            'statut'   => Commande::HONOREE,
            'event_id' => $resultat['event']->id,
        ]);

        Log::info('Solen : formule vendue', [
            'commande' => $commande->uuid,
            'plan'     => $commande->plan,
            'event'    => $resultat['event']->slug,
        ]);

        self::prevenir($commande->fresh(), $resultat['motDePasse']);
        self::facturer($commande->fresh());

        if ($commande->code_promo_id) {
            \App\Models\CodePromo::whereKey($commande->code_promo_id)->increment('utilisations');
        }

        return view('solen.merci', [
            'commande'     => $commande->fresh(),
            'identifiants' => $resultat['motDePasse'] ? [
                'email'      => $resultat['user']->email,
                'motDePasse' => $resultat['motDePasse'],
            ] : null,
        ]);
    }

    /**
     * Toute vente encaissée a sa facture, déjà réglée par carte.
     *
     * Si les mentions légales de l'entreprise sont incomplètes, la facture
     * reste en brouillon (marquée payée) : elle apparaît dans la console et
     * sera émise dès qu'elles seront renseignées. Jamais de facture invalide.
     */
    public static function facturer(Commande $commande): ?\App\Models\DocumentCommercial
    {
        try {
            $existante = \App\Models\DocumentCommercial::where('commande_id', $commande->id)->first();
            if ($existante) {
                return $existante;
            }

            $plan = collect(config('solen.plans'))->firstWhere('key', $commande->plan);
            $origine = collect(config('solen.plans'))->firstWhere('key', $commande->plan_origine);

            $facture = \App\Models\DocumentCommercial::create([
                'type'          => \App\Models\DocumentCommercial::FACTURE,
                'statut'        => 'paye',
                'event_id'      => $commande->event_id,
                'commande_id'   => $commande->id,
                'client_nom'    => $commande->nom,
                'client_email'  => $commande->email,
                'objet'         => 'Commande en ligne — mariage ' . $commande->nom,
                'lignes'        => [[
                    'designation'            => $commande->estMontee()
                        ? 'Passage de la formule ' . ($origine['nom'] ?? $commande->plan_origine) . ' à ' . ($plan['nom'] ?? $commande->plan)
                        : 'Formule ' . ($plan['nom'] ?? $commande->plan),
                    'detail'                 => $commande->estMontee() ? 'Différence de prix entre les deux formules' : ($plan['desc'] ?? null),
                    'quantite'               => 1,
                    'prix_unitaire_centimes' => (int) $commande->montant_centimes + (int) $commande->remise_centimes,
                ]],
                'remise_type'   => $commande->remise_centimes ? 'montant' : null,
                'remise_valeur' => $commande->remise_centimes ? $commande->remise_centimes / 100 : null,
                'remise_libelle' => $commande->code_promo_id ? \App\Models\CodePromo::find($commande->code_promo_id)?->description() : null,
                'code_promo_id' => $commande->code_promo_id,
                'mode_paiement' => 'carte',
                'paye_le'       => $commande->paye_at ?? now(),
            ]);

            if (\App\Solen\Entreprise::complete()) {
                $facture->emettre();

                $documents = app(\App\Solen\Documents::class);
                $documents->envoyer($commande->email, 'Votre facture ' . $facture->numero . ' — ' . config('solen.entreprise.nom_commercial'),
                    "Bonjour,

Merci pour votre commande. Vous trouverez ci-joint votre facture, déjà réglée par carte.",
                    [$documents->nomFichier($facture) => $documents->pdfCommercial($facture)],
                    $commande->event);
                $facture->update(['envoye_email_le' => now()]);
            }

            return $facture;
        } catch (\Throwable $e) {
            // La vente est encaissée : une facture qui échoue ne doit pas la bloquer.
            report($e);

            return null;
        }
    }

    /**
     * Envoie la confirmation au client et l'alerte à l'équipe.
     *
     * Les envois sont protégés : un serveur SMTP en panne ne doit jamais
     * faire échouer une vente déjà encaissée. Le mot de passe reste affiché
     * à l'écran, il n'est donc pas perdu si l'e-mail ne part pas.
     */
    public static function prevenir(Commande $commande, ?string $motDePasse): void
    {
        try {
            Mail::to($commande->email)->send(new CommandeConfirmee($commande, $motDePasse));
        } catch (\Throwable $e) {
            report($e);
            Log::error('Solen : confirmation d’achat non envoyée', [
                'commande' => $commande->uuid,
                'email'    => $commande->email,
            ]);
        }

        try {
            Mail::to(config('solen.brand.email'))->send(new AlerteVente($commande));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** L'acheteur a renoncé : le signal vaut souvent la vente. */
    private function prevenirEchec(Commande $commande): void
    {
        try {
            Mail::to(config('solen.brand.email'))->send(new AlerteVente($commande, echec: true));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Appelé par le webhook Stripe quand l'acheteur ferme son navigateur
     * avant le retour. Le mariage est créé quand même.
     */
    /**
     * Le couple a payé la différence : la formule change, les modules
     * manquants s'ajoutent (rien n'est retiré ni écrasé), et la facture
     * part. Idempotent, comme le reste du tunnel.
     */
    public static function appliquerMontee(Commande $commande): void
    {
        if ($commande->honoree()) {
            return;
        }

        $event = $commande->event;
        $event->update(['plan' => $commande->plan]);
        app(\App\Solen\CurrentEvent::class)->pretend($event, fn () => $event->appliquerFormule());
        app(\App\Solen\Jeux::class)->preparer($event);

        $commande->update(['statut' => Commande::HONOREE]);

        Log::info('Solen : montée de formule', ['event' => $event->slug, 'de' => $commande->plan_origine, 'vers' => $commande->plan]);

        self::facturer($commande->fresh());
    }

    public static function surPaiementConfirme(object $session): void
    {
        $uuid = $session->metadata->commande ?? $session->client_reference_id ?? null;

        if (! $uuid) {
            return;
        }

        $commande = Commande::where('uuid', $uuid)->first();

        if (! $commande || $commande->honoree()) {
            return;
        }

        $commande->update([
            'statut'                => Commande::PAYEE,
            'paye_at'               => now(),
            'stripe_payment_intent' => $session->payment_intent ?? null,
        ]);

        if ($commande->estMontee()) {
            self::appliquerMontee($commande);

            return;
        }

        $creation = app(CreationMariage::class);
        $resultat = $creation->executer($commande->versAssistant());

        $commande->update([
            'statut'   => Commande::HONOREE,
            'event_id' => $resultat['event']->id,
        ]);

        // Le navigateur n'a jamais atteint la page de remerciement :
        // l'e-mail est le seul moyen pour l'acheteur d'obtenir ses accès.
        self::prevenir($commande->fresh(), $resultat['motDePasse']);

        // Ce chemin oubliait la facture : toute vente encaissée a la sienne.
        self::facturer($commande->fresh());

        if ($commande->code_promo_id) {
            \App\Models\CodePromo::whereKey($commande->code_promo_id)->increment('utilisations');
        }
    }
}
