<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Un compte organisateur : les mariés, un témoin, un wedding planner,
 * ou un membre de l'équipe Solen.
 *
 * Les invités ne sont pas des users — ils vivent dans `participants` et
 * s'identifieront par lien magique.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN  = 'super_admin';
    public const ROLE_ORGANISATEUR = 'organisateur';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'telephone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'derniere_connexion_at' => 'datetime',
            'password'              => 'hashed',
        ];
    }

    /** Les mariages auxquels ce compte a accès. */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function estSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Où envoyer ce compte quand aucune page n'a été demandée.
     *
     * Toutes les routes de l'espace exigent le slug d'un mariage : il n'existe
     * plus de page d'accueil neutre vers laquelle rediriger aveuglément.
     */
    public function accueilApresConnexion(): string
    {
        // L'équipe Solen arrive sur la console, d'où elle voit tous les mariages.
        if ($this->estSuperAdmin()) {
            return route('console.index');
        }

        $event = $this->events()->orderBy('date_principale')->first();

        return $event
            ? route('espace.index', $event->slug)
            : route('solen.landing');
    }

    /** Ce compte peut-il administrer ce mariage ? */
    public function peutGerer(Event $event): bool
    {
        return $this->estSuperAdmin()
            || $this->events()->whereKey($event->getKey())->exists();
    }

    /** Propriétaire du mariage, par opposition à simple collaborateur. */
    public function estProprietaire(Event $event): bool
    {
        return $this->events()
            ->whereKey($event->getKey())
            ->wherePivot('role', 'proprietaire')
            ->exists();
    }
}
