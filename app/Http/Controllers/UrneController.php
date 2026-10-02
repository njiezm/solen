<?php

namespace App\Http\Controllers;

use App\Mail\RecuCagnotte;
use App\Models\Event;
use App\Models\Participant;
use App\Models\UrneDon;
use App\Solen\CurrentEvent;
use App\Solen\StripeConnect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * La cagnotte.
 *
 * Le paiement est un « destination charge » : Stripe encaisse puis reverse
 * immédiatement au compte des mariés, en retenant la commission éventuelle.
 * Sans ce mécanisme, l'argent atterrirait sur le compte de Solen — ce qui
 * n'est pas permis sans agrément d'établissement de paiement.
 */
class UrneController extends Controller
{
    public function __construct(private readonly StripeConnect $stripe)
    {
    }

    public function index(CurrentEvent $courant): View
    {
        $event = $courant->get();

        return view('pages.urne', [
            'titre'        => $event->reglage('cagnotte', 'titre', 'Notre liste de mariage'),
            'introduction' => $event->reglage('cagnotte', 'texte_intro'),
            'objectif'     => $event->reglage('cagnotte', 'objectif'),
            'montants'     => $this->montantsSuggeres($event),
            'collecte'     => UrneDon::where('statut', 'payé')->sum('montant'),
            'participants' => $event->reglage('cagnotte', 'afficher_participants')
                ? UrneDon::with('participant')->where('statut', 'payé')->latest()->get()
                : collect(),
            'afficherMontants' => (bool) $event->reglage('cagnotte', 'afficher_montants'),
            'ouvert'       => $this->stripe->pretAEncaisser($event),
        ]);
    }

    public function payer(Request $request, CurrentEvent $courant): RedirectResponse
    {
        $event = $courant->get();

        if (! $this->stripe->pretAEncaisser($event)) {
            return back()->with('error', 'La cagnotte n’est pas encore ouverte. Revenez d’ici peu.');
        }

        $donnees = $request->validate([
            'prenom'  => ['required', 'string', 'max:100'],
            'nom'     => ['required', 'string', 'max:100'],
            'email'   => ['nullable', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:1000'],
            'montant' => ['required', 'numeric', 'min:1', 'max:10000'],
        ], [], ['prenom' => 'prénom', 'nom' => 'nom', 'montant' => 'montant']);

        $participant = Participant::firstOrCreate(
            ['prenom' => $donnees['prenom'], 'nom' => $donnees['nom']],
            ['email' => $donnees['email'] ?? null],
        );

        $centimes   = (int) round($donnees['montant'] * 100);
        $commission = $this->stripe->commission($event, $centimes);

        $don = UrneDon::create([
            'participant_id'      => $participant->id,
            'montant'             => $donnees['montant'],
            'devise'              => 'EUR',
            'message'             => $donnees['message'] ?? null,
            'moyen_paiement'      => 'stripe',
            'statut'              => 'en_attente',
            'commission_centimes' => $commission,
        ]);

        try {
            $session = $this->stripe->client()->checkout->sessions->create([
                'mode'                 => 'payment',
                'payment_method_types' => ['card'],
                'customer_email'       => $donnees['email'] ?? null,
                'line_items' => [[
                    'quantity'   => 1,
                    'price_data' => [
                        'currency'     => 'eur',
                        'unit_amount'  => $centimes,
                        'product_data' => [
                            'name'        => 'Cagnotte — ' . $event->nom,
                            'description' => 'Participation de ' . $participant->prenom . ' ' . $participant->nom,
                        ],
                    ],
                ]],
                'payment_intent_data' => array_filter([
                    'transfer_data'          => ['destination' => $event->stripe_account_id],
                    'application_fee_amount' => $commission ?: null,
                    'metadata'               => ['don_id' => $don->id, 'event_id' => $event->id],
                ]),
                'metadata'    => ['don_id' => $don->id, 'event_id' => $event->id],
                'success_url' => route('urne.success', $event->slug) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('urne.cancel', $event->slug),
            ]);
        } catch (ApiErrorException $e) {
            report($e);
            $don->delete();

            return back()->with('error', 'Le paiement n’a pas pu être préparé. Merci de réessayer.');
        }

        $don->update(['transaction_id' => $session->id]);

        return redirect()->away($session->url);
    }

    public function success(Request $request): View
    {
        $don = null;

        if ($id = $request->query('session_id')) {
            $don = UrneDon::where('transaction_id', $id)->first();

            // Le webhook fait foi, mais il peut arriver après le retour du
            // navigateur : on confirme aussi ici, pour l'affichage.
            if ($don && $don->statut !== 'payé') {
                try {
                    $session = $this->stripe->client()->checkout->sessions->retrieve($id);

                    if ($session->payment_status === 'paid') {
                        $this->confirmer($don, $session->payment_intent);
                    }
                } catch (ApiErrorException $e) {
                    report($e);
                }
            }
        }

        return view('pages.urne-success', [
            'montant' => $don?->montant,
            'message' => $don?->message,
        ]);
    }

    public function cancel(): View
    {
        return view('pages.urne-cancel');
    }

    /**
     * Webhook Stripe. Hors contexte de mariage : ni cookie, ni URL de
     * locataire. Le don est retrouvé par ses métadonnées, et le scope global
     * doit être explicitement contourné.
     */
    public function stripeWebhook(Request $request)
    {
        try {
            $evenement = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                (string) config('services.stripe.webhook_secret'),
            );
        } catch (\UnexpectedValueException) {
            return response()->json(['error' => 'Charge utile invalide'], 400);
        } catch (SignatureVerificationException) {
            return response()->json(['error' => 'Signature invalide'], 400);
        }

        match ($evenement->type) {
            'checkout.session.completed' => $this->surPaiement($evenement->data->object),
            'account.updated'            => $this->surCompte($evenement->data->object),
            default                      => null,
        };

        return response()->json(['status' => 'ok']);
    }

    /**
     * Deux ventes distinctes passent par le même webhook : la cagnotte d'un
     * mariage, et l'achat d'une formule sur la vitrine. Les métadonnées de la
     * session tranchent.
     */
    private function surPaiement(object $session): void
    {
        if (($session->metadata->type ?? null) === 'commande') {
            CommandeController::surPaiementConfirme($session);

            return;
        }

        $don = UrneDon::tousEvenements()->where('transaction_id', $session->id)->first();

        if ($don && $don->statut !== 'payé') {
            $this->confirmer($don, $session->payment_intent ?? null);
        }
    }

    private function surCompte(object $compte): void
    {
        if ($event = Event::where('stripe_account_id', $compte->id)->first()) {
            $this->stripe->rafraichir($event);
        }
    }

    private function confirmer(UrneDon $don, ?string $paymentIntent): void
    {
        $don->update([
            'statut'                => 'payé',
            'paye_at'               => now(),
            'stripe_payment_intent' => $paymentIntent,
        ]);

        Log::info('Cagnotte : don confirmé', ['don' => $don->id, 'event' => $don->event_id]);

        $this->remercier($don);
    }

    /**
     * Remerciement à l'invité. Silencieux en cas d'échec : un serveur de
     * messagerie en panne ne doit pas remettre en cause un don encaissé.
     */
    private function remercier(UrneDon $don): void
    {
        $email = $don->participant?->email;
        $event = Event::find($don->event_id);

        if (! $email || ! $event) {
            return;
        }

        try {
            Mail::to($email)->send(new RecuCagnotte($don, $event));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** @return list<int> */
    private function montantsSuggeres(Event $event): array
    {
        return collect(explode(',', (string) $event->reglage('cagnotte', 'montants_suggeres', '20, 50, 100, 200')))
            ->map(fn ($m) => (int) trim($m))
            ->filter()
            ->values()
            ->all();
    }
}
