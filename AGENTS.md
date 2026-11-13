# AGENTS.md : consignes pour l'agent de développement de KOFFR-IX

Projet pédagogique (brief individuel, une semaine, démonstration de bout en bout). Plateforme de gestion électronique du patrimoine de l'État togolais. Données fictives uniquement, projet non officiel.

Le développeur (Samuel) devra expliquer le code et la démonstration à un commanditaire. Écris donc du code simple, lisible, commenté en français aux endroits clés, sans dépendance inutile.

## 1. Documents de référence

À placer dans `docs/` du dépôt :

- Cahier des charges GEPAT / KOFFR-IX (version du 11 nov. 2026)
- Schémas UML : `01-cas-utilisation.mmd`, `02-classes.mmd`, `03-sequence-controle-qr.drawio`, `04-deploiement.mmd`, `05-architecture.mmd`
- Maquettes (canvas Claude Design, 32 écrans : https://claude.ai/artifact/XrfduQHLJcS1sbaYMYhAbG) et charte graphique de la République togolaise
- `docs/assets/` : bloc blason officiel (`blason-republique-togolaise.svg`, extrait sans modification de la page 11 de la charte) et kit logo KOFFR-IX (`koffr-ix_*.svg`, `favicon*`)

En cas de conflit, l'ordre de priorité est : ce fichier, puis le cahier des charges, puis les maquettes. Ne modifie jamais les documents. Si un point n'est pas tranché ici, choisis la solution la plus simple et note-la dans `docs/DECISIONS.md`.

## 2. Périmètre

**À coder (priorité 1)**

1. Authentification et rôles
2. Gestion des biens, avec code patrimoine unique
3. QR codes et étiquette imprimable
4. Affectation et transfert, avec historique en ajout seul
5. Inventaire par scan et détection automatique des écarts

**À coder si le temps le permet, dans cet ordre** : vérification publique par QR code (page sans connexion), compteurs du tableau de bord, export CSV de la liste des biens.

**Maquette seulement (ne pas coder)** : documents joints, carte, interface du journal d'audit, procès-verbaux, vue nationale, traitement d'un écart, mode hors ligne, validation d'un transfert par le responsable, création de comptes par l'administrateur.

## 3. Stack et contraintes imposées

- Laravel uniquement (Laravel 12, PHP 8.4). Nginx et PostgreSQL sont de l'infrastructure.
- Architecture micro-services : `auth`, `patrimoine`, `inventaire`, plus l'application web `web`. Pas de `document-service` : les photos de contrôle sont stockées dans un volume Docker du service `inventaire`.
- PostgreSQL 16, une base par service. Aucun service ne lit la base d'un autre.
- JWT avec `php-open-source-saver/jwt-auth` (pas `tymon/jwt-auth`, non maintenu). Signature HS256 avec un secret partagé `JWT_SECRET`.
- Application web : Blade + Tailwind. QR codes : `simplesoftwareio/simple-qrcode` (génération), `html5-qrcode` (scan).
- Docker et Docker Compose, un conteneur par service.

## 4. État actuel de l'environnement (déjà en place et fonctionnel)

Dépôt monorepo sur Windows : `C:\Koffr-ix`.

```
koffr-ix/
├── docker-compose.yml
├── docker/
│   ├── gateway/default.conf     (Nginx)
│   └── postgres/init.sql        (crée auth_db, patrimoine_db, inventaire_db)
└── services/
    ├── auth/  patrimoine/  inventaire/   (projets Laravel 12 + Dockerfile)
```

- Services dans le compose : `db` (PostgreSQL, utilisateur `koffr`, mot de passe `secret`), `pgadmin`, `auth`, `patrimoine`, `inventaire`, `gateway` (port hôte 8080).
- Chaque service Laravel tourne avec `php artisan serve --host=0.0.0.0 --port=8080` (port interne 8080) et reçoit ses variables `DB_*` par le compose.
- `vendor/` de chaque service est dans un volume Docker nommé (`auth_vendor`, `patrimoine_vendor`, `inventaire_vendor`). Raison : l'extraction sur le dossier Windows partagé dépasse les délais. Pour ajouter un paquet : `docker compose exec <service> composer require ...`.
- Passerelle : `/api/auth/` vers `auth:8080/api/`, `/api/patrimoine/` vers `patrimoine:8080/api/`, `/api/inventaire/` vers `inventaire:8080/api/`.
- `php artisan install:api` a été exécuté : **Sanctum est installé et doit être retiré** (supprimer son usage et la migration `personal_access_tokens`) au profit du JWT.
- Chaque service expose `GET /api/ping`.
- À ajouter : le service `web` (Laravel Blade) et la route `/` de la passerelle vers `web`.
- Ne jamais lancer `docker compose down -v` sans prévenir : cela supprime les bases.
- Sous Git Bash, préfixer `docker run -w ...` par `MSYS_NO_PATHCONV=1`.

## 5. Architecture et règles de communication

- Passerelle Nginx : `/` vers `web`, `/api/auth`, `/api/patrimoine`, `/api/inventaire` vers les services.
- L'application web n'appelle que la passerelle (`http://gateway/api/...` dans le réseau Docker).
- Chaque service **vérifie lui-même le JWT** avec `JWT_SECRET` (un middleware commun, copié dans chaque service). Il ne rappelle pas `auth`.
- Appel interne : `inventaire` appelle `patrimoine` (`http://patrimoine:8080/api/...`) en transmettant le JWT reçu. Prévoir un délai maximal (3 s) et une erreur propre (503 en JSON) si `patrimoine` ne répond pas. Une panne d'un service n'arrête pas les autres, **sauf** que le contrôle par scan d'`inventaire` dépend de `patrimoine` (il lit la fiche du bien) : seul ce parcours est dégradé, à dire ainsi à la soutenance.
- Dépendance à `auth` : `patrimoine` et `inventaire` ne l'appellent jamais pour vérifier un jeton. Leur seule dépendance est la clé de signature `JWT_SECRET`. Seul le `web` appelle `auth`.
- Les liens entre services passent par un UUID, jamais par une clé étrangère entre bases.
- Le secret `JWT_SECRET` vient d'un fichier `.env` à la racine (non commité, avec un `.env.example`) injecté par le compose.
- **Ordre des appels du web** (toujours via la passerelle, avec le JWT de la session) : (1) `POST /api/auth/login` ; (2) pour une page de liste ou de fiche, `GET /api/patrimoine/...` ; (3) pour afficher les noms, un seul appel groupé `GET /api/auth/users?ids=...` et `GET /api/auth/structures?ids=...` avec les UUID collectés dans la réponse de l'étape 2 (jamais un appel par ligne). Le `web` fusionne les noms dans la vue. `patrimoine` et `inventaire` ne stockent que des UUID.
- Les routes `GET /users?ids=` et `GET /structures?ids=` ne renvoient que `id` et `name` (ou `nom`) : aucune adresse e-mail, aucun rôle, aucun mot de passe, conformément à « identité professionnelle seulement ».
- Amélioration facultative : trois rôles PostgreSQL dédiés (un par base) dans `init.sql`, pour garantir qu'aucun service n'accède à la base d'un autre. À documenter dans la fiche technique.

## 6. Rôles et permissions

Codes de rôle : `admin`, `comptable_matieres`, `agent_patrimoine`, `responsable_service`, `controleur`. Le public (vérification d'un bien) n'a pas de compte.

| Fonctionnalité | admin | comptable | agent | responsable | contrôleur |
|---|---|---|---|---|---|
| Consultation générale (biens, fiches, historique) | oui | oui | oui | oui | oui |
| Création, modification d'un bien | oui | oui | non | non | non |
| Déclaration de transfert, d'affectation, de maintenance, de sortie | non | oui | non | oui | non |
| Ouverture d'une campagne d'inventaire | non | oui | non | non | non |
| Scan de contrôle et constat | non | non | oui | non | non |
| Liste des écarts (lecture seule) | non | oui | non | non | oui |
| Gestion des comptes et structures | oui | non | non | non | non |

Règles :

- Middleware `role:...` dans chaque service. Réponse 401 si non connecté, 403 si le rôle ne convient pas.
- Messages de connexion génériques : « Identifiants incorrects » sans dire lequel est faux.
- Contenu du JWT : `sub` (UUID), `name`, `role`, `structure_id`, `ministere_code`. Durée de vie 15 minutes avec `POST /refresh` ; le web affiche l'écran « session expirée » quand le jeton expire.
- Comptes de démonstration (mot de passe `password`, environnement de développement seulement), un par rôle : `comptable@koffr.local`, `agent@koffr.local`, `controleur@koffr.local`, `responsable@koffr.local`, `admin@koffr.local`. Les seuls trois premiers sont proposés sur l'écran de connexion.
- Le domaine e-mail autorisé est configurable (`ALLOWED_EMAIL_DOMAIN`, vide en démonstration).

## 7. Modèle de données

Basé sur `02-classes.mmd`. Toutes les clés primaires sont des **UUID** (`HasUuids`). Noms de colonnes et champs JSON en français, `snake_case`, comme dans les UML.

**auth_db**
- `structures` : `id`, `nom`, `code` (ex. `SAN`, `FIN`, `EDU` pour un ministère), `type`, `region`, `parent_id`.
- `users` : colonnes Laravel (`name`, `email` unique, `password` haché) + `role`, `structure_id`, `actif`.

**patrimoine_db**
- `biens` : `id`, `code_unique` (unique, immuable), `designation`, `categorie` (Matériel informatique, Mobilier, Immeubles, Véhicules, Autres), `valeur_acquisition`, `date_acquisition`, `etat` (`bon`, `usage`, `hors_service`), `statut` (`en_service`, `sorti`), `latitude`, `longitude`, `localisation` (texte), `structure_id`, `responsable_id`, horodatages.
- `mouvements` (**ajout seul**) : `id`, `bien_id`, `type` (`affectation`, `transfert`, `maintenance`, `sortie`), `date`, `structure_origine_id`, `structure_destination_id`, `motif`, `statut_validation` (valeur `valide` par défaut), `auteur_id`.
- `sequences_code` : `ministere_code`, `annee`, `dernier_numero` (pour la génération atomique).
- `journal_audit` (ajout seul) : `id`, `utilisateur_id`, `action`, `objet`, `date`, `detail`. Prévoir aussi la même table dans `auth_db` pour les événements de connexion, de création de compte et d'accès refusé. Chaque service écrit dans son propre journal ; on ne les fusionne pas (l'interface du journal reste en maquette seulement). À consigner dans `docs/DECISIONS.md`.

**inventaire_db**
- `campagnes` : `id`, `nom`, `date_debut`, `date_fin`, `structure_id`, `statut` (`ouverte`, `cloturee`).
- `controles` (**ajout seul**) : `id`, `campagne_id`, `bien_id`, `etat_fiche` (état de la fiche au moment du contrôle), `etat_constate` (`bon`, `usage`, `hors_service`, `introuvable`), `latitude`, `longitude`, `photo_chemin` (nullable), `ecart_detecte` (booléen), `agent_id`, `date`. Contrainte unique sur (`campagne_id`, `bien_id`).

**Code patrimoine** : format `PAT-{ministere_code}-{année}-{numéro sur 4 chiffres}`, par exemple `PAT-SAN-2026-0412`. Le numéro est incrémenté par ministère et par année dans `sequences_code`, dans une transaction avec verrou de ligne (`lockForUpdate`), pour éviter les doublons. Une fois créé, le code n'est jamais modifiable (champ ignoré ou refusé en mise à jour).

## 8. Règles métier et contraintes d'ajout seul

- Aucune route de modification ni de suppression sur `mouvements`, `controles` et `journal_audit`. En plus, les modèles lèvent une exception sur `update` et `delete`.
- Transfert : en une seule transaction, créer le mouvement et mettre à jour `structure_id` et `responsable_id` du bien. La destination et le motif sont obligatoires. Pas de validation préalable (statut `valide` direct).
- Sortie (réforme, cession, perte) : mouvement `sortie` et `statut = sorti`. Maintenance : mouvement seul.
- Écart : à l'enregistrement d'un contrôle, `inventaire` lit la fiche dans `patrimoine`, copie `etat_fiche`, compare avec `etat_constate` et positionne `ecart_detecte`. Un seul contrôle par bien et par campagne (sinon 409).
- Le contrôleur n'a aucune route d'écriture.
- Valeurs en FCFA, entiers.
- Critères d'acceptation à respecter : voir les user stories US1 à US10 du cahier des charges (section 6). Chaque critère devient un test.

## 9. API REST (JSON, préfixe `/api`)

Réponses d'erreur : `{"message": "...", "errors": {...}}`, codes HTTP standards (401, 403, 404, 409, 422).

**auth** : `POST /login`, `POST /logout`, `POST /refresh`, `GET /me`, `GET /structures` (avec filtre `ids`), `GET /users?ids=...` (identifiant et nom uniquement, pour résoudre les noms), `GET /ping`.

**patrimoine**
- `GET /biens` (filtres `categorie`, `structure_id`, `etat`, recherche `q`, pagination)
- `POST /biens`, `GET /biens/{id}`, `PUT /biens/{id}`
- `GET /biens/code/{code}` (utilisé par le scan et par `inventaire`)
- `GET /biens/{id}/qrcode` (SVG), `GET /biens/{id}/etiquette` (étiquette imprimable)
- `POST /biens/{id}/mouvements`, `GET /biens/{id}/mouvements` (du plus récent au plus ancien)
- Bonus : `GET /public/biens/{code}` (sans authentification, donne seulement : bien de l'État, immatriculé, catégorie, ministère de rattachement ; aucune valeur, aucun responsable, aucune localisation précise), `GET /stats`.
- `GET /ping`

**inventaire**
- `GET /campagnes`, `POST /campagnes`, `GET /campagnes/{id}`
- `GET /controles/bien/{code}` (fiche et dernier état connu)
- `POST /controles`
- `GET /campagnes/{id}/controles`, `GET /campagnes/{id}/ecarts` (lecture seule)
- `GET /ping`

Fournir une description OpenAPI par service dans `docs/api/<service>.yaml`.

## 10. Application web (`web`)

Pages à coder : connexion, liste des biens (filtres et recherche), fiche d'un bien (étiquette QR et historique), création et modification d'un bien, transfert, campagnes d'inventaire (liste et ouverture), contrôle par scan (mobile), liste des écarts (lecture seule). Bonus : vérification publique, tableau de bord avec compteurs, export CSV.

- Le menu dépend du rôle. Sans connexion, redirection vers la page de connexion.
- Le JWT est gardé dans la session côté serveur. Les appels partent vers la passerelle avec l'en-tête `Authorization: Bearer`.
- Écran de scan conçu d'abord pour 375 px de large ; les autres écrans pour 1440 px. Le scan par caméra exige HTTPS ou `localhost` : prévoir toujours la saisie manuelle du code (écran « Étiquette abîmée »).
- Respecter les maquettes (mise en page, textes, états vides, erreurs).

**Charte et accessibilité**
- Couleurs : vert `#006A4F`, rouge `#D11135`, jaune `#FFCF11`, noir et blanc. Police Inter. Contraste d'au moins 4,5.
- Un statut n'est jamais indiqué par la couleur seule : toujours une icône ou un texte (`✓ Bon`, `▲ Usagé`, `● Hors service`, `✕ Écart`).
- Pied de page : « Maquette de démonstration · projet pédagogique, non officiel · données fictives ».
- Blason de la République : n'utiliser que `docs/assets/blason-republique-togolaise.svg` (bloc complet : blason, mention « République Togolaise », filet vert). Ne jamais le redessiner ni le modifier. Hauteur minimale 22 mm, zone de protection respectée, jamais sur fond vert ni noir, jamais à l'intérieur du logo KOFFR-IX. Si le fichier manque, mettre un emplacement texte.
- Identité KOFFR-IX : un seul composant d'en-tête (symbole K + mot « KOFFR-IX ») utilisé sur toutes les pages, `favicon.svg` dans `<head>`. Le nom « GEPAT » ne doit apparaître nulle part dans l'interface ni dans les messages. Étiquettes imprimées : mention « République Togolaise » en texte et QR code ; le bloc blason n'y figure que si le développeur le décide.

## 11. Conventions Docker

- Toute nouvelle dépendance se déclare dans `composer.json` du service et se réinstalle par `docker compose up -d --build`.
- Un nouveau service reprend le modèle des autres : Dockerfile `php:8.4-cli`, port interne 8080, volume `vendor` nommé, `DB_*` dans le compose, healthcheck de la base.
- Chaque service doit démarrer seul avec sa base (`docker compose up -d db auth`).
- Ne jamais commiter `.env`, `vendor/`, ni de secret.

## 12. Tests

- PHPUnit (tests Feature) dans chaque service : `docker compose exec <service> php artisan test`. Utiliser des bases dédiées `*_test`.
- Tests minimaux : connexion et rôles (401 et 403), création d'un bien avec code unique et non modifiable, mouvements non modifiables ni supprimables, transfert qui met à jour la structure, contrôle qui détecte un écart, le contrôleur ne peut rien écrire.
- Parcours de démonstration à valider : créer un bien, imprimer l'étiquette, transférer le bien, ouvrir une campagne, contrôler par scan, voir l'écart dans la liste, vérifier que le contrôleur est en lecture seule.

## 13. Données de démonstration (seeders)

- Structures : Ministère de la Santé (`SAN`), Ministère des Finances et du Budget (`FIN`), Ministère de l'Éducation (`EDU`), puis sous la Santé : Services généraux, Ressources humaines, Hôpital régional de Lomé, Centre de santé de zone, Inspection générale.
- Utilisateurs : Ama K. (comptable, Santé), Kodjo A. (responsable, Services généraux), Yao T. (agent, Services généraux), Abla M. (responsable, Ressources humaines), Mawuli S. (contrôleur, Inspection générale), Edem R. (admin).
- Biens : `PAT-SAN-2026-0412` Ordinateur portable HP 450, `PAT-SAN-2026-0411` Imprimante laser, `PAT-SAN-2025-0231` Climatiseur 18 000 BTU, `PAT-SAN-2025-0198` Groupe électrogène 20 kVA, `PAT-SAN-2024-0087` Véhicule utilitaire Toyota Hilux.
- Une campagne « Inventaire 2026 » ouverte pour la Santé.

## 14. Ordre de travail

Un commit par étape terminée, tests au vert avant de continuer.

1. **auth** : migrations (structures, rôles), seeders, JWT (`login`, `me`, `refresh`, `logout`), middleware de rôle, retrait de Sanctum, journal des connexions, tests.
2. **patrimoine** : vérification du JWT, biens (liste, création, fiche, modification), code unique, QR code et étiquette, mouvements et transfert, tests. Point de contrôle : création de bien, QR code et transfert fonctionnent.
3. **inventaire** : campagnes, contrôles, appel interne à `patrimoine`, détection d'écart, liste des écarts, photo de contrôle (optionnelle), tests.
4. **web** : service dans le compose et la passerelle, pages dans l'ordre : connexion, liste, fiche, création, transfert, campagnes, scan, écarts. Puis bonus.
5. **Documentation** : `README.md` (installation avec `docker compose up -d --build`, comptes de test, parcours de démonstration), fichiers OpenAPI, `docs/DECISIONS.md`.

## 15. Limites assumées (à citer dans la fiche technique et à la soutenance)

- **Secret JWT partagé (HS256)** : tout service compromis pourrait fabriquer de faux jetons. Suffisant pour la démonstration ; en production, clé privée dans `auth` seul et clé publique (RS256) dans les autres services.
- **Une seule instance PostgreSQL** avec trois bases (une par service) ; l'étanchéité repose sur la discipline du code, ou sur les rôles dédiés si l'option est faite.
- **Transferts sans validation** par le responsable de service (maquette seulement).
- **Photos de contrôle** dans un volume du service `inventaire`, sans `document-service`. Dans les schémas 04 et 05, `document-service` doit être aligné (en pointillé, optionnel, ou retiré).
- **Résilience partielle** : voir section 5.
- Données fictives, projet non officiel, QR et étiquettes de démonstration.

## 16. Règles de conduite

- Ne change pas le périmètre ni les décisions de ce fichier sans demander.
- Si quelque chose est ambigu, applique la règle de la section 1 et consigne le choix.
- Avant toute commande destructive (`down -v`, suppression de volumes, `migrate:fresh`), préviens le développeur.
- Termine chaque étape par un court résumé : ce qui marche, comment le tester, ce qui reste.
