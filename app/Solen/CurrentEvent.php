<?php

namespace App\Solen;

use App\Models\Event;

/**
 * Le locataire courant, pour la durée d'une requête.
 *
 * Enregistré en singleton. Tout le reste de l'application — le scope global,
 * les vues, les contrôleurs — passe par ici plutôt que de deviner l'événement
 * à partir de l'URL.
 */
class CurrentEvent
{
    protected ?Event $event = null;

    /**
     * L'événement a-t-il été résolu par son adresse (sous-domaine ou domaine
     * personnalisé) plutôt que par le repli de développement ?
     *
     * C'est ce qui distingue « on est sur le site d'un mariage » de « on est
     * sur solen.app », et donc ce que sert la racine du site.
     */
    protected bool $strict = false;

    public function set(?Event $event, bool $strict = false): void
    {
        $this->event  = $event;
        $this->strict = $strict && $event !== null;
    }

    /** Sommes-nous réellement sur le site d'un mariage ? */
    public function estSurUnMariage(): bool
    {
        return $this->strict;
    }

    public function get(): ?Event
    {
        return $this->event;
    }

    public function id(): ?int
    {
        return $this->event?->id;
    }

    public function exists(): bool
    {
        return $this->event !== null;
    }

    public function forget(): void
    {
        $this->event  = null;
        $this->strict = false;
    }

    /**
     * Exécute un traitement dans le contexte d'un autre événement, puis
     * restaure le précédent. Indispensable aux seeders et aux tâches de fond
     * qui balaient plusieurs mariages.
     */
    public function pretend(?Event $event, callable $callback): mixed
    {
        $previous = $this->event;
        $this->event = $event;

        try {
            return $callback();
        } finally {
            $this->event = $previous;
        }
    }
}
