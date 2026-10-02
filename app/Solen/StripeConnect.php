<?php

namespace App\Solen;

use App\Models\Event;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * Stripe Connect Express.
 *
 * L'argent de la cagnotte doit arriver sur le compte des mariés, jamais sur
 * celui de la plateforme : encaisser pour le compte d'autrui sans agrément
 * relève de l'établissement de paiement. Chaque mariage possède donc son
 * propre compte Stripe, et Solen ne prélève qu'une commission explicite.
 */
class StripeConnect
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function configure(): bool
    {
        return (bool) config('services.stripe.secret');
    }

    /**
     * Crée le compte du mariage s'il n'existe pas encore.
     *
     * @throws ApiErrorException
     */
    public function compte(Event $event): Account
    {
        if ($event->stripe_account_id) {
            return $this->stripe->accounts->retrieve($event->stripe_account_id);
        }

        $compte = $this->stripe->accounts->create([
            'type'          => 'express',
            'country'       => 'FR',
            'business_type' => 'individual',
            'capabilities'  => [
                'card_payments' => ['requested' => true],
                'transfers'     => ['requested' => true],
            ],
            'business_profile' => [
                'name'          => $event->nom,
                'product_description' => 'Cagnotte de mariage',
                'url'           => route('landing', $event->slug),
            ],
            'metadata' => ['event_id' => $event->id, 'slug' => $event->slug],
        ]);

        $event->update(['stripe_account_id' => $compte->id]);

        return $compte;
    }

    /**
     * Le lien d'inscription (vérification d'identité, coordonnées bancaires).
     * Valable quelques minutes seulement : il se régénère à chaque clic.
     *
     * @throws ApiErrorException
     */
    public function lienInscription(Event $event, string $retour, string $rafraichir): AccountLink
    {
        $compte = $this->compte($event);

        return $this->stripe->accountLinks->create([
            'account'     => $compte->id,
            'refresh_url' => $rafraichir,
            'return_url'  => $retour,
            'type'        => 'account_onboarding',
        ]);
    }

    /**
     * Interroge Stripe et met l'état local à jour.
     * À appeler au retour d'inscription et sur le webhook `account.updated`.
     */
    public function rafraichir(Event $event): ?Account
    {
        if (! $event->stripe_account_id) {
            return null;
        }

        try {
            $compte = $this->stripe->accounts->retrieve($event->stripe_account_id);
        } catch (ApiErrorException) {
            return null;
        }

        $event->update([
            'stripe_paiements_actifs' => (bool) $compte->charges_enabled,
            'stripe_virements_actifs' => (bool) $compte->payouts_enabled,
            'stripe_valide_at'        => $compte->details_submitted ? ($event->stripe_valide_at ?? now()) : null,
        ]);

        return $compte;
    }

    /** Lien vers le tableau de bord Stripe du couple. */
    public function lienTableauDeBord(Event $event): ?string
    {
        if (! $event->stripe_account_id) {
            return null;
        }

        try {
            return $this->stripe->accounts->createLoginLink($event->stripe_account_id)->url;
        } catch (ApiErrorException) {
            return null;
        }
    }

    /** Le mariage peut-il encaisser ? */
    public function pretAEncaisser(Event $event): bool
    {
        return $this->configure()
            && $event->stripe_account_id
            && $event->stripe_paiements_actifs;
    }

    /**
     * La commission Solen sur un don, en centimes.
     * `commission_bps` est en centièmes de pour cent : 250 = 2,5 %.
     */
    public function commission(Event $event, int $montantCentimes): int
    {
        return (int) floor($montantCentimes * $event->commission_bps / 10_000);
    }

    public function client(): StripeClient
    {
        return $this->stripe;
    }
}
