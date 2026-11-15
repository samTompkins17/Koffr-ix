# Cahier des charges KOFFR-IX

KOFFR-IX (anciennement GEPAT) — plateforme de gestion électronique du patrimoine de l'État

Gestion Électronique du Patrimoine de l'État · Version du 11 novembre 2026, corrigée et alignée sur le code livré, les schémas UML et `docs/DECISIONS.md`.

> Ce document est la version corrigée du cahier des charges. L'original PDF (`docs/Cahier des charges KOFFR-IX.pdf`) n'est pas modifié. Les corrections appliquées sont listées dans `correction.md`.

---

## 1. Contexte et problématique

Le Togo dispose déjà du cadre légal de la comptabilité des matières. La directive n°03/2012/CM/UEMOA du 29 juin 2012 l'impose, et le décret n°2016-060/PR du 4 mai 2016 l'a transposée pour l'État, ses établissements publics et les collectivités territoriales. Deux décrets adoptés le 18 septembre 2024 créent les postes comptables matières dans les ministères et institutions, ainsi que les trésoreries ministérielles.

Le processus prévu comporte quatre étapes : inventaire du patrimoine non financier, valorisation des biens, codification et immatriculation, enregistrement comptable des résultats. Un comptable matières assure la garde et la conservation des biens, tient la comptabilité des matières et rend le compte de gestion à la Cour des comptes. Environ 41 comptables principaux sont prévus ; au 26 novembre 2024, aucun n'était encore nommé.

**Problème à résoudre.** Les comptables matières qui prennent leurs fonctions ont besoin d'un outil commun pour inventorier, immatriculer et suivre les biens jusqu'à leur sortie du patrimoine. Sans outil partagé, chaque structure risque de tenir ses propres tableaux, difficiles à consolider et à contrôler.

**Hypothèses de travail (à valider avec le commanditaire).** Ces constats ne viennent pas des sources officielles :

- les données patrimoniales sont dispersées entre ministères, collectivités et établissements ;
- les inventaires sont surtout manuels et difficiles à mettre à jour ;
- les mouvements (affectation, transfert, réparation, perte, cession, réforme) sont mal tracés ;
- les terrains et bâtiments sont difficiles à localiser et à documenter ;
- il n'existe pas de tableau de bord national fiable pour les décideurs.

## 2. Présentation de la solution

KOFFR-IX est une plateforme web qui donne à chaque bien public un identifiant unique, un responsable, une localisation et un historique non modifiable. Chaque bien porte un QR code qui ouvre sa fiche. L'inventaire se fait en scannant le QR code ; le système compare l'état constaté à la fiche et signale seul les écarts, au lieu de consolider des tableurs à la main.

## 3. Objectifs et indicateurs de réussite

| N° | Objectif | Indicateur de réussite |
|---|---|---|
| 1 | Identifier chaque bien de façon unique | Tout bien enregistré reçoit un code patrimoine unique et un QR code imprimable |
| 2 | Garder la mémoire de chaque bien | Chaque affectation, transfert ou sortie est ajouté à l'historique avec sa date et son auteur ; aucune ligne ne peut être supprimée |
| 3 | Rendre l'inventaire plus rapide et plus fiable | Un agent contrôle un bien en le scannant ; l'écart entre l'état constaté et la fiche est signalé automatiquement |
| 4 | Faciliter le contrôle | Le contrôleur consulte l'historique d'un bien et la liste des écarts sans pouvoir rien modifier |
| 5 | Livrer une démonstration complète dans le délai du brief | Parcours créer un bien, le transférer, le contrôler, fonctionnel de bout en bout sous Docker en une semaine |

## 4. Parties prenantes et utilisateurs

### 4.1 Parties prenantes institutionnelles

- Ministère chargé de la protection du patrimoine de l'État : commanditaire du brief.
- Direction générale du Trésor et de la Comptabilité publique (DGTCP) : conduit la réforme de la comptabilité des matières et organise la formation des acteurs.
- Cour des comptes : reçoit le compte de gestion des matières.
- Ministères, institutions et collectivités : détiennent les biens et accueillent les postes comptables matières.

### 4.2 Profils utilisateurs

| Profil | Rôle dans KOFFR-IX | Ce qu'il attend |
|---|---|---|
| Administrateur | Gère utilisateurs, structures, référentiels et rôles | Créer des comptes et attribuer des rôles |
| Comptable matières | Enregistre, immatricule et suit les biens de son ministère | Créer et suivre les biens, transférer, lancer un inventaire |
| Agent de patrimoine | Fait l'inventaire sur le terrain : scan, photo, état, position | Scanner un bien et saisir son état en quelques gestes |
| Responsable de service | Valide affectations, transferts et sorties | Valider ou refuser avec un motif retracé |
| Contrôleur / auditeur | Consulte l'historique et les écarts, sans modifier | Consulter sans pouvoir modifier |
| Public | Vérifie qu'un bien appartient à l'État | Scanner l'étiquette et voir une page minimale |

Ces portraits sont des hypothèses de travail à affiner avec le commanditaire.

En démonstration, l'écran de connexion propose 3 profils (comptable matières, agent de patrimoine, contrôleur). Les 5 rôles sont tous créés dans la base de démonstration (comptes `admin@`, `comptable@`, `agent@`, `controleur@`, `responsable@koffr.local`).

## 5. Périmètre fonctionnel

### 5.1 Priorité 1 : le cœur (codé pendant la semaine)

1. **Authentification et rôles** : connexion, 5 profils, structures (ministères, directions, services).
2. **Gestion des biens** : fiche avec type, désignation, valeur, date d'acquisition, structure, responsable et localisation ; code patrimoine unique généré par le système.
3. **QR codes** : un QR code par bien, avec étiquette imprimable.
4. **Affectation et transfert** : le cycle de vie couvre aussi la maintenance et la sortie (réforme, cession, perte), avec un historique en ajout seul ; validation par le responsable de service.
5. **Inventaire mobile** : campagne, ouverture de la fiche par scan du QR code depuis le navigateur du téléphone, état physique, détection automatique des écarts ; photo et position GPS jointes au contrôle.

### 5.2 Priorité 2 : la valeur métier

6. **Documents** : factures, actes, titres et photos rattachés à un bien.
7. **Tableau de bord** : biens par structure, catégorie et état, taux d'inventaire.
8. **Rapports et exports** : PDF des biens et écarts, avec les filtres actifs.
9. **Journal d'audit** : actions sensibles, consultable par le contrôleur et l'administrateur.

### 5.3 Priorité 3 : la finition

10. **Vérification publique par QR code** : page publique minimale (bien de l'État, immatriculé, ministère de rattachement), sans donnée sensible.
11. **Procès-verbaux automatiques** : PV d'inventaire et PV de sortie, générés en PDF et HTML imprimable (A4) prêts à signer.
12. **Carte de localisation** : biens localisés sur une carte OpenStreetMap (Leaflet).
13. **Vue nationale pour les décideurs** : valeur totale du patrimoine, nombre de biens, taux d'inventaire par ministère.

Numérotation unique de 1 à 13. Si le temps manque, on retire par le bas : la vue nationale et la carte d'abord, puis la vérification publique, sans casser le reste.

### 5.4 Périmètre de démonstration (brief individuel, une semaine)

| N° | Fonctionnalité | État | Contenu de la démonstration |
|---|---|---|---|
| 1 | Authentification et rôles | Codé | Connexion JWT, 5 rôles (3 proposés sur l'écran de connexion), session expirée |
| 2 | Gestion des biens | Codé | Création, modification, liste (tableau et galerie), fiche, code patrimoine unique |
| 3 | QR codes | Codé | Étiquette unitaire 80 × 50 mm, planches A4 de 12 étiquettes 105 × 48 mm (PDF et SVG) |
| 4 | Affectation et transfert | Codé | Transfert, maintenance, sortie, validation ou refus par le responsable de service, historique en ajout seul |
| 5 | Inventaire mobile | Codé | Campagnes, scan caméra ou saisie manuelle, état constaté, photo, position GPS, détection d'écart |
| 6 | Documents | Codé | Pièces PDF/JPG/PNG privées, 10 Mo maximum, ajout seul, ajout par admin/comptable et téléchargement authentifié |
| 7 | Tableau de bord | Codé | Compteurs, répartitions, mouvements récents |
| 8 | Rapports et exports | Codé | PDF des biens et écarts, filtres conservés |
| 9 | Journal d'audit | Codé | Consultation en lecture seule des trois journaux (admin, contrôleur) |
| 10 | Vérification publique | Codé | Page publique minimale, sans donnée sensible |
| 11 | Procès-verbaux | Codé | PV d'inventaire et PV de sortie, mise en page A4 imprimable |
| 12 | Carte | Codé | Leaflet et OpenStreetMap |
| 13 | Vue nationale | Codé | Valeur, nombre de biens et taux d'inventaire par ministère |

Traitement d'un écart, création de comptes et de structures, planches d'étiquettes A4 (12 emplacements de 105 × 48 mm, PDF et SVG) et page d'accès refusé HTTP 403 sont également codés. Le 14 novembre 2026, le développeur confirme ces ajouts et laisse explicitement le mode hors ligne et le mot de passe oublié de côté.

### 5.5 Hors périmètre (à faire plus tard)

- application mobile native et mode hors ligne complet avec synchronisation ;
- notifications et alertes par email et SMS ;
- alertes par règles simples (bien non contrôlé depuis longtemps, sans responsable ou sans titre, écarts répétés) ;
- journal d'audit infalsifiable, où chaque entrée contient l'empreinte de la précédente ;
- statut d'occupation des terrains et bâtiments ;
- signalement citoyen d'un bien public dégradé ou occupé illégalement ;
- assistant en langage naturel sur un modèle local ;
- valorisation comptable et amortissement ;
- interconnexion avec les systèmes du Trésor et du Budget ;
- supervision (Prometheus, Grafana), intégration continue et Kubernetes ;
- réinitialisation de mot de passe en libre-service (explicitement laissée de côté).

## 6. Besoins fonctionnels détaillés (priorité 1)

Chaque user story a des critères d'acceptation, qui servent aussi de liste de tests.

| N° | User story | Critères d'acceptation |
|---|---|---|
| US1 | En tant qu'utilisateur, je veux me connecter avec mon email et mon mot de passe afin d'accéder aux fonctions de mon rôle | Un identifiant erroné affiche une erreur sans dire lequel est faux ; le menu dépend du rôle ; sans connexion, on est renvoyé vers la page de connexion |
| US2 | En tant que comptable matières, je veux créer la fiche d'un bien afin de l'immatriculer | Désignation, catégorie, valeur, date d'acquisition et structure obligatoires ; un code patrimoine unique est généré ; le code ne peut plus être modifié |
| US3 | En tant que comptable matières, je veux consulter et filtrer la liste des biens afin de retrouver un bien | Filtres par catégorie, structure et état ; recherche par désignation ou par code ; un clic ouvre la fiche |
| US4 | En tant que comptable matières, je veux imprimer l'étiquette QR code d'un bien afin de l'identifier physiquement | L'étiquette porte le code et le QR code ; le scan ouvre la fiche du bien |
| US5 | En tant que comptable matières, je veux transférer un bien vers une autre structure afin de suivre son changement d'affectation | Structure de destination et motif obligatoires ; le mouvement passe en attente ; la structure du bien est mise à jour à la validation par le responsable de service |
| US6 | En tant que comptable matières ou contrôleur, je veux consulter l'historique d'un bien afin de connaître son parcours | Mouvements listés du plus récent au plus ancien ; aucune modification ni suppression possible |
| US7 | En tant que comptable matières, je veux ouvrir une campagne d'inventaire pour une structure afin d'organiser le contrôle | Nom, structure et dates obligatoires ; la campagne couvre les biens de la structure |
| US8 | En tant qu'agent de patrimoine, je veux scanner le QR code d'un bien et saisir son état afin d'enregistrer mon contrôle | La fiche s'affiche après le scan ; l'état constaté est obligatoire ; le contrôle est enregistré avec sa date et son agent |
| US9 | En tant qu'agent de patrimoine, je veux être prévenue quand l'état constaté diffère de la fiche afin de signaler l'anomalie | L'écart est détecté automatiquement ; il apparaît dans la liste des écarts de la campagne |
| US10 | En tant que contrôleur, je veux consulter la liste des écarts d'une campagne afin de vérifier la conformité | Liste en lecture seule ; chaque ligne renvoie à la fiche du bien ; aucun bouton de modification |

La validation du transfert par le responsable de service (US5) et la photo et la position GPS jointes au contrôle (US8) sont codées.

## 7. Besoins non fonctionnels

| Catégorie | Exigence | Comment la vérifier |
|---|---|---|
| Sécurité | Accès par rôle, mots de passe hachés, HTTPS en production, secrets hors du dépôt. Le jeton JWT est signé en HS256 avec un secret partagé entre les services. En production, une paire de clés (RS256) remplacerait ce secret. | Se connecter avec chaque rôle et vérifier les menus ; le fichier `.env` n'est pas dans Git |
| Traçabilité | L'historique des mouvements est en ajout seul | Aucune route de modification ni de suppression sur les mouvements |
| Usage terrain | Les pages et le scan fonctionnent sur téléphone | Test sur un vrai téléphone, en scannant une étiquette |
| Résilience | La panne d'un service n'arrête pas les autres. Sans `patrimoine`, la liste des biens et le contrôle par scan sont indisponibles, mais la connexion et les campagnes d'inventaire continuent de fonctionner. | Arrêter un service et vérifier que les autres répondent |
| Indépendance des services | Chaque service a sa base et son conteneur. Trois rôles PostgreSQL dédiés : chaque service ne peut accéder qu'à sa propre base. | `docker compose up` d'un service seul ; aucune requête d'un service vers la base d'un autre |
| Interopérabilité | API REST documentée (OpenAPI) et export CSV | La documentation s'ouvre ; l'export se lit dans un tableur |
| Accessibilité | Contrastes suffisants, statuts jamais signalés par la seule couleur | Contrastes calculés (au moins 4,5) ; icône ou texte à côté de chaque couleur de statut |
| Données personnelles | Données des agents limitées au nécessaire | Liste des champs personnels réduite à l'identité professionnelle ; cadre togolais à vérifier avant mise en service réelle |
| Documentation | README, guide d'utilisation, documentation de l'API | Un tiers installe et lance le projet en suivant le README |

## 8. Contraintes du brief

| Contrainte | Conséquence pour KOFFR-IX |
|---|---|
| Brief individuel, une semaine | Périmètre de démonstration étendu : les 5 fonctionnalités cœur codées, plus carte, journal, PV, vue nationale, validation, traitement d'écart et création de comptes |
| Stack Laravel uniquement | Services et application web en Laravel ; Nginx et PostgreSQL restent de l'infrastructure |
| Architecture micro-services | Application web et services (auth, patrimoine, inventaire) ; le service document est optionnel et non livré ; les photos de contrôle sont stockées dans un volume du service inventaire |
| Conception en UML | Cas d'utilisation, classes, séquence et déploiement |
| Maquettes | **32 écrans** livrés (Claude Design / canvas), 3 parcours cliquables |
| Conteneurisation avec Docker | Un conteneur par service ; une instance PostgreSQL commune contenant une base par service, lancés par Docker Compose |
| Livrables écrits | Analyse du besoin, proposition financière, fiche technique, guide d'utilisation |
| Évaluation sur 13 compétences | Checklist de couverture des compétences |

## 9. Existant et alternatives étudiées

| Critère | A. Tableurs par structure | B. Extension du système budgétaire et comptable existant | C. Progiciel de gestion d'actifs | D. KOFFR-IX sur mesure |
|---|---|---|---|---|
| Coût de départ | Très faible | Inconnu | Élevé (licences) | Moyen |
| Identification unique et QR code | Non | À vérifier | Souvent oui | Oui, au cœur du projet |
| Historique non modifiable | Faible | À vérifier | Variable | Oui, par conception |
| Adaptation au cadre togolais | Totale mais manuelle | Bonne | À adapter | Conçu pour ce cadre |
| Inventaire par scan sur téléphone | Non | À vérifier | Souvent oui | Oui |
| Maîtrise du code et de l'évolution | Totale | Dépend de l'État | Faible | Totale |

**Choix retenu : D.** Il répond au brief, garde la maîtrise du code et de la donnée, et se branche plus tard sur les systèmes de l'État par l'API et les exports. Les notes sont des appréciations qualitatives à confirmer. La voie B reste à explorer avant tout déploiement réel : le ministère des Finances et du Budget a publié un appel à manifestation d'intérêt pour un diagnostic de son système d'information budgétaire et comptable, et on ne sait pas s'il couvre déjà le suivi des biens.

## 10. Architecture technique

KOFFR-IX se découpe en une application web et trois services derrière une passerelle Nginx. Chaque service a sa propre base et tourne dans son propre conteneur. L'application web n'appelle que la passerelle ; aucun service ne lit la base d'un autre.

| Service | Responsabilité | Base | Dépend de |
|---|---|---|---|
| auth-service | Comptes, rôles, structures, émission du JWT | `auth_db` (PostgreSQL) | — |
| patrimoine-service | Fiches, code et QR code, affectations, transferts, sorties, statistiques, journal d'audit | `patrimoine_db` (PostgreSQL) | — (partage seulement la clé de vérification du JWT) |
| inventaire-service | Campagnes, contrôles par scan, détection des écarts, statistiques | `inventaire_db` (PostgreSQL) | patrimoine-service (appel REST interne, délai 3 s) |
| document-service | Non livré : pièces jointes gérées par patrimoine, photos de contrôle par inventaire | volumes des services concernés | — |

**Règles de communication.**

- Les services échangent en REST/JSON.
- Le JWT est signé par auth-service et vérifié **localement** par chaque service ; aucun service n'appelle auth-service pour traiter une requête.
- Les liens entre services passent par un UUID, jamais par une clé étrangère entre bases.
- Une panne de service n'arrête pas les autres. Sans `patrimoine`, le scan et la liste des biens sont indisponibles (réponse 503), mais la connexion et les campagnes d'inventaire fonctionnent.
- L'application web n'appelle que la passerelle. Le web résout les noms affichés via `GET /api/auth/users?ids=` et `GET /api/auth/structures`.

### 10.1 Stack

| Couche | Choix |
|---|---|
| Services | Laravel (API REST), un projet par service |
| Application web | Laravel (vues Blade), Tailwind, JavaScript ciblé |
| Authentification | JWT (**php-open-source-saver/jwt-auth**) |
| Base de données | PostgreSQL 16, une instance, une base et un rôle dédié par service |
| Passerelle | Nginx, HTTPS en production |
| Carte | Leaflet et OpenStreetMap |
| QR codes | simple-qrcode (génération), html5-qrcode (scan) |
| Exports | PDF (Dompdf) ; procès-verbaux PDF et HTML imprimable |
| Conteneurs | Docker et Docker Compose |
| Documentation API | OpenAPI (Swagger) |

### 10.2 Modèle de données

**auth_db** : `structures` (id, nom, `code`, type, region, parent_id) ; `utilisateurs` (id, nom, email unique, mot_de_passe_hash, role, structure_id, actif) ; `journal_audit` (ajout seul).

**patrimoine_db** : `biens` (code_unique unique et jamais modifié, `localisation`, `latitude`, `longitude`) ; `mouvements` (table en ajout seul, type parmi affectation, transfert, maintenance, sortie) ; `sequences_code` (génération atomique du code `PAT-{ministère}-{année}-{NNNN}`) ; `validations_mouvement` (ajout seul : décision, auteur, motif) ; `journal_audit` (ajout seul). `documents` (ajout seul : id, bien_id, type, nom_original, chemin privé, mime_type, taille, auteur_id, horodatages). Les fichiers sont privés dans le volume du service patrimoine. Les procès-verbaux sont générés à la demande, sans table.

**inventaire_db** : `campagnes` ; `controles` (un bien contrôlé une fois par campagne : unique sur campagne_id et bien_id ; `etat_fiche`, `etat_constate` — bon, usage, hors_service, introuvable —, `photo_chemin`, `ecart_detecte`, latitude et longitude) ; `traitements_ecart` (ajout seul) ; `journal_audit` (ajout seul).

**document-service** : non livré ; les documents joints sont gérés par `patrimoine`, sans nouveau service.

Les schémas UML (cas d'utilisation, classes, séquence, déploiement) et le schéma d'architecture sont fournis en fichiers Mermaid.

## 11. Maquettes

**32 écrans** couvrent le MVP et les fonctionnalités différenciantes. Les écrans de bureau sont en 1440 px ; l'écran de scan est conçu d'abord en 375 px, car il sert sur le terrain.

Écrans : connexion, tableau de bord, liste des biens, fiche d'un bien, création et modification d'un bien, affectation et transfert, campagnes d'inventaire, contrôle par scan (mobile), carte des biens, écarts et rapports, utilisateurs/rôles/structures, journal d'audit, page publique de vérification (mobile, sans connexion), procès-verbaux, vue nationale.

Trois parcours cliquables : créer un bien, transférer un bien, contrôler un bien.

Typographie : Inter. Palette retenue : charte graphique togolaise (vert `#006A4F`, rouge `#D11135`, jaune `#FFCF11`, noir et blanc). Un statut n'est jamais signalé par la couleur seule.

## 12. Livrables

- Analyse du besoin
- Maquettes : **32 écrans**, 3 parcours cliquables
- Conception UML : cas d'utilisation, classes, séquence, déploiement
- Solution en micro-services conteneurisés avec Docker
- Proposition financière
- Fiche technique
- Guide d'utilisation
- README et documentation OpenAPI

## 13. Planning (solo, 7 jours)

| Jour | Livrable |
|---|---|
| J1 | Analyse du besoin rédigée, cas d'utilisation et diagramme de classes |
| J2 | UML complet (séquence, déploiement), début des maquettes |
| J3 | Prototype cliquable du parcours « contrôler un bien », conteneurs Docker qui démarrent, bases créées |
| J4 | Connexion et rôles fonctionnels (auth-service), début du service patrimoine |
| J5 | Création de bien avec code et QR code, liste et fiche, transfert avec historique |
| J6 | Inventaire par scan avec détection d'écart, premiers tests de bout en bout |
| J7 | Tests finaux, guide d'utilisation, fiche technique, répétition de la soutenance ; bonus : vérification publique, compteurs, export CSV |

Point de contrôle fin J5 : création de bien, QR code et transfert doivent fonctionner. Sinon, J6 sert à les terminer et l'inventaire passe en version minimale (scan et état constaté seulement).

> Périmètre étendu en cours de semaine (carte, journal, PV, vue nationale, validation, traitement d'écart, création de comptes).

## 14. Budget

Hypothèse : taux journalier de 25 000 FCFA (profil développeur junior), montants hors taxes. La solution complète représente 83 jours-homme.

| Poste | Jours-homme | Montant (FCFA) |
|---|---|---|
| Analyse du besoin et cadrage | 5 | 125 000 |
| Conception UML et base de données | 5 | 125 000 |
| Maquettes et prototype | 8 | 200 000 |
| Back-end (auth 5, patrimoine 12, inventaire 8, documents 4 — optionnel, non livré dans la démonstration) | 29 | 725 000 |
| Front web : **32 écrans**, scan, carte, export CSV | 18 | 450 000 |
| Docker, passerelle et déploiement | 4 | 100 000 |
| Tests et recette | 5 | 125 000 |
| Documentation et guide d'utilisation | 4 | 100 000 |
| Formation des utilisateurs | 2 | 50 000 |
| Gestion de projet et soutenance | 3 | 75 000 |
| **Total réalisation** | **83** | **2 075 000** |

Ce budget reste l'estimation initiale (83 jours-homme, 2 075 000 FCFA), avec 4 jours pour un service documentaire optionnel distinct. L'ajout simplifié de documents dans patrimoine ne constitue pas la livraison de ce service ; l'extension du 14 novembre n'a pas fait l'objet d'un nouveau chiffrage.

**Exploitation annuelle (estimation)**

| Calcul | Montant (FCFA) |
|---|---|
| Hébergement du serveur (20 000 × 12) | 240 000 |
| Nom de domaine (forfait annuel) | 30 000 |
| Sauvegardes externes (5 000 × 12) | 60 000 |
| Maintenance corrective et évolutive (10 % de la réalisation) | 207 500 |
| **Total annuel** | **537 500** |

Total première année : 2 612 500 FCFA. Les coûts d'exploitation sont des estimations à remplacer par des devis.

## 15. Risques

| Risque | Probabilité | Impact | Réponse prévue |
|---|---|---|---|
| Une semaine ne suffit pas pour 5 fonctionnalités et 3 services | Élevée | Élevé | Point de contrôle au jour 5 ; inventaire en version minimale ; passage à 2 services si besoin |
| Les services ne communiquent pas sous Docker | Moyenne | Élevé | Lancer un service à la fois ; healthchecks ; tester chaque appel avant d'ajouter le suivant |
| Le scan par caméra ne démarre pas sur certains téléphones | Moyenne | Moyen | La caméra exige HTTPS ou localhost ; tester tôt ; prévoir la saisie manuelle du code |
| Les hypothèses sur l'état des inventaires sont fausses | Moyenne | Moyen | Les présenter comme hypothèses ; les valider avec le commanditaire |
| Données personnelles mal protégées | Faible en démo | Élevé en production | Données fictives en démo ; vérifier le cadre togolais avant tout déploiement |
| Secret JWT partagé compromis | Faible en démo | Élevé en production | Passer à RS256 (clé privée dans auth seul) |
| Instance PostgreSQL unique | Faible | Élevé | Une instance par service en production, sauvegardes |
| Soutenance mal préparée | Moyenne | Moyen | Répétition au jour 7 avec les réponses aux questions probables |

## 16. Questions à poser au commanditaire

1. Existe-t-il déjà un registre ou un outil de suivi des biens ? Dans quel format ?
2. Quelles catégories de biens sont prioritaires : immeubles, véhicules, matériel informatique, mobilier ?
3. Qui valide les transferts et les sorties, et à quel niveau ?
4. Les agents de terrain disposent-ils de téléphones et d'une connexion pour l'inventaire ?
5. Quels états le contrôle et la Cour des comptes attendent-ils ?
6. Faut-il se brancher aux systèmes du Trésor et du Budget, et lesquels ?
7. Où sera hébergé le système : serveur de l'État ou hébergeur externe ?
8. Quelles règles de protection des données s'appliquent aux informations des agents ?

## 17. Limites assumées

- Les constats sur l'état actuel des inventaires dans les ministères sont des hypothèses à valider.
- Le mode hors ligne, les SMS, la valorisation comptable et l'interconnexion avec le Trésor sont hors du MVP.
- Les coûts d'exploitation sont des estimations.
- Le cadre togolais de protection des données personnelles reste à vérifier avant toute mise en service réelle.
- Le jeton JWT utilise un secret partagé (HS256) ; en production, une paire de clés (RS256).
- Les trois bases partagent une seule instance PostgreSQL ; en production, une instance par service.
- Les mots de passe de démonstration sont écrits dans la configuration Docker ; en production, des secrets gérés hors du dépôt.
- Le scan par caméra exige HTTPS ou localhost ; la saisie manuelle du code est toujours disponible.
- Non codés par choix explicite : mode hors ligne et mot de passe oublié.
- Documents joints : stockage privé et contrôle du format, mais pas d'antivirus intégré ; fichiers fictifs fiables uniquement en démonstration.
- Planches : sélection de 1 à 100 biens, désignations longues abrégées sur papier et impression à taille réelle (100 %).
- La compétence C8 (CMS ou e-commerce) est peu couverte par le cœur de KOFFR-IX : option d'un portail public de transparence sous CMS, à clarifier avec l'évaluateur.

## Sources

Ministère des Finances et du Budget : atelier de Kpalimé sur le recensement des biens des collectivités (31 juillet 2023) ; décrets du 18 septembre 2024 sur les postes comptables matières (26 novembre 2024) ; page d'accueil (appel à manifestation d'intérêt sur le diagnostic du système d'information budgétaire et comptable). Pages lues le 10 novembre 2026.
