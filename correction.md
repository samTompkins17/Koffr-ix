# Corrections à apporter au cahier des charges KOFFR-IX

Ce fichier liste, section par section, ce qu'il faut modifier dans `Cahier des charges KOFFR-IX.pdf`
(version du 11 nov. 2026) pour l'aligner sur le code du dépôt, les schémas UML corrigés et
`docs/DECISIONS.md`. Chaque correction indique **le texte actuel** puis **le texte à mettre**.

Légende : 🔴 contradiction avec le code ou les schémas · 🟠 imprécision à corriger · 🟢 mise à jour facultative.

---

## Titre et nom du produit 🔴

- Actuel : « Cahier des charges GEPAT », « Gestion Électronique du Patrimoine de l'État », et GEPAT partout dans le texte.
- À mettre : **KOFFR-IX** partout (titre, en-tête de page, §2, §5, §8, §9, §10, §14).
- Ajouter une ligne sous le titre : « KOFFR-IX (anciennement GEPAT) — plateforme de gestion électronique du patrimoine de l'État ».

## §4.2 Profils utilisateurs 🟢

Ajouter une phrase : « En démonstration, l'écran de connexion propose 3 profils (comptable matières, agent de patrimoine, contrôleur). Les 5 rôles sont tous créés dans la base de démonstration (comptes `admin@`, `comptable@`, `agent@`, `controleur@`, `responsable@koffr.local`). »

## §5 Périmètre fonctionnel : numérotation 🔴

La numérotation se répète (7 et 8 apparaissent deux fois) et le tableau 5.4 renvoie aux mauvais numéros. Numéroter une seule fois de 1 à 13 :

| N° | Fonctionnalité | Priorité |
|---|---|---|
| 1 | Authentification et rôles | 1 |
| 2 | Gestion des biens | 1 |
| 3 | QR codes | 1 |
| 4 | Affectation et transfert | 1 |
| 5 | Inventaire mobile | 1 |
| 6 | Documents | 2 |
| 7 | Tableau de bord | 2 |
| 8 | Rapports et exports | 2 |
| 9 | Journal d'audit | 2 |
| 10 | Vérification publique par QR code | 2 |
| 11 | Procès-verbaux automatiques | 2 |
| 12 | Carte de localisation | 3 |
| 13 | Vue nationale pour les décideurs | 3 |

## §5.4 Périmètre de démonstration 🔴

Le tableau décrit un périmètre plus petit que ce qui est codé. Le remplacer par :

| N° | Fonctionnalité | État | Contenu de la démonstration |
|---|---|---|---|
| 1 | Authentification et rôles | Codé | Connexion JWT, 5 rôles (3 proposés sur l'écran de connexion), session expirée |
| 2 | Gestion des biens | Codé | Création, modification, liste (tableau et galerie), fiche, code patrimoine unique |
| 3 | QR codes | Codé | Génération, étiquette imprimable 80 × 50 mm |
| 4 | Affectation et transfert | Codé | Transfert, maintenance, sortie, validation ou refus par le responsable de service, historique en ajout seul |
| 5 | Inventaire mobile | Codé | Campagnes, scan caméra ou saisie manuelle, état constaté, photo, position GPS, détection d'écart |
| 6 | Documents | Maquette | Écran Figma uniquement |
| 7 | Tableau de bord | Codé | Compteurs, répartitions, mouvements récents |
| 8 | Rapports et exports | Codé en partie | Export CSV (biens, écarts) ; Excel et PDF non codés |
| 9 | Journal d'audit | Codé | Consultation en lecture seule des trois journaux (admin, contrôleur) |
| 10 | Vérification publique | Codé | Page publique minimale, sans donnée sensible |
| 11 | Procès-verbaux | Codé | PV d'inventaire et PV de sortie, mise en page A4 imprimable |
| 12 | Carte | Codé | Leaflet et OpenStreetMap |
| 13 | Vue nationale | Codé | Valeur, nombre de biens et taux d'inventaire par ministère |

Ajouter en dessous : « Traitement d'un écart et création de comptes et de structures sont également codés. Restent en maquette seulement : documents joints, mode hors ligne, planche d'étiquettes. »

## §5.5 Hors périmètre 🟢

Ajouter : « exports Excel et PDF générés côté serveur ; planche d'étiquettes par lot ; page d'accès refusé dédiée. »

## §6 User stories 🟠

Dernière phrase : « Si le temps le permet : validation du transfert par le responsable de service (US5), photo et position GPS jointes au contrôle (US8). »
→ « La validation du transfert par le responsable de service (US5) et la photo et la position GPS jointes au contrôle (US8) sont codées. »

## §7 Besoins non fonctionnels

**Résilience** 🔴
- Actuel : « La panne d'un service n'arrête pas les autres. »
- À mettre : « La panne d'un service n'arrête pas les autres. Sans `patrimoine`, la liste des biens et le contrôle par scan sont indisponibles, mais la connexion et les campagnes d'inventaire continuent de fonctionner. »

**Sécurité** 🟠
- Ajouter : « Le jeton JWT est signé en HS256 avec un secret partagé entre les services. En production, une paire de clés (RS256) remplacerait ce secret. »

**Indépendance des services** 🟠
- Ajouter : « Trois rôles PostgreSQL dédiés : chaque service ne peut accéder qu'à sa propre base. »

## §8 Contraintes du brief 🔴

- Actuel : « services (auth, patrimoine, inventaire, document) ».
- À mettre : « services (auth, patrimoine, inventaire) et application web ; le service document est optionnel et non livré ; les photos de contrôle sont stockées dans un volume du service inventaire. »
- Actuel : « Maquettes avec Figma : 15 écrans, 3 parcours cliquables ».
- À mettre : le nombre réel d'écrans livrés (**32 écrans** dans les maquettes du dépôt) et l'outil réellement utilisé. À aligner avec le §11 et le §12.
- Actuel : « Un conteneur par service et par base ».
- À mettre : « Un conteneur par service ; une instance PostgreSQL commune contenant une base par service. »

## §10 Architecture technique

**Tableau des services** 🔴 (colonne « Dépend de »)

| Service | Base | Dépend de |
|---|---|---|
| auth-service | `auth_db` | — |
| patrimoine-service | `patrimoine_db` | — (partage seulement la clé de vérification du JWT) |
| inventaire-service | `inventaire_db` | patrimoine-service (appel REST interne, délai 3 s) |
| document-service | volume Docker | — (optionnel, non livré) |

Remplacer aussi les noms de bases `db-auth`, `db-patrimoine`, `db-inventaire` par `auth_db`, `patrimoine_db`, `inventaire_db`.

**Règles de communication** 🔴
- Actuel : « Une panne de service n'arrête pas les autres : sans document-service, on consulte et met à jour les fiches, seules les pièces jointes sont indisponibles. »
- À mettre : « Une panne de service n'arrête pas les autres. Sans `patrimoine`, le scan et la liste des biens sont indisponibles (réponse 503), mais la connexion et les campagnes d'inventaire fonctionnent. »
- Ajouter : « Le JWT est signé par auth-service et vérifié **localement** par chaque service ; aucun service n'appelle auth-service pour traiter une requête. L'application web n'appelle que la passerelle. Le web résout les noms affichés via `GET /api/auth/users?ids=` et `GET /api/auth/structures`. »

**§10.1 Stack** 🔴
- Actuel : « Authentification : JWT (tymon/jwt-auth) ». À mettre : **php-open-source-saver/jwt-auth**.
- Actuel : « Exports : Laravel Excel, DomPDF ». À mettre : « Export CSV ; procès-verbaux en HTML imprimable (impression navigateur) ». Excel et PDF ne sont pas dans le code.
- Actuel : « PostgreSQL 16, une base par service ». À mettre : « PostgreSQL 16, une instance, une base et un rôle dédié par service. »

**§10.2 Modèle de données** 🔴
- db-auth : ajouter `code` à `structures` ; ajouter `journal_audit` (ajout seul).
- db-patrimoine : `biens` gagne `localisation`, `latitude`, `longitude` ; ajouter `sequences_code` (génération atomique du code `PAT-{ministère}-{année}-{NNNN}`) et `validations_mouvement` (ajout seul : décision, auteur, motif) ; `journal_audit` (ajout seul) ; `proces_verbaux` → **à retirer** (les PV sont générés à la demande, sans table).
- db-inventaire : `controles` gagne `etat_fiche`, `etat_constate` (bon, usage, hors_service, introuvable), `photo_chemin`, `ecart_detecte`, latitude et longitude ; ajouter `traitements_ecart` (ajout seul) et `journal_audit` (ajout seul).
- document-service : « une table documents si le service est activé » → « non livré ; hors périmètre codé ».

## §11 Maquettes et §12 Livrables 🔴

- Actuel : « Quinze écrans », « palette A (bleu institutionnel) sert de base tant qu'une autre n'est pas retenue ; les valeurs sont provisoires ».
- À mettre : « **32 écrans** », et la palette retenue : charte graphique togolaise (vert `#006A4F`, rouge `#D11135`, jaune `#FFCF11`, police Inter). Supprimer toute mention de la palette A bleue.
- Mettre le même nombre d'écrans dans §8, §11, §12 et §14 (ligne « Front web : 15 écrans »).

## §13 Planning 🟢

Facultatif : le planning est celui prévu. Ajouter une colonne « Réalisé » ou une note : « Périmètre étendu en cours de semaine (carte, journal, PV, vue nationale, validation, traitement d'écart, création de comptes). »

## §14 Budget 🟠

- « Back-end (auth 5, patrimoine 12, inventaire 8, documents 4) » : préciser « documents 4 (optionnel, non livré dans la démonstration) ».
- « Front web : 15 écrans, scan, carte, exports » → nombre d'écrans réel et « export CSV ».
- Le total (83 jours-homme, 2 075 000 FCFA) reste valable si on garde les 4 jours documents comme option.

## §15 Risques 🟢

Ajouter une ligne : « Secret JWT partagé compromis — Faible en démo — Élevé en production — Passer à RS256 (clé privée dans auth seul) ». Ajouter : « Instance PostgreSQL unique — Faible — Élevé — Une instance par service en production, sauvegardes. »

## §17 Limites assumées 🔴

Ajouter à la liste :
- « Le jeton JWT utilise un secret partagé (HS256) ; en production, une paire de clés (RS256). »
- « Les trois bases partagent une seule instance PostgreSQL ; en production, une instance par service. »
- « Les mots de passe de démonstration sont écrits dans la configuration Docker ; en production, des secrets gérés hors du dépôt. »
- « Le scan par caméra exige HTTPS ou localhost ; la saisie manuelle du code est toujours disponible. »
- « Non codés : documents joints, mode hors ligne, planche d'étiquettes, exports Excel et PDF, page d'accès refusé dédiée, mot de passe oublié. »

## Résumé de ce qu'il faut modifier en priorité

1. Renommer GEPAT en KOFFR-IX.
2. Remplacer le tableau §5.4 et corriger la numérotation §5.
3. Corriger le tableau §10 (« Dépend de ») et la phrase de résilience.
4. Aligner le nombre d'écrans et la palette (§8, §11, §12, §14).
5. Retirer `proces_verbaux` et ajouter les nouvelles tables au §10.2.
6. Compléter le §17.