<?php

namespace App\Providers;

use App\Solen\CurrentEvent;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Le locataire courant, partagé par tout ce qui tourne dans la requête.
        $this->app->singleton(CurrentEvent::class);
    }

    public function boot(): void
    {
        // Les liens de paiement et les QR codes doivent être en https hors local.
        if (! $this->app->environment('local')) {
            URL::forceScheme('https');
        }

        // <x-courriel::layout> → resources/views/emails/layout.blade.php.
        // L'espace `mail::` est réservé aux e-mails markdown de Laravel.
        Blade::anonymousComponentNamespace('emails', 'courriel');

        $this->traduireLeLienDeReinitialisation();
    }

    /**
     * Laravel envoie son e-mail de réinitialisation en anglais, avec sa mise
     * en page par défaut. On le remplace par le gabarit Solen.
     */
    private function traduireLeLienDeReinitialisation(): void
    {
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            $minutes = config('auth.passwords.users.expire', 60);

            return (new MailMessage)
                ->subject('Votre nouveau mot de passe Solen')
                ->view('emails.mot-de-passe', [
                    'url'      => url($url),
                    'minutes'  => $minutes,
                    'user'     => $notifiable,
                ]);
        });
    }
}
