<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Rend l'accès à la console quand le mot de passe super-admin est perdu.
 *
 * Le mot de passe initial n'est affiché qu'une fois, au premier seed : sans
 * cette commande, le perdre obligeait à passer par la base. Elle exige un
 * accès au serveur, ce qui vaut preuve d'identité.
 *
 * Crée le compte s'il n'existe pas, le promeut super-admin sinon, et lui
 * donne un nouveau mot de passe, affiché une seule fois.
 */
class AccesAdmin extends Command
{
    protected $signature = 'solen:admin
                            {email? : Adresse du compte (défaut : SOLEN_ADMIN_EMAIL)}';

    protected $description = 'Crée ou réinitialise le compte super-admin de la console.';

    public function handle(): int
    {
        $email = $this->argument('email') ?: env('SOLEN_ADMIN_EMAIL', 'contact@solen.app');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Adresse invalide : {$email}");

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user && ! $user->estSuperAdmin()
            && ! $this->confirm("{$email} existe mais n'est pas super-admin. Lui donner l'accès à la console ?")) {
            return self::FAILURE;
        }

        // Sans symboles : la console Symfony interprète `<...>` comme une
        // balise de style et mutilerait l'affichage.
        $motDePasse = Str::password(20, symbols: false);

        $user ??= new User(['name' => 'Équipe Solen', 'email' => $email]);
        $user->password = Hash::make($motDePasse);
        $user->role     = User::ROLE_SUPER_ADMIN;
        $user->save();

        $this->warn('──────────────────────────────────────────────');
        $this->warn("  Console : " . url('/console'));
        $this->warn("  Compte : {$email}");
        $this->warn("  Mot de passe : {$motDePasse}");
        $this->warn('  Notez-le maintenant, il ne sera plus affiché.');
        $this->warn('──────────────────────────────────────────────');

        return self::SUCCESS;
    }
}
