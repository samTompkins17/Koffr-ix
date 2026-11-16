# KOFFR-IX

Plateforme de gestion électronique du patrimoine de l'État togolais.

> Projet pédagogique non officiel, utilisant exclusivement des données fictives. Démonstration réalisée avec Laravel 12 (PHP 8.4), PostgreSQL 16 et Docker Compose.

## Liens de livraison

- **Code source** : [samTompkins17/Koffr-ix sur GitHub](https://github.com/samTompkins17/Koffr-ix).
- **Application hébergée** : adresse à renseigner après déploiement et vérification ; aucune URL publique n'est encore validée dans ce document.
- **Démonstration locale** : http://localhost:8080 après installation.

Un lien GitHub ne garantit pas que les modifications locales y sont publiées : la livraison doit identifier le commit effectivement déployé.

## Sommaire

1. [Le besoin](#le-besoin)
2. [Informations gérées](#informations-gérées)
3. [Présentation et fonctionnalités](#présentation-et-fonctionnalités)
4. [Architecture](#architecture)
5. [Normalisation des données et conventions](#normalisation-des-données-et-conventions)
6. [Installation](#installation)
7. [Hébergement gratuit et sécurité](#hébergement-gratuit-et-sécurité)
8. [Comptes de démonstration](#comptes-de-démonstration)
9. [Parcours de démonstration](#parcours-de-démonstration)
10. [API](#api)
11. [Tests et qualité](#tests-et-qualité)
12. [Limites assumées](#limites-assumées)
13. [Checklist de livraison](#checklist-de-livraison)

## Le besoin

### Nature du besoin

KOFFR-IX répond à un **besoin de gestion du patrimoine mobilier et immobilier de l'État** : savoir quels biens existent, à qui ils sont confiés, où ils se trouvent, dans quel état ils sont, et ce qui leur est arrivé jusqu'à leur sortie du patrimoine.

Ce n'est pas un besoin de comptabilité budgétaire, ni un besoin de paie, ni une application grand public. Le cœur du besoin est opérationnel :

- **immatriculer** chaque bien une seule fois, avec un code qui ne change plus ;
- **affecter et transférer** un bien d'une structure à une autre, avec une décision tracée ;
- **inventorier sur le terrain** en comparant ce que l'agent voit à ce que la fiche dit ;
- **contrôler** sans pouvoir réécrire l'historique ;
- **rendre compte** par des listes, des écarts, des procès-verbaux et une vue agrégée.

Le livrable actuel est une **démonstration pédagogique**, brief individuel d'une semaine, avec des données fictives. Il montre le parcours de bout en bout. Il ne constitue pas un système officiel de l'État togolais et ne remplace pas les outils du Trésor ou du Budget.

Le cadrage détaillé, les user stories et les hypothèses à valider avec le commanditaire sont dans le [cahier des charges corrigé](docs/Cahier%20des%20charges%20KOFFR-IX%20corrige.md).

### Contexte

Le cadre légal de la comptabilité des matières existe déjà : la directive n°03/2012/CM/UEMOA du 29 juin 2012, transposée pour l'État, ses établissements publics et les collectivités par le décret n°2016-060/PR du 4 mai 2016. Deux décrets du 18 septembre 2024 créent les postes comptables matières dans les ministères et institutions, ainsi que les trésoreries ministérielles.

Le processus prévu en quatre étapes est : inventorier le patrimoine non financier, valoriser les biens, les codifier et les immatriculer, puis enregistrer comptablement les résultats. Le comptable matières garde les biens, tient la comptabilité des matières et rend le compte de gestion à la Cour des comptes.

Les parties concernées par ce cadre, dans le brief, sont :

| Partie | Place dans le besoin |
|---|---|
| Ministère chargé de la protection du patrimoine de l'État | Commanditaire du brief |
| Direction générale du Trésor et de la Comptabilité publique | Conduit la réforme et la formation des acteurs |
| Cour des comptes | Destinataire du compte de gestion des matières |
| Ministères, institutions et collectivités | Détiennent les biens et accueillent les postes comptables |

### Problème à traiter

Les comptables matières qui prennent leurs fonctions ont besoin d'un **outil commun** pour inventorier, immatriculer et suivre les biens jusqu'à leur sortie. Sans outil partagé, chaque structure tient ses propres tableaux : ils sont difficiles à consolider, difficiles à contrôler, et une ligne peut être corrigée ou effacée sans qu'il en reste de trace.

Hypothèses de travail, à confirmer avec le commanditaire (elles ne viennent pas d'une source officielle) :

- les données patrimoniales sont dispersées entre ministères, collectivités et établissements ;
- les inventaires sont surtout manuels et vieillissent vite ;
- les mouvements (affectation, transfert, réparation, perte, cession, réforme) sont mal tracés ;
- les terrains et les bâtiments sont difficiles à localiser et à documenter ;
- il n'existe pas de tableau de bord national fiable pour les décideurs.

### Ce que la solution doit permettre

| Objectif | Signe que le besoin est couvert |
|---|---|
| Identifier chaque bien une seule fois | Un code patrimoine unique et un QR code imprimable sont créés à l'enregistrement ; le code ne se modifie plus |
| Garder la mémoire du bien | Affectation, transfert, maintenance et sortie s'ajoutent à l'historique avec date, auteur et motif ; aucune ligne ne se supprime |
| Rendre l'inventaire plus fiable | L'agent scanne ou saisit le code, constate l'état ; l'écart avec la fiche est signalé par le système |
| Permettre le contrôle | Le contrôleur lit l'historique, les écarts et les journaux, sans bouton d'écriture |
| Montrer un parcours complet | Créer un bien, l'étiqueter, le transférer, ouvrir une campagne, le contrôler et voir l'écart, le tout sous Docker |

Trois voies ont été écartées pour ce brief : rester sur des tableurs par structure, étendre un système budgétaire dont on ne sait pas s'il couvre déjà les biens, ou acheter un progiciel de gestion d'actifs. KOFFR-IX est une solution sur mesure de démonstration : le code et les données restent maîtrisables, et les API permettent un branchement ultérieur. Ce choix est qualitatif ; il reste à confronter à l'existant du ministère des Finances avant tout déploiement réel.

### Qui a besoin de quoi

| Profil | Besoin concret | Ce qu'il ne doit pas pouvoir faire |
|---|---|---|
| Comptable des matières | Créer et mettre à jour les fiches, immatriculer, joindre les pièces, déclarer un mouvement, ouvrir une campagne, documenter un écart | Valider à lui seul un transfert en attente ; effacer un historique |
| Agent du patrimoine | Sur le terrain, ouvrir la fiche par scan ou saisie, constater l'état, joindre une photo et une position | Créer un bien, transférer, clôturer un écart |
| Responsable de service | Déclarer un mouvement et décider les affectations et transferts en attente, avec un motif | Réécrire un mouvement déjà décidé |
| Contrôleur | Lire les biens, l'historique, les écarts et les journaux | Toute écriture |
| Administrateur | Créer les comptes et les structures, consulter les droits et les journaux | Se substituer au responsable pour valider un transfert |
| Public, sans compte | Vérifier qu'une étiquette correspond à un bien de l'État | Voir la valeur, le responsable, la localisation précise ou les pièces |

En démonstration, l'écran de connexion propose les trois premiers comptes métier. Les cinq rôles existent dans les données de départ.

### Hors de ce besoin

Restent hors du livrable, par choix ou par délai : application mobile native, inventaire hors ligne, mot de passe oublié, notifications, amortissement, interconnexion avec les systèmes du Trésor, journal chaîné par empreinte, signalement citoyen. Le détail est dans le cahier des charges, section « Hors périmètre ».

## Informations gérées

Le besoin porte sur des **informations patrimoniales**, pas sur un dossier complet de l'agent. Chaque service ne stocke que ce qui lui sert. Les noms des personnes et des structures restent dans `auth` ; les autres services ne gardent que des identifiants.

### Informations d'un bien

| Information | Rôle dans le besoin | Règle |
|---|---|---|
| Code patrimoine `PAT-{ministère}-{année}-{numéro}` | Immatriculation stable, imprimée sur l'étiquette et encodée dans le QR | Unique, créé par le système, jamais modifié ensuite |
| Désignation, catégorie | Retrouver et classer le bien | Catégories : Matériel informatique, Mobilier, Immeubles, Véhicules, Autres |
| Valeur d'acquisition, date d'acquisition | Connaître le coût d'entrée, en francs CFA entiers | Réservées aux utilisateurs connectés ; absentes de la page publique |
| État (`bon`, `usage`, `hors_service`) et statut (`en_service`, `sorti`) | Savoir si le bien sert encore | L'état constaté sur le terrain peut différer de l'état de la fiche |
| Localisation texte, latitude, longitude | Situer le bien pour la carte et le contrôle | La page publique ne montre pas la localisation précise |
| Structure de rattachement, responsable | Savoir qui détient le bien | La structure ne change qu'à la validation d'une affectation ou d'un transfert |
| Documents (facture, acte, titre, photo, autre) | Justifier le bien | PDF, JPG ou PNG, 10 Mo maximum, ajout seul, téléchargement authentifié |

### Informations d'un mouvement

Un mouvement est un fait ajouté, pas une correction de la fiche.

| Type | Effet attendu | Décision |
|---|---|---|
| Affectation, transfert | Changement de structure | Créé `en_attente` ; la structure change seulement si un responsable valide. Un refus laisse le bien en place |
| Maintenance | Trace d'une intervention | Appliquée tout de suite, sans changer la structure |
| Sortie (réforme, cession, perte) | Le bien quitte le service | Appliquée tout de suite ; le statut devient `sorti` |

Chaque mouvement garde la date, le motif, les structures d'origine et de destination, et l'auteur. La décision du responsable (validée ou refusée, motif, décideur) est un second enregistrement : le mouvement d'origine n'est pas réécrit.

### Informations d'un inventaire

| Information | Rôle |
|---|---|
| Campagne (nom, dates, structure, ouverte ou clôturée) | Cadre le contrôle d'un ensemble de biens |
| Contrôle (bien, campagne, agent, date) | Un seul constat par bien et par campagne |
| État de la fiche au moment du contrôle | Copie figée : une correction ultérieure de la fiche ne réécrit pas le constat |
| État constaté (`bon`, `usage`, `hors_service`, `introuvable`) | Ce que l'agent voit |
| Écart | Positionné par le système quand l'état constaté diffère de la fiche |
| Photo et position du contrôle | Preuve de terrain, stockée avec le constat |
| Traitement de l'écart | Décision du comptable (régularisé, réformé, main levée, autre) plus un commentaire ; le constat reste intact |

### Ce qui est montré, et à qui

| Information | Utilisateur connecté | Page publique de vérification |
|---|---|---|
| Bien de l'État, code, catégorie, ministère | Oui | Oui |
| Valeur, dates, responsable | Oui | Non |
| Localisation précise, coordonnées | Oui | Non |
| Historique, documents, écarts, journaux | Selon le rôle | Non |
| Adresse e-mail, rôle, mot de passe d'un tiers | Non dans les listes de noms | Non |

Les listes qui affichent un nom de personne ou de structure passent par un appel groupé à `auth` : celui-ci ne renvoie que l'identifiant et le nom.

### Jeu d'informations de la démonstration

Les données de départ sont fictives et servent à raconter le parcours sans saisie préalable.

- **Structures** : Ministère de la Santé (`SAN`), Ministère des Finances et du Budget (`FIN`), Ministère de l'Éducation (`EDU`). Sous la Santé : Services généraux, Ressources humaines, Hôpital régional de Lomé, Centre de santé de zone, Inspection générale.
- **Comptes** : Ama K. (comptable, Santé), Kodjo A. (responsable, Services généraux), Yao T. (agent, Services généraux), Abla M. (responsable, Ressources humaines), Mawuli S. (contrôleur, Inspection générale), Edem R. (administrateur). Mot de passe de démonstration : `password`.
- **Biens déjà immatriculés** : `PAT-SAN-2026-0412` Ordinateur portable HP 450, `PAT-SAN-2026-0411` Imprimante laser, `PAT-SAN-2025-0231` Climatiseur 18 000 BTU, `PAT-SAN-2025-0198` Groupe électrogène 20 kVA, `PAT-SAN-2024-0087` Véhicule utilitaire Toyota Hilux. Le prochain code Santé de 2026 sera donc `PAT-SAN-2026-0413`.
- **Campagne** : « Inventaire 2026 », ouverte pour la Santé.

## Présentation et fonctionnalités

KOFFR-IX permet de recenser les biens, suivre leur affectation, contrôler leur présence sur le terrain et conserver une trace des opérations.

| Domaine | Fonctionnalités disponibles |
|---|---|
| Authentification | Connexion, JWT, cinq rôles, expiration de session, page d'accès refusé |
| Patrimoine | Liste et recherche, création/modification, code unique immuable, fiche et historique |
| Mouvements | Affectation/transfert avec validation ou refus ; maintenance et sortie immédiates |
| Inventaire | Ouverture de campagnes, scan QR ou saisie manuelle, constats et détection des écarts |
| Documents | Ajout privé de PDF/JPG/PNG, téléchargement authentifié, sans modification ni suppression |
| Impression | QR SVG, étiquette unitaire, planches A4 PDF et ZIP/SVG, exports PDF des biens et écarts |
| Rapports | PV d'inventaire et de sortie en PDF, carte, compteurs et vue nationale |
| Administration | Création de comptes et structures, consultation des droits et journaux par service |
| Accès public | Vérification limitée d'un bien par son code, sans données sensibles |

Les maquettes sont des références : certaines fonctions y sont plus détaillées que l'implémentation. Le mode hors ligne et le mot de passe oublié ne sont pas implémentés ; les exports CSV/Excel ne sont pas disponibles dans la version actuelle.

## Architecture

Quatre applications Laravel indépendantes, une base PostgreSQL par service, une passerelle Nginx.

```
navigateur
    │  http://localhost:8080
    ▼
gateway (Nginx)
    ├── /                    → web        (Blade + Tailwind : interface responsive et navigation avec icônes SVG)
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
  dégradé, le reste continue de fonctionner. Le contrôle mobile utilise `/controles/scan` ; le scan
  caméra requiert `localhost` ou HTTPS, avec saisie manuelle disponible en secours.
- Tables en **ajout seul** : `mouvements`, `controles`, `documents`, `journal_audit` (aucune route de
  modification ou de suppression, et les modèles lèvent une exception sur `update`/`delete`).
- Les décisions sont elles aussi en ajout seul : `validations_mouvement` (une par mouvement,
  `validee` ou `refusee`) et `traitements_ecart` (une par contrôle). Ni le mouvement ni le constat
  ne sont réécrits ; le statut affiché est calculé à la lecture (`statut_effectif`).

### Modèle de données

| Base | Tables | Contenu |
|---|---|---|
| `auth_db` | `users`, `structures`, `journal_audit` | comptes, hiérarchie des structures, journal d'authentification |
| `patrimoine_db` | `biens`, `sequences_code`, `mouvements`, `validations_mouvement`, `documents`, `journal_audit` | biens, compteurs de codes, mouvements, décisions et pièces jointes |
| `inventaire_db` | `campagnes`, `controles`, `traitements_ecart`, `journal_audit` | campagnes, constats, décisions du comptable |

Chaque service journalise ses propres actions dans sa base ; les trois journaux de la page
d'audit sont lus séparément (`auth`, `patrimoine`, `inventaire`) et ne sont jamais fusionnés.

## Normalisation des données et conventions

### Organisation des données

- **Une entité métier par table** : comptes et structures dans `auth`, biens et documents dans `patrimoine`, campagnes et constats dans `inventaire`. Les documents ne sont pas une liste de fichiers dans une colonne du bien : chaque pièce jointe possède son propre enregistrement.
- **Identifiants UUID** pour les entités métier. Le code patrimoine est une référence fonctionnelle unique, distincte de l'identifiant interne. Des tables techniques peuvent utiliser d'autres clés.
- **Relations locales** : les relations entre tables d'un même service peuvent utiliser des clés étrangères. Les relations entre services utilisent uniquement des UUID, sans jointure SQL ni clé étrangère entre bases.
- **Identités centralisées** : les noms des personnes et structures restent dans `auth`. Le web les résout par appels groupés ; les services métier conservent leurs identifiants.
- **Historique explicite** : l'état de la fiche au moment d'un contrôle est conservé dans `etat_fiche`. Cette copie est volontaire : modifier ultérieurement le bien ne doit pas réécrire le constat historique.
- **Décisions séparées** : une validation ou un traitement est un nouvel enregistrement, pas une modification du mouvement ou du contrôle d'origine.

Ces principes expliquent la normalisation retenue et les copies historiques nécessaires ; ils ne constituent pas une certification formelle de conformité à une forme normale SQL.

### Nommage et formats

| Élément | Convention |
|---|---|
| Champs métier et JSON | Français en `snake_case`, par exemple `structure_id`, `etat_constate` |
| Champs Laravel standards | Noms conservés : `name`, `email`, `password`, `created_at`, `updated_at` |
| Montants | Entiers en FCFA, sans calcul monétaire en virgule flottante |
| Code patrimoine | `PAT-{ministere_code}-{année}-{numéro}`, numéro sur au moins quatre chiffres, génération transactionnelle |
| Dates | Valeurs validées côté API ; affichage français dans les vues et documents |
| Permissions | Contrôlées dans les services ; masquer un bouton ne remplace pas une autorisation serveur |
| Erreurs | Codes HTTP standards et messages JSON ; les appels à un service indisponible peuvent renvoyer `503` |
| Code PHP | Classes et conventions Laravel, style vérifié avec Laravel Pint |

Les gardes « ajout seul » protègent les opérations Eloquent et les routes exposées. Ils ne remplacent pas des permissions SQL strictes ou une protection contre un accès direct à la base.

## Installation

Prérequis : Git et Docker avec Docker Compose v2. Sur Windows, Docker Desktop doit être démarré en mode conteneurs Linux ; les commandes ci-dessous utilisent Git Bash. PHP, Composer et PostgreSQL sont exécutés dans les conteneurs.

Les ports locaux `8080`, `5432` et `5050` doivent être disponibles. Une connexion Internet est nécessaire lors du premier téléchargement des images et dépendances.

```bash
git clone https://github.com/samTompkins17/Koffr-ix.git koffr-ix
cd koffr-ix

# 1. Secret JWT partagé (fichier non commité)
cp .env.example .env
# remplacer JWT_SECRET par une valeur aléatoire, par exemple :
#   node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"

# 2. Construction et démarrage
docker compose up --pull always -d --build

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
- Le script PostgreSQL `docker/postgres/init.sql` s'exécute uniquement à l'initialisation d'un volume vide. Une reconstruction des images ne l'applique pas à une base existante : ne pas supprimer le volume pour contourner un problème de droits.
- Les entrypoints préparent l'environnement et les clés, mais les migrations et les seeders restent les étapes manuelles indiquées ci-dessus.

### Variables principales

| Variable | Utilisation | Configuration locale |
|---|---|---|
| `JWT_SECRET` | Signature et vérification des jetons par les API | `.env` racine, valeur aléatoire obligatoire |
| `JWT_TTL` | Durée de validité en minutes | `.env` racine, 15 par défaut |
| `ALLOWED_EMAIL_DOMAIN` | Restriction de domaine à la connexion | `.env` racine, vide pour la démonstration |
| `APP_URL` | URL de l'application | Valeur locale dans le Compose, à adapter au déploiement |
| `PUBLIC_VERIFY_URL` | Préfixe des URL encodées dans les QR | `patrimoine`, `http://localhost:8080/verifier` en local |
| `GATEWAY_URL` | Passerelle appelée par le web | Adresse interne `http://gateway` |
| `PATRIMOINE_URL` | API appelée par l'inventaire | Adresse interne `http://patrimoine:8080` |
| `DB_*` | Base et rôle dédiés au service | Fournis par le Compose |

Les variables actuellement écrites en dur dans le Compose ne deviennent pas configurables simplement en les ajoutant au `.env` racine : il faut aussi adapter leur injection au déploiement.

### Vérification et dépannage

```bash
docker compose ps
docker compose logs --tail=100 gateway web auth patrimoine inventaire
curl http://localhost:8080/api/auth/ping
curl http://localhost:8080/api/patrimoine/ping
curl http://localhost:8080/api/inventaire/ping
```

| Symptôme | Vérification |
|---|---|
| Application inaccessible | Docker démarré, conteneurs actifs, port 8080 disponible ; consulter les logs |
| Erreur PostgreSQL de permission | Vérifier l'utilisateur du service et les droits du schéma sur le volume existant |
| Jeton invalide après connexion | Vérifier que les trois API utilisent le même secret ; ne jamais l'afficher dans un ticket public |
| Retour vers « Session expirée » | Se reconnecter ; un jeton de démonstration expire normalement après 15 minutes |
| Caméra indisponible sur téléphone | Utiliser HTTPS et autoriser la caméra ; sinon saisir le code manuellement |
| QR inaccessible depuis un autre appareil | `localhost` désigne l'appareil qui scanne ; configurer l'adresse publique avant de générer les étiquettes à distribuer |
| Fichier refusé | Vérifier PDF/JPG/PNG et la limite de 10 Mo ; contrôler aussi les limites PHP et Nginx |

Pour arrêter sans effacer les volumes : `docker compose stop`. Pour reprendre : `docker compose up -d`.

## Hébergement gratuit et sécurité

**État : déploiement public non réalisé dans cette procédure.** Le Compose fourni sert au développement local ; il ne doit pas être exposé tel quel sur Internet.

L'application nécessite un environnement exécutant PHP et PostgreSQL, avec un réseau entre services et du stockage persistant pour les documents et photos. GitHub Pages seul ne peut pas exécuter cette architecture.

Une machine virtuelle gratuite permettant Docker Compose évite de réécrire les quatre applications. **Oracle Cloud Always Free est une piste à valider**, selon l'éligibilité du compte et la capacité disponible dans la région. L'offre gratuite est distincte des crédits d'essai : les quotas doivent être contrôlés, une carte bancaire est demandée pour la vérification du compte et certaines instances inactives peuvent être récupérées. Aucun hébergement gratuit ne doit être présenté comme une garantie de disponibilité ou de conservation des données.

Références de l'hébergeur : [ressources Always Free](https://docs.oracle.com/iaas/Content/FreeTier/freetier_topic-Always_Free_Resources.htm) et [conditions d'inscription](https://www.oracle.com/cloud/free/faq/).

### Préparation obligatoire avant publication

1. Choisir l'offre et vérifier ses quotas gratuits, son stockage persistant et les architectures CPU prises en charge par les images.
2. Préparer une configuration de déploiement distincte : environnement de production, débogage désactivé dans la configuration effectivement chargée et serveur PHP adapté, au lieu de `artisan serve`.
3. Remplacer les identifiants de développement et fournir les secrets hors Git et hors images Docker. Les `.dockerignore` doivent exclure les `.env` avant toute construction destinée à un registre.
4. N'exposer que la passerelle publique en HTTPS. PostgreSQL et pgAdmin ne doivent pas être accessibles publiquement ; conserver les communications métier sur le réseau interne.
5. Configurer les URL publiques, les cookies sécurisés et les en-têtes du proxy HTTPS. Conserver des clés applicatives stables entre les redémarrages.
6. Initialiser les bases, appliquer les migrations et ne charger que des données fictives. Les comptes administrateurs ne doivent pas conserver le mot de passe public de démonstration.
7. Sauvegarder les bases et les fichiers privés, puis tester leur restauration. Un volume persistant n'est pas une sauvegarde.
8. Vérifier les parcours avec l'adresse HTTPS réelle, y compris le scan depuis un téléphone, les droits, les téléchargements privés et les exports PDF.
9. Renseigner dans ce README l'URL validée et le commit déployé, sans y inscrire de secret ni d'accès administrateur.

## Comptes de démonstration

Mot de passe commun : `password`. Environnement de développement uniquement.

| Compte | Rôle | Droits principaux |
|---|---|---|
| `comptable@koffr.local` | comptable des matières | créer/modifier un bien, déclarer un mouvement, ouvrir une campagne, traiter les écarts |
| `agent@koffr.local` | agent du patrimoine | scan de contrôle et constat |
| `controleur@koffr.local` | contrôleur | consultation, liste des écarts (lecture seule) |
| `responsable@koffr.local` | responsable de service | déclaration de mouvement, validation ou refus des affectations et transferts |
| `admin@koffr.local` | administrateur | biens, consultation, création de comptes et de structures |

Les trois premiers comptes sont proposés sur l'écran de connexion.

## Parcours de démonstration

1. **Connexion** avec `comptable@koffr.local` / `password`.
2. **Créer un bien** : « Biens » → « + Nouveau bien ». Le code patrimoine est généré
   automatiquement (`PAT-SAN-2026-0413`) et restera définitif.
3. **Imprimer l'étiquette** : fiche du bien → « Étiquette QR » → « Imprimer ».
   Le QR code encode `http://localhost:8080/verifier/PAT-…`.
4. **Transférer le bien** : fiche → « Transférer le bien », ou menu « Affectations et transferts ».
   Choisir le bien, le type (affectation, transfert, maintenance, sortie), la destination et le motif.
   Affectation et transfert sont créés **en attente de validation** : avec `responsable@koffr.local`,
   la section « En attente de validation » de la même page permet de valider ou de refuser, avec un
   motif. La structure du bien change à la validation seulement ; un refus le laisse dans sa
   structure d'origine. Sortie et maintenance s'appliquent immédiatement. Un mouvement déjà décidé
   ne peut plus l'être (l'API répond `409`).
5. **Ouvrir une campagne** : « Inventaire » → formulaire « Ouvrir une campagne » en bas de page.
6. **Contrôler par scan** : se connecter avec `agent@koffr.local`, « Contrôle par scan »,
   saisir le code (ou scanner le QR code au téléphone), choisir l'état constaté « ● Hors service ».
   L'encart « Écart détecté » s'affiche dès que l'état diffère de la fiche.
7. **Traiter l'écart** : avec `comptable@koffr.local`, menu « Écarts et rapports » → « Traiter l'écart » :
   choisir l'action (régularisé, réformé, main levée, autre) et saisir un commentaire, tous deux
   obligatoires. Le constat d'origine n'est jamais modifié ; la décision, son auteur et sa date
   apparaissent dans la colonne « Traitement ». Un écart ne peut être traité qu'une fois.
   Avec `controleur@koffr.local`, la même liste reste en lecture seule.
8. **Vérifier publiquement** : ouvrir `http://localhost:8080/verifier` sans être connecté,
   puis saisir le code (ou ouvrir directement `http://localhost:8080/verifier/<code>`).
   Seuls le statut « bien de l'État », l'immatriculation, la catégorie et le ministère apparaissent.
9. **Administration** : avec `admin@koffr.local`, menu « Utilisateurs et rôles », trois onglets :
   « Comptes » (liste et formulaire « Créer un compte » : nom, e-mail, mot de passe provisoire,
   rôle, structure), « Rôles » (matrice des droits réellement appliqués par les services) et
   « Structures » (biens rattachés, responsable, formulaire « Créer une structure » : nom, code,
   type, région, structure parente). Un e-mail déjà utilisé ou un code déjà pris répond `422`
   sans rien modifier ; les créations sont tracées dans le journal d'authentification.
10. **Carte** : menu « Carte » : les biens géolocalisés, avec leur fiche en lien.
11. **Vue nationale** : menu « Vue nationale » : compteurs par ministère, taux d'inventaire et écarts.
12. **Procès-verbaux** : menu « Procès-verbaux » → « Générer le PV » (inventaire d'une campagne).
13. **Journal d'audit** : avec `admin@koffr.local` ou `controleur@koffr.local`, menu « Journal d'audit » :
    les trois journaux séparés (auth, patrimoine, inventaire), filtrés par action, en lecture seule
    (une entrée par appel : connexion, création, mouvement, validation, traitement, scan).

Le contrôleur ne peut rien écrire : les boutons de création disparaissent et l'API répond `403`.
Les étapes 1 à 7 suffisent pour une démonstration courte ; 10 à 13 montrent les écrans de
consultation construits sur les mêmes données.

### Documents, étiquettes et exports

- Fiche d'un bien → **Ajouter un document** (admin ou comptable) : type et fichier PDF/JPG/PNG, 10 Mo maximum. Téléchargement réservé aux utilisateurs connectés ; aucun remplacement ni suppression.
- Liste des biens → cocher les biens de la page → **Préparer les étiquettes sélectionnées** : aperçu, PDF A4 et ZIP de planches SVG. L'étiquette unitaire 80 × 50 mm reste disponible sur la fiche.
- Biens et écarts → **Exporter en PDF** : le fichier est généré côté serveur et conserve les filtres. Les écarts restent réservés au comptable et au contrôleur. Les exports CSV et Excel ne sont pas disponibles.
- Aperçu d'un PV → **Télécharger PDF** ; l'impression navigateur reste possible.
- Accéder à une page interdite au rôle affiche une vraie réponse **403**, avec explication et retour au tableau de bord.

La migration `documents` s'applique avec `docker compose exec patrimoine php artisan migrate`. Les fichiers restent dans `patrimoine_storage` ; aucun nouveau service ni accès public au stockage.

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
| GET | `/api/auth/hierarchie` | structures (id, nom, code, type, parent) pour la vue nationale |
| POST | `/api/auth/comptes` | admin (création d'un compte) |
| POST | `/api/auth/structures` | admin (création d'une structure) |
| GET | `/api/auth/annuaire` | admin (page Administration : comptes et structures, sans e-mail) |
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
| GET/POST | `/api/patrimoine/biens/{id}/documents` | lecture authentifiée ; ajout admin, comptable (multipart `type`, `fichier`) |
| GET | `/api/patrimoine/biens/{bienId}/documents/{documentId}` | téléchargement privé authentifié |
| GET | `/api/patrimoine/mouvements/recents?limite=` | tous (derniers mouvements, avec code du bien) |
| GET | `/api/patrimoine/mouvements/en-attente` | admin, comptable, responsable |
| POST | `/api/patrimoine/mouvements/{id}/valider` | responsable (met à jour la structure) |
| POST | `/api/patrimoine/mouvements/{id}/refuser` | responsable |
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
| POST | `/api/inventaire/ecarts/{id}/traitement` | comptable (décision documentée) |
| GET | `/api/inventaire/controles/bien/{code}` | agent (fiche + dernier état connu) |
| POST | `/api/inventaire/controles` | agent (détecte l'écart, 409 si déjà contrôlé) |

## Tests et qualité

```bash
docker compose exec auth php artisan test        # authentification, rôles, JWT
docker compose exec patrimoine php artisan test  # biens, code unique, QR, mouvements
docker compose exec inventaire php artisan test  # campagnes, contrôles, écarts, 503
docker compose exec web php artisan test         # accès, fichiers et vues
```

La suite web couvre notamment les accès par rôle, documents, étiquettes, exports PDF et procès-verbaux. Le nombre exact de tests et d'assertions est celui affiché par ces commandes pour le commit livré ; un ancien total n'est pas une preuve de validation de la version courante.

Les tests métier utilisent les bases dédiées `*_test` (voir `tests/TestCase.php`) et les tests web simulent les appels API. Ne pas remplacer leur configuration par les coordonnées d'une base de production.

Contrôle du style sans réécriture :

```bash
docker compose exec auth vendor/bin/pint --test
docker compose exec patrimoine vendor/bin/pint --test
docker compose exec inventaire vendor/bin/pint --test
docker compose exec web vendor/bin/pint --test
```

Avant livraison, exécuter également le parcours de démonstration dans un navigateur sur ordinateur et téléphone : les tests automatisés ne valident pas à eux seuls la caméra, la mise en page ou le contenu visuel des PDF.

## Limites assumées

- **Secret JWT partagé (HS256)** : un service compromis pourrait fabriquer de faux jetons.
  En production : clé privée dans `auth`, clé publique (RS256) dans les autres services.
- **Une seule instance PostgreSQL** avec trois bases métier et des rôles dédiés (`koffr_auth`, `koffr_patrimoine`, `koffr_inventaire`). Le script accorde les droits nécessaires, mais ne révoque pas explicitement les droits de `PUBLIC` : l'isolation SQL doit être durcie et vérifiée avant une exposition publique.
- **Photos de contrôle** stockées dans un volume du service `inventaire`, sans `document-service`.
- **Résilience partielle** : seul le scan dépend de `patrimoine`.
- **Documents joints** : fichiers PDF/JPG/PNG privés, 10 Mo maximum, sans antivirus intégré ; n'utiliser que des fichiers fictifs fiables pour la démonstration. Une photo jointe ne remplace pas la vignette de galerie.
- **Toujours en maquette** : mode hors ligne (le scan exige le réseau) et mot de passe oublié, laissés de côté par choix explicite.
- **Planches** : sélection de 1 à 100 biens, A4 à 12 étiquettes de 105 × 48 mm ; imprimer à 100 %, sans ajustement.

Les choix techniques non tranchés par `AGENTS.md` sont consignés dans `docs/DECISIONS.md`.

## Checklist de livraison

Cette liste est à vérifier sur le commit livré ; elle ne représente pas un déploiement déjà effectué.

- [ ] Dépôt GitHub accessible au jury ou au commanditaire et contenant la version à livrer.
- [ ] Aucun `.env`, secret, fichier téléversé privé ou contenu de base de données dans le dépôt ou les images publiées.
- [ ] Installation validée depuis un clone propre dans un environnement distinct, sans effacer les volumes de travail.
- [ ] Tests des quatre applications exécutés et résultat associé au commit livré.
- [ ] Application disponible sur une URL HTTPS, offre gratuite et limites vérifiées.
- [ ] Migrations appliquées, données fictives uniquement, accès de démonstration séparés des accès administrateurs.
- [ ] Contrôle mobile, QR publics, fichiers privés, planches et exports PDF vérifiés sur l'hébergement.
- [ ] Sauvegarde et restauration des bases, documents et photos testées.
- [ ] Adresse de l'application, identifiant du commit et limites connues renseignés dans ce README.
- [ ] Licence du code du projet clarifiée avant redistribution ; les licences des dépendances et droits sur les éléments graphiques restent distincts.

Documents complémentaires : [décisions techniques](docs/DECISIONS.md), [API auth](docs/api/auth.yaml), [API patrimoine](docs/api/patrimoine.yaml) et [API inventaire](docs/api/inventaire.yaml).
