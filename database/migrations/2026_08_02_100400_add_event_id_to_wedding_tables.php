<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Bascule mono-mariage → multi-locataire.
 *
 * Toutes les tables métier reçoivent un event_id. Les données existantes
 * (le mariage Maëva & Gilles du 26.12.2025) sont rattachées à l'événement
 * par défaut, puis la colonne devient obligatoire.
 *
 * qr_code_scans est volontairement exclue : elle est toujours atteinte via
 * son qr_code, déjà porté par un événement, et son volume ne justifie pas
 * la dénormalisation.
 */
return new class extends Migration
{
    /** Tables métier à rattacher à un événement. */
    private const TABLES = [
        'participants',
        'livre_ors',
        'photos',
        'urne_dons',
        'jeu_qui_deux',
        'jeu_chasse_photos',
        'questions_qui_deux',
        'sessions_jeu',
        'reponses_qui_deux',
        'chasse_photos',
        'etape_ceremonies',
        'mots_croises',
        'mots',
        'memory_cards',
        'lectures',
        'chants',
        'prieres',
        'remerciements',
        'qr_codes',
    ];

    public function up(): void
    {
        // 1. Colonne nullable, le temps du rattachement.
        foreach ($this->presentTables() as $table) {
            if (Schema::hasColumn($table, 'event_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('event_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }

        // Une étape de déroulé appartient en plus à une partie du mariage
        // (mairie, cérémonie, soirée…). Nullable : les étapes existantes
        // seront rattachées par le seeder.
        if (Schema::hasTable('etape_ceremonies') && ! Schema::hasColumn('etape_ceremonies', 'event_part_id')) {
            Schema::table('etape_ceremonies', function (Blueprint $t) {
                $t->foreignId('event_part_id')->nullable()->constrained('event_parts')->nullOnDelete();
            });
        }

        // 2. Rattachement des données existantes.
        $eventId = $this->defaultEventId();

        foreach ($this->presentTables() as $table) {
            DB::table($table)->whereNull('event_id')->update(['event_id' => $eventId]);
        }

        // 3. La colonne devient obligatoire et indexée.
        foreach ($this->presentTables() as $table) {
            $this->setNotNull($table);

            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->index('event_id', "{$table}_event_id_idx");
            });
        }
    }

    public function down(): void
    {
        foreach ($this->presentTables() as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropIndex("{$table}_event_id_idx");
                $t->dropConstrainedForeignId('event_id');
            });
        }

        if (Schema::hasTable('etape_ceremonies') && Schema::hasColumn('etape_ceremonies', 'event_part_id')) {
            Schema::table('etape_ceremonies', function (Blueprint $t) {
                $t->dropConstrainedForeignId('event_part_id');
            });
        }
    }

    /** @return list<string> */
    private function presentTables(): array
    {
        return array_values(array_filter(self::TABLES, fn ($t) => Schema::hasTable($t)));
    }

    /**
     * L'événement auquel rattacher l'historique : le mariage d'origine.
     * Créé ici plutôt qu'en seeder pour que `migrate` seul laisse une base
     * cohérente, y compris en production.
     */
    private function defaultEventId(): int
    {
        $existing = DB::table('events')->orderBy('id')->value('id');

        if ($existing) {
            return $existing;
        }

        return DB::table('events')->insertGetId([
            'uuid'            => (string) Str::uuid(),
            'slug'            => 'maeva-gilles',
            'nom'             => 'Maëva & Gilles',
            'partenaire_1'    => 'Maëva',
            'partenaire_2'    => 'Gilles',
            'date_principale' => '2025-12-26',
            'timezone'        => 'America/Martinique',
            'lieu_ville'      => 'Le Lamentin',
            'lieu_pays'       => 'Martinique',
            'plan'            => 'signature',
            'statut'          => 'publie',
            'est_demo'        => false,
            'publie_at'       => now(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    /**
     * PostgreSQL n'a pas besoin de doctrine/dbal pour un simple SET NOT NULL,
     * et l'ordre explicite évite que Laravel ne recrée la clé étrangère.
     */
    private function setNotNull(string $table): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE \"{$table}\" ALTER COLUMN event_id SET NOT NULL");

            return;
        }

        Schema::table($table, function (Blueprint $t) {
            $t->unsignedBigInteger('event_id')->nullable(false)->change();
        });
    }
};
