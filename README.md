# KOFFR-IX

Plateforme de gestion électronique du patrimoine de l'État togolais.

> **Maquette de démonstration · projet pédagogique, non officiel · données fictives.**
> Démonstration de bout en bout réalisée avec Laravel 12 (PHP 8.4), PostgreSQL 16 et Docker Compose.

## Sommaire

1. [Architecture](#architecture)
2. [Installation](#installation)
3. [Comptes de démonstration](#comptes-de-démonstration)
4. [Parcours de démonstration](#parcours-de-démonstration)
5. [API](#api)
6. [Tests](#tests)
7. [Limites assumées](#limites-assumées)

## Architecture

Quatre applications Laravel indépendantes, une base PostgreSQL par service, une passerelle Nginx.

```
navigateur
    │  http://localhost:8080
    ▼
gateway (Nginx)
    ├── /                    → web        (Blade + Tailwind : l'interface)
    ├── /api/auth/           → auth        (comptes, structures, JWT)
    ├── /api/patrimoine/     → patrimoine  (biens, mouvements, QR codes)
    └── /api/inventaire/     → inventaire  (campagnes, contrôles, écarts)
                                   │
                                   └── appel interne (3 s max) → patrimoine
```

Règles structurantes :

- **Chaque service vérifie le JWT lui-même** avec le secret partagé `JWT_SECRET` (HS256).
  Aucun service n'appelle `auth` pour valider un jeton ; seul le `web` appelle `auth` (connexion).
- **Une base par service** : `auth_db`, `patrimoine_db`, `inventaire_db`. Aucun service ne lit
  la base d'un autre. Les liens entre services passent par des UUID.
- **Le web ne parle qu'à la passerelle** (`http://gateway`), avec le jeton stocké en session serveur.
- `inventaire` appelle `patrimoine` pour lire la fiche d'un bien lors d'un contrôle. Délai maximal
  3 secondes, réponse `503` en JSON si `patrimoine` ne répond pas : seul le parcours de scan est
  dégradé, le reste continue de fonctionner.
- Tables en **ajout seul** : `mouvements`, `controles`, `journal_audit` (aucune route de
  modification ou de suppression, et les modèles lèvent une exception sur `update`/`delete`).

## Installation

Prérequis : Docker Desktop avec Docker Compose.

```bash
git clone <dépôt> koffr-ix
cd koffr-ix

# 1. Secret JWT partagé (fichier non commité)
cp .env.example .env
# remplacer JWT_SECRET par une valeur aléatoire, par exemple :
#   node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"

# 2. Construction et démarrage
docker compose up -d --build

# 3. Schéma et données de démonstration (une fois par service)
docker compose exec auth php artisan migrate --seed
docker compose exec patrimoine php artisan migrate --seed
docker compose exec inventaire php artisan migrate --seed
```

Ouvrir ensuite **http://localhost:8080**.

Notes d'installation :

- Chaque service recopie au démarrage les variables d'infrastructure du `compose`
  (base de données, `JWT_SECRET`) dans son `.env` et génère sa clé applicative
  (`entrypoint.sh`). Aucun fichier `.env` de service n'a besoin d'être créé à la main.
- `vendor/` de chaque service est dans un volume Docker nommé (le partage de fichiers Windows
  est lent). Pour ajouter un paquet : `docker compose exec <service> composer require ...`
  puis `docker compose up -d --build`.
- Sous Git Bash, préfixer les commandes `docker run -w ...` par `MSYS_NO_PATHCONV=1`.
- **Ne jamais lancer `docker compose down -v`** sans prévenir : cela supprime les bases.
- Bases de test : `auth_test`, `patrimoine_test`, `inventaire_test` (créées par `init.sql`).

## Comptes de démonstration

Mot de passe commun : `password`. Environnement de développement uniquement.

| Compte | Rôle | Droits principaux |
|---|---|---|
| `comptable@koffr.local` | comptable des matières | créer/modifier un bien, déclarer un mouvement, ouvrir une campagne, écarts |
| `agent@koffr.local` | agent du patrimoine | scan de contrôle et constat |
| `controleur@koffr.local` | contrôleur | consultation, liste des écarts (lecture seule) |
| `responsable@koffr.local` | responsable de service | consultation, déclaration de mouvement |
| `admin@koffr.local` | administrateur | création/modification des biens, consultation |

Les trois premiers comptes sont proposés sur l'écran de connexion.

## Parcours de démonstration

1. **Connexion** avec `comptable@koffr.local` / `password`.
2. **Créer un bien** : « Biens » → « + Nouveau bien ». Le code patrimoine est généré
   automatiquement (`PAT-SAN-2026-0413`) et restera définitif.
3. **Imprimer l'étiquette** : fiche du bien → « Étiquette QR » → « Imprimer ».
   Le QR code encode `http://localhost:8080/verifier/PAT-…`.
4. **Transférer le bien** : fiche → « Transférer le bien », ou menu « Affectations et transferts ».
   Choisir le bien, le type (affectation, transfert, maintenance, sortie), la destination et le motif.
   La structure du bien est mise à jour et le mouvement apparaît dans l'historique.
5. **Ouvrir une campagne** : « Inventaire » → formulaire « Ouvrir une campagne » en bas de page.
6. **Contrôler par scan** : se connecter avec `agent@koffr.local`, « Contrôle par scan »,
   saisir le code (ou scanner le QR code au téléphone), choisir l'état constaté « ● Hors service ».
   L'encart « Écart détecté » s'affiche dès que l'état diffère de la fiche.
7. **Voir l'écart** : se connecter avec `controleur@koffr.local`, menu « Écarts et rapports » :
   la ligne s'affiche avec les deux états et l'agent, en lecture seule (export CSV possible).
8. **Vérifier publiquement** : ouvrir `http://localhost:8080/verifier` sans être connecté,
   puis saisir le code (ou ouvrir directement `http://localhost:8080/verifier/<code>`).
   Seuls le statut « bien de l'État », l'immatriculation, la catégorie et le ministère apparaissent.

Le contrôleur ne peut rien écrire : les boutons de création disparaissent et l'API répond `403`.

## API

Description complète : `docs/api/auth.yaml`, `docs/api/patrimoine.yaml`, `docs/api/inventaire.yaml`.

Toutes les réponses d'erreur suivent `{"message": "...", "errors": {...}}` avec les codes
`401`, `403`, `404`, `409`, `422`.

### auth

| Méthode | Route | Description |
|---|---|---|
| POST | `/api/auth/login` | connexion, renvoie un JWT de 15 minutes |
| POST | `/api/auth/refresh` | renouvelle le jeton |
| POST | `/api/auth/logout` | blackliste le jeton courant |
| GET | `/api/auth/me` | profil du compte connecté |
| GET | `/api/auth/structures?ids=` | id et nom des structures |
| GET | `/api/auth/users?ids=` | id et nom des comptes (rien d'autre) |
| GET | `/api/auth/ping` | disponibilité |

### patrimoine

| Méthode | Route | Rôle |
|---|---|---|
| GET | `/api/patrimoine/biens` | tous (filtres `q`, `categorie`, `etat`, `structure_id`, `ids`, pagination) |
| POST | `/api/patrimoine/biens` | admin, comptable |
| GET | `/api/patrimoine/biens/{id}` | tous |
| PUT | `/api/patrimoine/biens/{id}` | admin, comptable (le code est ignoré) |
| GET | `/api/patrimoine/biens/code/{code}` | tous (+ appel interne inventaire) |
| GET | `/api/patrimoine/biens/{id}/qrcode` | SVG |
| GET | `/api/patrimoine/biens/{id}/etiquette` | étiquette imprimable |
| GET/POST | `/api/patrimoine/biens/{id}/mouvements` | lecture tous ; écriture comptable, responsable |
| GET | `/api/patrimoine/mouvements/recents?limite=` | tous (derniers mouvements, avec code du bien) |
| GET | `/api/patrimoine/public/biens/{code}` | **sans authentification** (vérification) |
| GET | `/api/patrimoine/stats` | compteurs du tableau de bord |

### inventaire

| Méthode | Route | Rôle |
|---|---|---|
| GET | `/api/inventaire/campagnes` | tous (avec `controles_count`, `ecarts_count`) |
| POST | `/api/inventaire/campagnes` | comptable |
| GET | `/api/inventaire/campagnes/{id}` | tous (avec compteurs) |
| GET | `/api/inventaire/campagnes/{id}/controles` | tous |
| GET | `/api/inventaire/campagnes/{id}/ecarts` | comptable, contrôleur (lecture seule) |
| GET | `/api/inventaire/ecarts?campagne_id=` | comptable, contrôleur (tous les écarts) |
| GET | `/api/inventaire/controles/bien/{code}` | agent (fiche + dernier état connu) |
| POST | `/api/inventaire/controles` | agent (détecte l'écart, 409 si déjà contrôlé) |

## Tests

```bash
docker compose exec auth php artisan test        # authentification, rôles, JWT
docker compose exec patrimoine php artisan test  # biens, code unique, QR, mouvements
docker compose exec inventaire php artisan test  # campagnes, contrôles, écarts, 503
docker compose exec web php artisan test         # accès aux pages
```

Les tests utilisent les bases dédiées `*_test` (voir `tests/TestCase.php`) : la base de
démonstration n'est jamais modifiée.

## Limites assumées

- **Secret JWT partagé (HS256)** : un service compromis pourrait fabriquer de faux jetons.
  En production : clé privée dans `auth`, clé publique (RS256) dans les autres services.
- **Une seule instance PostgreSQL** avec trois bases : l'étanchéité repose sur la discipline du
  code (des rôles PostgreSQL dédiés seraient une amélioration).
- **Transferts sans validation** par le responsable de service (maquette seulement).
- **Photos de contrôle** stockées dans un volume du service `inventaire`, sans `document-service`.
- **Résilience partielle** : seul le scan dépend de `patrimoine`.
- Pas de mode hors ligne, pas de journal d'audit consultable, pas de procès-verbaux,
  pas de vue nationale, pas de traitement d'écart : maquette seulement.

Les choix techniques non tranchés par `AGENTS.md` sont consignés dans `docs/DECISIONS.md`.
