<?php

namespace App\Solen;

use App\Models\Event;
use App\Models\Sauvegarde;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Le système tampon des remises à zéro.
 *
 * Avant d'effacer, on met de côté : les lignes, exactement telles qu'en
 * base (identifiants compris), et les fichiers, déplacés du disque public
 * vers un dossier privé. Restaurer remet les unes et les autres à leur
 * place. Rien n'est perdu sur un clic malheureux.
 */
class Corbeille
{
    /**
     * Vide les jeux du mariage : scores, réponses, chasse photo, et les
     * participants qui n'existaient que pour jouer.
     */
    public function viderJeux(Event $event): Sauvegarde
    {
        $id = $event->id;

        // Ne part que celui qui a vraiment joué, et n'a laissé ni photo, ni
        // mot, ni don. Un invité dont la photo dort dans la corbeille de la
        // galerie n'a rien d'un joueur : il reste.
        $joueurs = DB::table('participants')->where('event_id', $id)
            ->where(fn ($q) => $q
                ->whereExists(fn ($s) => $s->from('scores_jeu')->whereColumn('scores_jeu.participant_id', 'participants.id'))
                ->orWhereExists(fn ($s) => $s->from('reponses_qui_deux')->whereColumn('reponses_qui_deux.participant_id', 'participants.id'))
                ->orWhereExists(fn ($s) => $s->from('chasse_photos')->whereColumn('chasse_photos.participant_id', 'participants.id')))
            ->whereNotExists(fn ($q) => $q->from('photos')->whereColumn('photos.participant_id', 'participants.id'))
            ->whereNotExists(fn ($q) => $q->from('livre_ors')->whereColumn('livre_ors.participant_id', 'participants.id'))
            ->whereNotExists(fn ($q) => $q->from('urne_dons')->whereColumn('urne_dons.participant_id', 'participants.id'));

        // Les auteurs des photos en attente de restauration restent aussi.
        $auteursEnCorbeille = Sauvegarde::where('event_id', $id)->whereIn('quoi', ['galerie', 'photobooth'])
            ->whereNull('restauree_le')->where('created_at', '>', now()->subDays(Sauvegarde::DUREE_JOURS))
            ->get()
            ->flatMap(fn (Sauvegarde $s) => array_column($s->lignes['photos'] ?? [], 'participant_id'))
            ->filter()->unique()->all();

        if ($auteursEnCorbeille) {
            $joueurs->whereNotIn('id', $auteursEnCorbeille);
        }

        // Dans l'ordre de restauration : les parents d'abord.
        $tables = [
            'participants'      => $joueurs,
            'sessions_jeu'      => DB::table('sessions_jeu')->where('event_id', $id),
            'scores_jeu'        => DB::table('scores_jeu')->where('event_id', $id),
            'reponses_qui_deux' => DB::table('reponses_qui_deux')->where('event_id', $id),
            'jeu_qui_deux'      => DB::table('jeu_qui_deux')->where('event_id', $id),
            'chasse_photos'     => DB::table('chasse_photos')->where('event_id', $id),
            'jeu_chasse_photos' => DB::table('jeu_chasse_photos')->where('event_id', $id),
        ];

        $fichiers = DB::table('chasse_photos')->where('event_id', $id)->pluck('photo_path')
            ->concat(DB::table('jeu_chasse_photos')->where('event_id', $id)->pluck('photo_path'));

        return $this->mettreDeCote($event, 'jeux', 'Jeux : scores, réponses et chasse photo', $tables, $fichiers, 'scores_jeu');
    }

    /** Vide la galerie (photos partagées) ou les clichés du photobooth. */
    public function viderPhotos(Event $event, bool $photobooth): Sauvegarde
    {
        $requete = DB::table('photos')->where('event_id', $event->id)
            ->where('path', $photobooth ? 'like' : 'not like', '%/photobooth/%');

        return $this->mettreDeCote(
            $event,
            $photobooth ? 'photobooth' : 'galerie',
            $photobooth ? 'Photos du photobooth' : 'Photos de la galerie',
            ['photos' => $requete],
            (clone $requete)->pluck('path'),
            'photos',
        );
    }

    /**
     * @param  array<string, \Illuminate\Database\Query\Builder>  $tables  dans l'ordre de restauration
     * @param  Collection<int, string>  $fichiers  chemins sur le disque public
     */
    private function mettreDeCote(Event $event, string $quoi, string $libelle, array $tables, Collection $fichiers, string $compte): Sauvegarde
    {
        $sauvegarde = DB::transaction(function () use ($event, $quoi, $libelle, $tables, $fichiers, $compte) {
            $lignes = collect($tables)->map(fn ($q) => (clone $q)->get()->map(fn ($l) => (array) $l)->all())->all();

            $sauvegarde = Sauvegarde::create([
                'event_id' => $event->id,
                'quoi'     => $quoi,
                'libelle'  => $libelle,
                'lignes'   => $lignes,
                'fichiers' => $fichiers->filter()->values()->all(),
                'nombre'   => count($lignes[$compte] ?? []),
            ]);

            // Les enfants avant les parents : l'ordre inverse de restauration.
            foreach (array_reverse(array_keys($tables)) as $table) {
                $ids = array_column($lignes[$table], 'id');
                if ($ids) {
                    DB::table($table)->whereIn('id', $ids)->delete();
                }
            }

            return $sauvegarde;
        });

        // Les fichiers, une fois la base à jour : déplacés, pas supprimés.
        foreach ($sauvegarde->fichiers ?? [] as $chemin) {
            if (Storage::disk('public')->exists($chemin)) {
                Storage::disk('local')->put($sauvegarde->dossier() . '/' . $chemin, Storage::disk('public')->get($chemin));
                Storage::disk('public')->delete($chemin);
            }
        }

        return $sauvegarde;
    }

    /** Remet tout en place : les lignes, puis les fichiers. */
    public function restaurer(Sauvegarde $sauvegarde): int
    {
        $restaurees = DB::transaction(function () use ($sauvegarde) {
            $n = 0;

            foreach ($sauvegarde->lignes as $table => $lignes) {
                // Une ligne dont l'identifiant existe de nouveau n'est pas
                // écrasée : la restauration complète, elle ne remplace pas.
                $existants = DB::table($table)->whereIn('id', array_column($lignes, 'id'))->pluck('id')->all();

                // Une photo dont l'auteur a disparu revient quand même, sans
                // nom : mieux qu'une restauration qui échoue.
                if ($table === 'photos') {
                    $auteurs = DB::table('participants')->whereIn('id', array_filter(array_column($lignes, 'participant_id')))->pluck('id')->all();
                    $lignes = array_map(fn ($l) => $l['participant_id'] && ! in_array($l['participant_id'], $auteurs) ? ['participant_id' => null] + $l : $l, $lignes);
                }

                foreach (array_chunk(array_filter($lignes, fn ($l) => ! in_array($l['id'], $existants)), 200) as $paquet) {
                    DB::table($table)->insert($paquet);
                    $n += count($paquet);
                }

                // Postgres : la séquence ne doit pas reproposer un identifiant restauré.
                if (DB::getDriverName() === 'pgsql' && $lignes) {
                    DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), GREATEST((SELECT MAX(id) FROM {$table}), 1))");
                }
            }

            $sauvegarde->update(['restauree_le' => now()]);

            return $n;
        });

        foreach ($sauvegarde->fichiers ?? [] as $chemin) {
            $cache = $sauvegarde->dossier() . '/' . $chemin;
            if (Storage::disk('local')->exists($cache)) {
                Storage::disk('public')->put($chemin, Storage::disk('local')->get($cache));
            }
        }
        Storage::disk('local')->deleteDirectory($sauvegarde->dossier());

        return $restaurees;
    }

    /** Supprime pour de bon les sauvegardes expirées ou déjà restaurées. */
    public function purger(): int
    {
        $anciennes = Sauvegarde::where('created_at', '<', now()->subDays(Sauvegarde::DUREE_JOURS))
            ->orWhereNotNull('restauree_le')
            ->get();

        foreach ($anciennes as $s) {
            Storage::disk('local')->deleteDirectory($s->dossier());
            $s->delete();
        }

        return $anciennes->count();
    }
}
