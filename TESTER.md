# Tester Solen de bout en bout

Guide de recette. Suivre les parcours dans l'ordre : chacun s'appuie sur le
précédent.

---

## Avant de commencer

Dans `.env`, en **mode test** (clés commençant par `pk_test_` et `sk_test_`) :

```dotenv
STRIPE_KEY=pk_test_…
STRIPE_SECRET=sk_test_…
STRIPE_WEBHOOK_SECRET=whsec_…
```

Les clés se trouvent sur <https://dashboard.stripe.com/test/apikeys>.
Vérifiez que l'interrupteur **« Mode test »** est bien activé en haut à droite
du tableau de bord Stripe — sinon vous encaisseriez de vrais paiements.

### Activer Connect — obligatoire avant toute cagnotte

Sans cette étape, Stripe refuse de créer les comptes des mariés avec le
message *« You can only create new accounts if you've signed up for
Connect »*. Ce n'est pas un défaut de l'application, c'est un réglage de
votre compte.

1. Ouvrir <https://dashboard.stripe.com/connect/overview>
2. Cliquer sur **Get started** / **Commencer**
3. Choisir **Platform or marketplace**
4. Type de comptes : **Express**
5. Renseigner le nom public (« Solen »), le site et l'adresse de support —
   c'est ce que verront les mariés pendant leur inscription

En mode test, l'activation est immédiate. En mode réel, Stripe vérifie
votre entité : comptez un à deux jours ouvrés.

### Recevoir les webhooks en local

Sans cela, un paiement fermé trop vite ne créera pas le mariage.

```bash
stripe login
stripe listen --forward-to http://weedingmetg.test/webhook/stripe
```

La commande affiche un `whsec_…` : c'est lui qu'il faut mettre dans
`STRIPE_WEBHOOK_SECRET`, puis `php artisan config:clear`.

---

## Cartes de test Stripe

Toutes acceptent **n'importe quelle date d'expiration future**, **n'importe
quel CVC à 3 chiffres** et **n'importe quel code postal**.

### Paiements qui réussissent

| Numéro | Ce que ça teste |
|---|---|
| `4242 4242 4242 4242` | Visa, le cas nominal |
| `5555 5555 5555 4444` | Mastercard |
| `4000 0025 0000 3155` | **Authentification 3D Secure** — une fenêtre s'ouvre, cliquez sur « Complete » |
| `4000 0000 0000 3220` | 3D Secure obligatoire |

### Paiements qui échouent

| Numéro | Ce que ça teste |
|---|---|
| `4000 0000 0000 0002` | Carte refusée — vous devez revenir sur la page de commande |
| `4000 0000 0000 9995` | Fonds insuffisants |
| `4000 0000 0000 0069` | Carte expirée |
| `4000 0000 0000 0127` | CVC incorrect |
| `4100 0000 0000 0019` | Paiement bloqué pour fraude |

### Cartes européennes

| Numéro | Pays |
|---|---|
| `4000 0025 0000 1001` | France |
| `4000 0005 6000 0008` | Belgique |
| `4000 0075 6000 0009` | Suisse |

---

## Parcours 1 — Acheter une formule

1. Ouvrir `/` — la vitrine Solen.
2. Cliquer sur **Choisir Célébration** dans les tarifs.
3. Sur la page de commande, changer de formule : le récapitulatif, le total et
   **l'adresse dans la barre du navigateur** doivent suivre.
4. Remplir le formulaire, cocher la case, valider.
5. Payer avec `4242 4242 4242 4242`.
6. Vous arrivez sur la page de remerciement, avec **l'e-mail et le mot de
   passe** du nouveau compte. Notez-les : ils ne seront plus affichés.

**À vérifier ensuite**, dans `/console` : le mariage existe, sa formule est la
bonne, ses moments de journée et son déroulé sont pré-remplis selon le type de
cérémonie choisi.

**Le cas d'échec** : recommencer avec `4000 0000 0000 0002`. Aucun mariage ne
doit être créé, et la commande doit apparaître en « annulée ».

**La formule gratuite** ne passe pas par Stripe : le mariage est créé
immédiatement.

---

## Parcours 2 — Configurer son mariage

Connectez-vous avec les identifiants obtenus.

1. **Informations** — dates, lieu, fuseau, thème, puis passer le site en
   « Publié ».
2. **Pages et contenu** — ajouter une étape d'histoire, un hôtel, un plat.
   Chaque ajout doit apparaître immédiatement sur le site invité.
3. **Modules** — désactiver le livre d'or : sa tuile doit disparaître de la
   page d'accueil du site invité. Le réactiver.
4. **Réglages d'un module** — changer le code vestimentaire dans le module
   « Mini-site invité », vérifier sur l'accueil.

---

## Parcours 3 — Le photobooth

Depuis le site invité, `/mariage/{slug}/photobooth`.

- Le navigateur demande l'accès à la caméra : accepter.
- Compte à rebours, flash, aperçu, puis **Envoyer aux mariés**.
- La photo doit apparaître dans la galerie et dans l'espace des mariés.

**Mode borne** : uniquement sur la formule Signature. L'interface passe en
plein écran sans navigation, et enchaîne automatiquement d'un invité à l'autre.
Pour tester, passer le mariage en Signature depuis `/console`.

> En local sur `http://`, Chrome n'autorise la caméra que sur `localhost`.
> Sur `weedingmetg.test`, il faut du HTTPS. Utilisez
> `php artisan serve` puis `http://127.0.0.1:8000/…` pour tester la caméra.

---

## Parcours 4 — La cagnotte

1. Dans l'espace des mariés : **Cagnotte** → **Commencer**.
2. Stripe demande une identité et un IBAN. En mode test, utiliser :
   - **IBAN** : `FR1420041010050500013M02606`
   - **SIREN / identité** : n'importe quelles valeurs, Stripe les accepte en test
   - Sur la page de vérification, un bouton **« Skip this step »** apparaît
     souvent : il permet d'aller au bout sans documents.
3. De retour sur Solen, la cagnotte affiche « ouverte ».
4. Depuis le site invité, `/urne` : participer avec `4242 4242 4242 4242`.
5. Le don apparaît dans l'espace des mariés, statut « reçu ».

**Point important** : l'argent va sur le compte Stripe des mariés, pas sur
celui de Solen. La commission éventuelle se règle par mariage depuis
`/console`, en centièmes de pour cent (100 = 1 %).

---

## Parcours 5 — Le livre souvenir

Formule Signature uniquement.

1. Espace des mariés → **Livre souvenir**.
2. **Aperçu** : le rendu HTML, rapide à relire.
3. **Télécharger le PDF** : couverture, dédicace, déroulé, messages, photos.

---

## Parcours 6 — Le jour J

1. `/mariage/{slug}/admin/etapes-ceremonie` : marquer une étape « en cours ».
2. Sur le site invité, page de la cérémonie : le bandeau **EN DIRECT** doit
   afficher cette étape.
3. Les jeux : `/jeux/qui-de-nous-2`, `/jeux/memory`, `/jeux/mots-croises`,
   `/jeux/chasse-photo`.

---

## Remplir un mariage de démonstration

Pour peupler un mariage d'un coup — contenu, invités, messages et jeux :

```bash
php artisan db:seed --class=DemonstrationSeeder

# ou sur un autre mariage
SOLEN_DEMO_SLUG=njie-jessie php artisan db:seed --class=DemonstrationSeeder
```

Le seeder est rejouable : il remplace ce qu'il gère au lieu de l'empiler.

---

## Repartir de zéro

```bash
php artisan migrate:fresh --force
php artisan db:seed --class=ThemeSeeder --force
php artisan db:seed --class=CatalogueSeeder --force
```

⚠️ `migrate:fresh` **efface tout**, y compris le mariage de votre sœur.
Sauvegardez d'abord (voir [DEPLOIEMENT.md](DEPLOIEMENT.md)).
