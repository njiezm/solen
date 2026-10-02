# Déploiement de Solen

Cette mise à jour transforme un site mono-mariage en plateforme multi-mariages.
Elle **modifie la structure de la base** et **change toutes les URL**. À lire en
entier avant de commencer.

---

## Avant tout : la sauvegarde

Non négociable. La migration ajoute une colonne obligatoire à 19 tables et
rattache les données existantes.

```bash
pg_dump -U postgres -d weeding -F c -f sauvegarde-avant-solen.dump
```

Vérifiez que le fichier n'est pas vide avant d'aller plus loin.

---

## Ce qui change pour les visiteurs

| Avant | Après |
|---|---|
| `/` | `/mariage/maeva-gilles` |
| `/home` | `/mariage/maeva-gilles/accueil` |
| `/galerie` | `/mariage/maeva-gilles/galerie` |
| `/livre-or` | `/mariage/maeva-gilles/livre-or` |
| `/details-pratiques` | `/mariage/maeva-gilles/infos-pratiques` |
| `/pensée-pour` | `/mariage/maeva-gilles/hommage` |
| `/admin190964` | `/mariage/maeva-gilles/admin` — **authentification requise** |
| `/maries` | `/mariage/maeva-gilles/maries` — **authentification requise** |
| — | `/` sert désormais la vitrine Solen |

**Les QR codes déjà imprimés continuent de fonctionner** : `/qr/track/{uuid}`
n'a pas changé. En revanche, ceux dont l'URL de destination pointe vers une
ancienne adresse doivent être mis à jour depuis l'administration — la
redirection se fait côté base, pas côté papier.

---

---

## Déploiement automatique (la voie normale)

Trois façons de déclencher exactement la même chose :

| Depuis | Comment |
|---|---|
| **Un push sur `main`** | [.github/workflows/deploiement.yml](.github/workflows/deploiement.yml) s'en charge : contrôles, puis SSH sur le serveur |
| **Le serveur, à la main** | `./deploy.sh` — git, composer, npm, puis la commande ci-dessous |
| **L'application seule** | `php artisan solen:deployer` — migrations, catalogue, caches |

### Ce que fait `solen:deployer`

Mise en maintenance → purge des caches → migrations → thèmes → catalogue des
modules et formules → lien de stockage → reconstruction des caches → remise en
ligne → vérifications.

Il **refuse de rejouer les seeders de contenu** si la base contient déjà des
blocs : c'est la protection contre la duplication de données. Pour forcer,
`--forcer-contenu` (destructif).

```bash
php artisan solen:deployer --simulation    # montre tout, n'exécute rien
php artisan solen:deployer                 # applique
php artisan solen:deployer --sans-maintenance
```

Il refuse aussi de démarrer si la base est injoignable, si `APP_KEY` manque,
ou si `APP_DEBUG` est actif en production. Il avertit — sans bloquer — quand
les clés Stripe sont absentes.

### Secrets à définir dans GitHub

`SSH_HOTE`, `SSH_UTILISATEUR`, `SSH_CLE`, `SSH_PORT` (facultatif),
`CHEMIN_APPLICATION`, `URL_APPLICATION`.

Le workflow ne déploie que si les contrôles passent, et vérifie ensuite que le
site répond en 200 — six tentatives espacées de dix secondes.

---

## Déploiement manuel (dépannage)

### 1. Récupérer le code et les dépendances

```bash
git pull
composer install --no-dev --optimize-autoloader
```

### 2. Ajuster l'environnement

Dans le `.env` de production :

```dotenv
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr

# Compte organisateur créé au premier seeding. À définir AVANT de lancer
# les seeders, sinon un mot de passe est généré et affiché une seule fois.
SOLEN_ADMIN_EMAIL=votre@adresse.fr
SOLEN_ADMIN_PASSWORD=un-mot-de-passe-long-et-unique
```

L'adressage se fait par chemin (`/mariage/{slug}`) : ni DNS joker, ni
certificat par client, ni vhost à créer à chaque vente.

Pour la cagnotte, **Stripe Connect doit être activé** sur votre compte
(Paramètres → Connect → activer les comptes Express) :

```dotenv
STRIPE_KEY=pk_live_…
STRIPE_SECRET=sk_live_…
STRIPE_WEBHOOK_SECRET=whsec_…
```

Le webhook Stripe pointe sur `/webhook/stripe` et doit écouter
`checkout.session.completed` et `account.updated`.

### 3. Migrer

```bash
php artisan migrate --force
```

Sept migrations s'exécutent. Celle nommée `add_event_id_to_wedding_tables`
crée l'événement « Maëva & Gilles », rattache toutes les données existantes,
puis rend la colonne obligatoire. Elle est la plus longue.

### 4. Charger le catalogue et le contenu

```bash
php artisan db:seed --class=ThemeSeeder --force
php artisan db:seed --class=CatalogueSeeder --force
php artisan db:seed --class=EvenementInitialSeeder --force
php artisan db:seed --class=ContenuInitialSeeder --force
```

> **N'exécutez pas `php artisan db:seed` tout court** : la commande appellerait
> aussi les seeders de contenu du mariage (questions, cérémonie, mots croisés)
> et dupliquerait des données déjà en base.

Notez le mot de passe affiché si vous n'avez pas défini `SOLEN_ADMIN_PASSWORD`.
Il ne sera plus jamais montré.

### 5. Finaliser

```bash
php artisan storage:link      # si ce n'est pas déjà fait
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6. Vérifier

Dans cet ordre :

1. `/` affiche la vitrine Solen.
2. `/mariage/maeva-gilles` affiche la page d'entrée du mariage.
3. `/mariage/maeva-gilles/accueil` affiche les tuiles, le compte à rebours et
   le code vestimentaire.
4. `/mariage/maeva-gilles/infos-pratiques` affiche les hôtels et le contact.
5. `/mariage/maeva-gilles/espace` **redirige vers la connexion** — c'est le
   point le plus important : ces pages étaient publiques jusqu'ici.
6. Connectez-vous, puis modifiez le code vestimentaire depuis
   `/espace/modules/site` et vérifiez qu'il change sur l'accueil.

---

## En cas de problème

### Revenir en arrière

```bash
php artisan down
pg_restore -U postgres -d weeding --clean --if-exists sauvegarde-avant-solen.dump
git checkout <commit-précédent>
composer install --no-dev
php artisan optimize:clear
php artisan up
```

### Les pages affichent « Class "Route" not found »

Le cache de configuration est périmé : `php artisan config:clear` puis
`php artisan config:cache`.

### Une page renvoie 404

Le slug du mariage est incorrect, ou l'événement est archivé. Vérifiez :

```bash
php artisan tinker --execute='App\Models\Event::all(["slug","statut"])->each(fn($e)=>print("$e->slug : $e->statut\n"));'
```

### Le site du mariage est invisible pour les invités

Son statut est `brouillon`. Passez-le à `publie` depuis
`/mariage/{slug}/espace/informations`.

---

## Après le déploiement

- Changez le mot de passe du compte organisateur si vous l'avez laissé générer.
- Mettez à jour les QR codes dont la destination pointait vers une ancienne URL.
- Le webhook Stripe reste sur `/webhook/stripe`, rien à changer côté Stripe.
