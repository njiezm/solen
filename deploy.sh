#!/usr/bin/env bash
#
# Déploiement de Solen sur le serveur.
#
# À lancer depuis la racine du projet, sur le serveur :
#   ./deploy.sh
#
# Ou automatiquement, par un webhook de dépôt / une action GitHub.
# La commande artisan solen:deployer fait le gros du travail ; ce script
# gère ce qui vit en dehors de PHP : git, composer, npm et la sauvegarde.

set -euo pipefail

BRANCHE="${1:-main}"
PHP="${PHP_BIN:-php}"
COMPOSER="${COMPOSER_BIN:-composer}"
DOSSIER_SAUVEGARDES="${DOSSIER_SAUVEGARDES:-storage/app/sauvegardes}"

bleu()  { printf '\033[1;36m▸ %s\033[0m\n' "$1"; }
vert()  { printf '\033[0;32m✓ %s\033[0m\n' "$1"; }
rouge() { printf '\033[0;31m✗ %s\033[0m\n' "$1" >&2; }

trap 'rouge "Échec à la ligne $LINENO. Le site peut être resté en maintenance : php artisan up"' ERR

# ── 1. Sauvegarde ────────────────────────────────────────────────────────
# Non négociable : les migrations touchent la structure de 20 tables.
bleu "Sauvegarde de la base"
mkdir -p "$DOSSIER_SAUVEGARDES"
HORODATAGE="$(date +%Y%m%d-%H%M%S)"
FICHIER="$DOSSIER_SAUVEGARDES/avant-deploiement-$HORODATAGE.dump"

DB_DATABASE="$(grep -E '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"')"
DB_USERNAME="$(grep -E '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"')"
DB_HOST="$(grep -E '^DB_HOST=' .env | cut -d= -f2- | tr -d '"')"

if command -v pg_dump >/dev/null 2>&1; then
    PGPASSWORD="$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '"')" \
        pg_dump -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -F c -f "$FICHIER"
    vert "Sauvegarde écrite dans $FICHIER"

    # On garde les dix dernières, pas plus : le disque n'est pas infini.
    ls -1t "$DOSSIER_SAUVEGARDES"/*.dump 2>/dev/null | tail -n +11 | xargs -r rm --
else
    rouge "pg_dump introuvable — déploiement interrompu, sauvegarde impossible."
    exit 1
fi

# ── 2. Code ──────────────────────────────────────────────────────────────
bleu "Récupération du code ($BRANCHE)"
git fetch --all --prune
git checkout "$BRANCHE"
git reset --hard "origin/$BRANCHE"
vert "$(git log -1 --pretty='%h %s')"

# ── 3. Dépendances ───────────────────────────────────────────────────────
bleu "Dépendances PHP"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction --prefer-dist

if [ -f package.json ] && command -v npm >/dev/null 2>&1; then
    bleu "Dépendances front"
    npm ci --silent
    npm run build --silent
fi

# ── 4. Application ───────────────────────────────────────────────────────
bleu "Migrations, catalogue et caches"
$PHP artisan solen:deployer

# ── 5. Files d'attente ───────────────────────────────────────────────────
# Les workers tournent sur l'ancien code tant qu'ils ne sont pas relancés.
bleu "Redémarrage des files d'attente"
$PHP artisan queue:restart

vert "Déploiement terminé — $(git log -1 --pretty='%h')"
