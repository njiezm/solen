<?php

namespace App\Http\Controllers\Espace;

use App\Http\Controllers\Controller;
use App\Models\UrneDon;
use App\Solen\CurrentEvent;
use App\Solen\StripeConnect;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

/**
 * Raccordement de la cagnotte au compte bancaire des mariés.
 */
class PaiementController extends Controller
{
    public function __construct(private readonly StripeConnect $stripe)
    {
    }

    public function index(CurrentEvent $courant): View
    {
        $event = $courant->get();

        // L'état peut avoir changé côté Stripe depuis la dernière visite.
        if ($event->stripe_account_id) {
            $this->stripe->rafraichir($event);
            $event->refresh();
        }

        return view('espace.paiements', [
            'configure'  => $this->stripe->configure(),
            'pret'       => $this->stripe->pretAEncaisser($event),
            'dons'       => UrneDon::with('participant')->latest()->limit(20)->get(),
            'total'      => UrneDon::where('statut', 'payé')->sum('montant'),
            'enAttente'  => UrneDon::where('statut', 'en_attente')->count(),
        ]);
    }

    /** Ouvre le parcours d'inscription Stripe, ou le reprend là où il s'est arrêté. */
    public function inscrire(CurrentEvent $courant): RedirectResponse
    {
        $event = $courant->get();

        if (! $this->stripe->configure()) {
            return back()->with('erreur', 'Stripe n’est pas configuré sur cette installation.');
        }

        try {
            $lien = $this->stripe->lienInscription(
                $event,
                retour: route('espace.paiements', $event->slug),
                rafraichir: route('espace.paiements.inscrire', $event->slug),
            );
        } catch (ApiErrorException $e) {
            report($e);

            return back()->with('erreur', $this->expliquer($e));
        }

        return redirect()->away($lien->url);
    }

    /**
     * Traduit les refus de Stripe en quelque chose d'actionnable.
     *
     * Les messages de Stripe sont en anglais et s'adressent à un
     * développeur : un couple qui lit « You can only create new accounts
     * if you've signed up for Connect » ne sait pas quoi en faire.
     */
    private function expliquer(ApiErrorException $e): string
    {
        $message = $e->getMessage();

        return match (true) {
            str_contains($message, 'signed up for Connect') =>
                'La plateforme n’est pas encore habilitée à ouvrir des cagnottes. '
                . 'Écrivez-nous, nous nous en occupons : ' . config('solen.brand.email'),

            str_contains($message, 'Invalid API Key'),
            str_contains($message, 'No API key') =>
                'La configuration de paiement est incomplète côté plateforme. '
                . 'Prévenez-nous à ' . config('solen.brand.email'),

            str_contains($message, 'testmode'),
            str_contains($message, 'test mode') =>
                'La plateforme est en mode test : les cagnottes réelles ne sont pas encore ouvertes.',

            default => 'Stripe a refusé la demande. Réessayez dans quelques minutes, '
                . 'et écrivez-nous si cela persiste.',
        };
    }

    /** Ouvre le tableau de bord Stripe des mariés (virements, justificatifs). */
    public function tableauDeBord(CurrentEvent $courant): RedirectResponse
    {
        $lien = $this->stripe->lienTableauDeBord($courant->get());

        return $lien
            ? redirect()->away($lien)
            : back()->with('erreur', 'Le compte Stripe n’est pas encore utilisable.');
    }
}
