# Déploiement gratuit de KOFFR-IX (Neon + Render, sans carte bancaire)

Ce guide met la plateforme en ligne gratuitement : trois bases PostgreSQL chez **Neon**, cinq services chez **Render** (`auth`, `patrimoine`, `inventaire`, `gateway`, `web`). L'adresse à donner au jury est celle du service **web**.

> **Limites de l'offre gratuite** : un service s'endort après 15 minutes sans visite (réveil d'environ 1 minute), le disque est temporaire (les photos de contrôle disparaissent à chaque redémarrage), et les 750 heures par mois sont partagées entre les services. Avant une démonstration, ouvrir les pages de test de l'étape 9 environ 5 minutes avant.

## Étape 0 : préparer le code (sur ton PC)

### 0.1 Migrations au démarrage

Render gratuit n'a pas de terminal : les migrations doivent se lancer toutes seules. Dans **`services/auth/entrypoint.sh`, `services/patrimoine/entrypoint.sh` et `services/inventaire/entrypoint.sh`**, ajoute ceci juste avant la dernière ligne `exec "$@"` :

```sh
# Migrations et données de démonstration, pilotées par des variables.
if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
    php artisan migrate --force --no-interaction
fi
if [ "${RUN_SEED:-0}" = "1" ]; then
    php artisan db:seed --force --no-interaction
fi
```

Les migrations peuvent tourner à chaque démarrage sans danger. Les seeders, non : `RUN_SEED=1` ne sert qu'au tout premier déploiement (étape 6).

### 0.2 Port fourni par Render

Dans les quatre Dockerfiles (`auth`, `patrimoine`, `inventaire`, `web`), remplace la dernière ligne par :

```dockerfile
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]
```

En local, `PORT` n'existe pas : le port reste 8080, rien ne change.

### 0.3 Passerelle Render

Copie le dossier `render-gateway/` de ce zip dans ton dépôt, à `docker/render-gateway/` (deux fichiers : `Dockerfile` et `default.conf.template`).

### 0.4 Vérifier en local, puis pousser sur GitHub

```powershell
cd C:\Koffr-ix
docker compose up -d --build
docker compose ps
```

Ouvre http://localhost:8080 : tout doit marcher comme avant. Puis :

```powershell
git add .
git commit -m "chore: préparation du déploiement Render et Neon"
git push
```

Vérifie que `.env` n'est pas dans le dépôt (il est dans `.gitignore`).

## Étape 1 : créer les comptes

- Neon : https://neon.com (inscription avec GitHub ou e-mail).
- Render : https://render.com (inscription avec GitHub : c'est ce qui lui donne accès à ton dépôt).

Ne donne aucune carte bancaire. Si une page en demande une, arrête-toi.

## Étape 2 : trois bases sur Neon

Crée **trois projets** Neon (un par service, donc des bases séparées) : `koffr-auth`, `koffr-patrimoine`, `koffr-inventaire`. Choisis une région européenne (Francfort) et la version PostgreSQL 16 si elle est proposée.

Pour chacun, ouvre *Connect* et désactive l'option **Connection pooling** (on veut la connexion directe). Note ces cinq valeurs :

| Valeur | Exemple |
|---|---|
| Hôte | `ep-xxxx.eu-central-1.aws.neon.tech` |
| Port | `5432` |
| Base | `neondb` |
| Utilisateur | `neondb_owner` |
| Mot de passe | (affiché une fois, à copier) |

Garde ces notes dans un fichier **hors du dépôt**.

## Étape 3 : générer les secrets (sur ton PC)

```powershell
cd C:\Koffr-ix
docker compose exec web php artisan key:generate --show
```

Lance cette commande **4 fois** (`auth`, `patrimoine`, `inventaire`, `web`, avec `docker compose exec <service> php artisan key:generate --show`) et note chaque clé `base64:…` : ce sont les valeurs de `APP_KEY`.

Pour `JWT_SECRET`, génère un secret long et aléatoire, **le même pour les quatre services** et différent de celui de ton `.env` local :

```powershell
docker compose exec auth php -r "echo bin2hex(random_bytes(32));"
```

## Étape 4 : choisir les noms des services Render

Render donne l'adresse `https://<nom>.onrender.com`. Les noms doivent être uniques dans le monde. Propose :

| Service | Nom | Adresse |
|---|---|---|
| auth | `koffr-auth` | `koffr-auth.onrender.com` |
| patrimoine | `koffr-patrimoine` | `koffr-patrimoine.onrender.com` |
| inventaire | `koffr-inventaire` | `koffr-inventaire.onrender.com` |
| gateway | `koffr-gateway` | `koffr-gateway.onrender.com` |
| web | `koffr-web` | `koffr-web.onrender.com` |

Si un nom est pris, ajoute un suffixe (par exemple tes initiales) et adapte **partout** les adresses ci-dessous.

## Étape 5 : créer les services `auth`, `patrimoine`, `inventaire`

Pour chaque service : *New +* → *Web Service* → choisir le dépôt GitHub `Koffr-ix`, puis :

| Champ | Valeur |
|---|---|
| Name | `koffr-auth` (ou le nom du service) |
| Language / Runtime | **Docker** |
| Branch | `main` |
| Root Directory | `services/auth` (ou `services/patrimoine`, `services/inventaire`) |
| Dockerfile Path | `./Dockerfile` (voir l'astuce ci-dessous) |
| Region | Frankfurt |
| Instance Type | **Free** |

> Astuce : si le build échoue avec « failed to read dockerfile », essaie `./services/auth/Dockerfile` dans *Dockerfile Path*. Si le type « Free » ou le runtime Docker n'apparaît pas, arrête-toi et envoie-moi une capture d'écran.

Dans *Environment Variables*, ajoute (valeurs à remplacer) :

**Communes aux trois services**

| Variable | Valeur |
|---|---|
| `APP_ENV` | `production` |
| `APP_KEY` | la clé `base64:…` de **ce** service |
| `JWT_SECRET` | le secret commun de l'étape 3 |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | hôte Neon du **projet de ce service** |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | `neondb` |
| `DB_USERNAME` | `neondb_owner` |
| `DB_PASSWORD` | mot de passe Neon de ce projet |
| `RUN_MIGRATIONS` | `1` |
| `RUN_SEED` | `1` (**à retirer après le premier déploiement réussi**, étape 6) |
| `PHP_CLI_SERVER_WORKERS` | `4` |
| `APP_URL` | l'adresse de ce service, ex. `https://koffr-auth.onrender.com` |

**En plus, pour `auth` seulement** : `JWT_TTL` = `15`.

**En plus, pour `patrimoine` seulement** : `PUBLIC_VERIFY_URL` = `https://koffr-web.onrender.com/verifier` (c'est ce que contiennent les QR codes).

**En plus, pour `inventaire` seulement** : `PATRIMOINE_URL` = `https://koffr-patrimoine.onrender.com`.

Clique sur *Create Web Service*. Le premier build prend plusieurs minutes (Composer s'exécute pendant le build).

## Étape 6 : vérifier, puis retirer `RUN_SEED`

Quand un service affiche « Live », ouvre dans le navigateur :

- `https://koffr-auth.onrender.com/api/ping` → `{"service":"auth","ok":true}`
- `https://koffr-patrimoine.onrender.com/api/ping`
- `https://koffr-inventaire.onrender.com/api/ping`

Dans l'onglet *Logs* de chaque service, vérifie que les migrations se sont bien déroulées (lignes « DONE »). Puis, **sur chacun des trois services**, supprime la variable `RUN_SEED` (*Environment* → supprimer → *Save*). Sinon, les seeders se relanceraient à chaque réveil du service et provoqueraient des erreurs de doublons.

Dans Neon, onglet *Tables*, tu dois voir les tables créées.

## Étape 7 : la passerelle

*New +* → *Web Service* → même dépôt :

| Champ | Valeur |
|---|---|
| Name | `koffr-gateway` |
| Runtime | Docker |
| Root Directory | `docker/render-gateway` |
| Instance Type | Free |

Variables :

| Variable | Valeur |
|---|---|
| `AUTH_HOST` | `koffr-auth.onrender.com` (sans `https://`) |
| `PATRIMOINE_HOST` | `koffr-patrimoine.onrender.com` |
| `INVENTAIRE_HOST` | `koffr-inventaire.onrender.com` |

Test : `https://koffr-gateway.onrender.com/ping` → `gateway ok`, puis `https://koffr-gateway.onrender.com/api/auth/ping` → la réponse d'`auth`.

## Étape 8 : le web

*New +* → *Web Service* :

| Champ | Valeur |
|---|---|
| Name | `koffr-web` |
| Runtime | Docker |
| Root Directory | `services/web` |
| Instance Type | Free |

Variables :

| Variable | Valeur |
|---|---|
| `APP_ENV` | `production` |
| `APP_KEY` | la clé `base64:…` du web |
| `APP_URL` | `https://koffr-web.onrender.com` |
| `GATEWAY_URL` | `https://koffr-gateway.onrender.com` |
| `SESSION_DRIVER` | `cookie` |
| `CACHE_STORE` | `file` |
| `PHP_CLI_SERVER_WORKERS` | `4` |

(Pas de `JWT_SECRET` ni de base de données : le web ne vérifie pas les jetons et ne stocke rien.)

## Étape 9 : tester de bout en bout

1. Ouvre `https://koffr-web.onrender.com` et connecte-toi avec un compte de démonstration. La première visite peut prendre 1 à 2 minutes (les services se réveillent en chaîne).
2. Crée un bien, ouvre sa fiche, imprime l'étiquette, fais un transfert, lance un contrôle.
3. Sur ton **téléphone**, ouvre l'adresse du web et teste le scan par caméra (le HTTPS de Render le permet).
4. Scanne le QR code d'une étiquette : il doit ouvrir `https://koffr-web.onrender.com/verifier/…`.

## En cas de problème

| Symptôme | Cause probable | Action |
|---|---|---|
| Build qui échoue | Chemin du Dockerfile | Voir l'astuce de l'étape 5 |
| Service « Live » mais page d'erreur 500 | `APP_KEY` ou variable manquante | Onglet *Logs* ; vérifier les variables |
| `could not connect to server` | Mauvais hôte ou mot de passe Neon | Revérifier les cinq valeurs de l'étape 2 |
| Erreur SSL côté base | Neon exige SSL | Me renvoyer le message exact des logs |
| 504 après une longue inactivité | Un service se réveille | Réessayer après 1 minute |
| Services suspendus | Les 750 heures sont épuisées | Attendre le mois suivant ; ne pas laisser les pages ouvertes pour rien |

## À écrire dans le README

- L'adresse publique : `https://koffr-web.onrender.com`.
- Les comptes de démonstration et leur mot de passe (données fictives).
- Les limites de l'hébergement gratuit (mise en veille, photos non conservées).
