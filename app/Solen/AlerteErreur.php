<?php

namespace App\Solen;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Prévient l'équipe Solen quand une page plante en production.
 *
 * Deux garde-fous indispensables :
 *  · un même incident n'alerte qu'une fois par heure — un bug sur une page
 *    populaire enverrait sinon des centaines d'e-mails et ferait blacklister
 *    le domaine ;
 *  · les erreurs courantes (404, validation, session expirée) sont ignorées :
 *    elles ne signalent rien d'anormal.
 */
class AlerteErreur
{
    /** Exceptions qui n'ont rien d'un incident. */
    private const IGNOREES = [
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException::class,
        \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
        \Illuminate\Validation\ValidationException::class,
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
    ];

    public function signaler(Throwable $e): void
    {
        if (! $this->merite($e)) {
            return;
        }

        // Une empreinte par lieu d'erreur, pas par occurrence.
        $empreinte = 'alerte:' . md5(get_class($e) . $e->getFile() . $e->getLine());

        if (! Cache::add($empreinte, true, now()->addHour())) {
            return;
        }

        try {
            Mail::to(config('solen.brand.email'))->send(
                new class ($e) extends Mailable {
                    public function __construct(public Throwable $erreur)
                    {
                    }

                    public function envelope(): Envelope
                    {
                        return new Envelope(
                            subject: 'Erreur en production — ' . class_basename($this->erreur),
                        );
                    }

                    public function content(): Content
                    {
                        return new Content(view: 'emails.alerte-erreur', with: [
                            'erreur'  => $this->erreur,
                            'url'     => request()?->fullUrl(),
                            'methode' => request()?->method(),
                            'user'    => auth()->user()?->email,
                            'trace'   => collect(explode("\n", $this->erreur->getTraceAsString()))
                                ->take(12)->implode("\n"),
                        ]);
                    }
                }
            );
        } catch (Throwable $envoi) {
            // Surtout ne pas relancer : une alerte qui plante masquerait
            // l'erreur d'origine.
            Log::error('Alerte d’erreur non envoyée : ' . $envoi->getMessage());
        }
    }

    private function merite(Throwable $e): bool
    {
        if (app()->environment('local', 'testing')) {
            return false;
        }

        if (! config('solen.brand.email')) {
            return false;
        }

        foreach (self::IGNOREES as $classe) {
            if ($e instanceof $classe) {
                return false;
            }
        }

        return true;
    }
}
