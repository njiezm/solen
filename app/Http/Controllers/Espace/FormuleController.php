<?php

namespace App\Http\Controllers\Espace;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Plan;
use App\Solen\CurrentEvent;
use App\Solen\StripeConnect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

/**
 * Monter de formule depuis l'espace : le couple ne paie que la différence.
 *
 * Rien n'est retiré ni écrasé : les modules de la nouvelle formule
 * s'ajoutent à ceux déjà réglés. La suite (activation, facture) passe par
 * le même tunnel que la commande initiale (CommandeController).
 */
class FormuleController extends Controller
{
    public function __construct(
        private readonly CurrentEvent $courant,
        private readonly StripeConnect $stripe,
    ) {
    }

    public function index(): View
    {
        $event = $this->courant->get();
        $actuelle = Plan::where('cle', $event->plan)->first();
        $catalogue = collect(config('solen.plans'))->keyBy('key');

        return view('espace.formule', [
            'actuelle'  => $actuelle,
            'details'   => $catalogue,
            'superieures' => Plan::actifs()->get()
                ->filter(fn (Plan $p) => $p->prix > ($actuelle?->prix ?? 0))
                ->map(fn (Plan $p) => [
                    'plan'       => $p,
                    'difference' => $p->prix - ($actuelle?->prix ?? 0),
                    'nouveautes' => $p->modules()->whereNotIn('modules.id', $actuelle?->modules()->pluck('modules.id') ?? [])->get(),
                ])
                ->values(),
            'paiement'  => $this->stripe->configure(),
        ]);
    }

    public function payer(Request $request, string $plan): RedirectResponse
    {
        $event = $this->courant->get();
        $actuelle = Plan::where('cle', $event->plan)->firstOrFail();
        $cible = Plan::actifs()->where('cle', $plan)->firstOrFail();

        abort_unless($cible->prix > $actuelle->prix, 422, 'Cette formule n’est pas supérieure à la vôtre.');

        if (! $this->stripe->configure()) {
            return back()->with('erreur', 'Le paiement en ligne est momentanément indisponible. Écrivez-nous : nous faisons le changement pour vous.');
        }

        $commande = Commande::create([
            'type'             => 'montee',
            'plan'             => $cible->cle,
            'plan_origine'     => $actuelle->cle,
            'event_id'         => $event->id,
            'nom'              => $event->nom,
            'email'            => $request->user()->email,
            'timezone'         => $event->timezone,
            'type_ceremonie'   => 'civil',
            'montant_centimes' => ($cible->prix - $actuelle->prix) * 100,
            'statut'           => Commande::EN_ATTENTE,
        ]);

        try {
            $session = $this->stripe->client()->checkout->sessions->create([
                'mode'                 => 'payment',
                'customer_email'       => $commande->email,
                'client_reference_id'  => $commande->uuid,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'quantity'   => 1,
                    'price_data' => [
                        'currency'     => 'eur',
                        'unit_amount'  => $commande->montant_centimes,
                        'product_data' => ['name' => "Solen — passage de {$actuelle->nom} à {$cible->nom}"],
                    ],
                ]],
                'metadata'    => ['type' => 'commande', 'commande' => $commande->uuid],
                'success_url' => route('commande.merci', $commande->uuid) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('espace.formule', $event->slug),
            ]);
        } catch (ApiErrorException $e) {
            report($e);
            $commande->update(['statut' => Commande::ANNULEE]);

            return back()->with('erreur', 'Le paiement n’a pas pu être préparé. Merci de réessayer.');
        }

        $commande->update(['stripe_session_id' => $session->id]);

        return redirect()->away($session->url);
    }
}
