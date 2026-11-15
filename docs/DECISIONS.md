# Journal des décisions techniques

Ce fichier consigne les points non tranchés par `AGENTS.md`, tranchés selon la règle
« la solution la plus simple », conformément à la section 1.

## 1. Emplacement des documents de référence

Les schémas UML sont dans `docs/Diagrammes/` (dépôt existant) et non à la racine de `docs/`
comme l'indique la section 1 de `AGENTS.md`. Les documents n'étant jamais modifiés, ils n'ont
pas été déplacés. Les maquettes restent dans `assets/Maquette/`, le blason dans `docs/assets/`.

## 2. Identité visuelle

Le kit logo KOFFR-IX ne contenait que le blason officiel. Les fichiers
`docs/assets/koffr-ix_logo.svg`, `koffr-ix_symbole.svg` et `favicon.svg` ont donc été créés
(symbole K vert, pastille jaune, mot KOFFR-IX) et sont repris à l'identique dans
`services/web/public/`. Le blason `docs/assets/blason-republique-togolaise.svg` est une copie
non modifiée du fichier fourni ; il n'est utilisé que sur les pages sans en-tête (connexion,
vérification publique) à 22 mm de haut sur fond blanc, jamais dans le logo KOFFR-IX.

## 3. Un seul composant d'en-tête

L'en-tête KOFFR-IX est le partiel Blade `resources/views/partials/logo.blade.php`, inclus dans
les deux layouts (`layouts/app.blade.php`, `layouts/auth.blade.php`). Aucune autre variante.

## 4. Tailwind sans étape de build

Tailwind est chargé par le CDN de développement (`cdn.tailwindcss.com`) avec la palette de la
charte (`vert #006A4F`, `rouge #D11135`, `jaune #FFCF11`, police Inter). Cela évite Node.js
dans les conteneurs pour une démonstration d'une semaine. En production : compilation Tailwind
via Vite et fichier CSS statique.

## 5. Vérification du JWT dans chaque service

Le middleware `App\Http\Middleware\VerifierJwt` est copié à l'identité dans `auth`,
`patrimoine` et `inventaire`. Il décode le jeton avec `PHPOpenSourceSaver/JWTAuth` et place
`sub`, `name`, `role`, `structure_id`, `ministere_code` dans les attributs de la requête.
Aucun appel à `auth`. Le guard `api` (driver `jwt`) n'est utilisé que dans `auth`, pour la
connexion et le rafraîchissement.

## 6. Journal d'audit dupliqué par service

`journal_audit` existe dans `auth`, `patrimoine` et `inventaire`. Chaque service écrit les
événements qui le concernent (connexion et accès refusé dans `auth`, création et mouvements
dans `patrimoine`, contrôles dans `inventaire`). Les journaux ne sont jamais fusionnés :
l'interface de consultation reste une maquette.

## 7. Identifiants fixes dans les seeders

Les structures et les comptes de démonstration ont des UUID fixes
(`00000000-0000-4000-8000-0000000000xx`). C'est ce qui permet aux seeders de `patrimoine`
et `inventaire` de référencer une structure sans clé étrangère entre bases. Les identifiants
sont documentés dans les seeders.

## 8. Codes ministère sur la page publique

`patrimoine` ne stocke que des UUID : la page publique de vérification ne peut pas résoudre le
nom du ministère (elle n'appelle pas `auth`). Le service renvoie donc le code présent dans le
code patrimoine (`SAN`), et le web l'affiche via la table de correspondance
`config/koffr.php` (`SAN` → « Ministère de la Santé »).

## 9. Filtre `ids` sur la liste des biens

Ajout d'un filtre `ids` à `GET /api/patrimoine/biens` pour que le web affiche les désignations
des biens d'un contrôle en **un seul appel groupé**, conformément à la règle « jamais un appel
par ligne ».

## 10. Mot de passe et sessions du service web

Le web ne stocke aucune donnée métier. Sa session et son cache utilisent le pilote `file`
(répertoire `storage/` monté) : pas de base supplémentaire à installer.

## 11. Variables d'environnement et `php artisan serve`

`php artisan serve` transmet à son processus fils les valeurs du `.env` du service, qui
prennent le dessus sur les variables Docker Compose. Un `entrypoint.sh` recopie donc au
démarrage les variables d'infrastructure (`DB_*`, `JWT_SECRET`, `GATEWAY_URL`,
`PATRIMOINE_URL`…) dans le `.env`, et génère `APP_KEY` si elle manque. Compose reste ainsi la
source de vérité.

## 12. Passerelle : résolution DNS et en-tête Host

Deux corrections nécessaires en démonstration :

- Nginx résout les noms d'`upstream` au démarrage : après un redémarrage de conteneur, il
  pouvait viser une ancienne adresse IP. Le resolver Docker (`127.0.0.11`) est maintenant
  interrogé et `proxy_pass` utilise une variable ; le `set` précède le `rewrite` car
  `rewrite … break` arrête la phase de réécriture.
- `proxy_set_header Host $http_host` conserve le port : sinon Laravel générait des
  redirections vers `http://localhost/...` (port 80) au lieu de `http://localhost:8080/...`.

## 13. Format des étiquettes

Étiquette 80 × 50 mm, mention « République Togolaise » en texte, code patrimoine, désignation,
catégorie et QR code. Le bloc blason n'y figure pas (choix du développeur autorisé par la
section 10 de `AGENTS.md`) pour rester lisible à cette taille.

## 14. Écarts : traitement hors périmètre

Les écarts sont en lecture seule : aucune route d'écriture, aucun formulaire de traitement.
La page rappelle que le traitement d'un écart est une maquette.

## 15. Annuaire administrateur

`GET /api/auth/annuaire` est réservé au rôle `admin`. Il sert la page Administration
(comptes et structures, lecture seule). Il ne renvoie ni e-mail ni mot de passe.
`GET /users?ids=` reste limité à `id` et `name`. La création de comptes n'est pas
codée : elle reste une maquette, comme indiqué à la section 2 d'`AGENTS.md`.

## 16. Photos de contrôle

Les photos sont stockées dans le volume Docker `inventaire_photos` monté sur
`storage/app/controles` (disque `controles`), sans `document-service`. La photo est facultative
et n'est pas affichée dans l'interface.

## 17. Rôles PostgreSQL dédiés

`docker/postgres/init.sql` crée trois rôles de connexion (`koffr_auth`, `koffr_patrimoine`,
`koffr_inventaire`), chacun ayant des droits uniquement sur sa base (et sa base de test).
Le compose injecte ces identifiants par service : même si un service était compromis,
il ne peut lire ni écrire la base d'un autre. C'est l'amélioration facultative de la
section 5 d'`AGENTS.md`, retenue pour renforcer l'étanchéité.

## 18. Interface alignée sur les maquettes

La couche web reprend la structure des maquettes plutôt que des écrans génériques :
barre latérale blanche (logo, menu, bloc utilisateur, déconnexion) et contenu sur fond gris ;
connexion et vérification publique en écran scindé (blason + accroche à gauche, carte à droite) ;
tableau de bord à cartes et barres ; liste des biens avec colonne valeur et pagination
« Affichage de X à Y sur N biens » ; fiche avec bandeau d'informations, étiquette QR et frise
d'historique ; formulaire avec bandeau d'erreurs et aperçu d'étiquette ; page unique
« Affectations et transferts » ; inventaire en cartes de progression avec formulaire intégré ;
page globale « Écarts et rapports » (export CSV) ; écrans mobiles avec en-tête vert et filet jaune.

Éléments de maquette volontairement non codés, faute de règle métier ou de route d'écriture :
panneau « À valider » du transfert (le mouvement est immédiat, l'API ne gère pas de validation
différée), colonne « Traiter » des écarts, exports Excel/PDF, taux d'inventaire par structure
(l'API ne fournit pas ce croisement) et « Mot de passe oublié ? » (aucun envoi d'e-mail).
Les entrées de menu Carte, Procès-verbaux, Vue nationale et Journal d'audit figurent dans la barre
latérale comme libellés désactivés marqués « maquette » (pas de lien, pas d'action) : la navigation
reste fidèle aux maquettes sans laisser croire que ces écrans existent.

## 19. Administration : trois onglets

La page `/administration` reprend la maquette « Utilisateurs, rôles et structures » et ses trois
onglets (`?onglet=utilisateurs|roles|structures`, onglet inconnu ramené à « Utilisateurs ») :
comptes (nom, rôle, structure, actif), matrice des droits et structures. La matrice applique la
section 6 d'`AGENTS.md` ; les lignes Validation d'un transfert, Traitement d'un écart, Génération de
procès-verbaux et Journal d'audit sont marquées † car leurs écrans ne sont pas codés.
« Biens rattachés » vient de `par_structure` ajouté à `GET /api/patrimoine/stats` (un seul appel,
jamais un comptage par ligne). « Responsable » affiche le compte `responsable_service` rattaché à la
structure : le modèle `structures` n'a pas de champ responsable. Les structures n'ayant pas de champ
d'activation, leur statut affiché est « Active » (elles existent dans l'annuaire). Le bouton
« Créer un compte » est présent mais désactivé : la création de comptes reste une maquette.

## 20. Extension de périmètre : écrans de consultation

Suite à la mise à jour d'`AGENTS.md` (13 nov. 2026), le journal d'audit, la carte, la vue nationale
et les procès-verbaux ne sont plus des maquettes.

- **Journal d'audit** : le web interroge les trois journaux (`/api/auth/journal`,
  `/api/patrimoine/journal`, `/api/inventaire/journal`) et les affiche séparément, sans fusion ni
  agrégation en base. Réservé à l'administrateur et au contrôleur (lecture).
- **Carte** : Leaflet et fond OpenStreetMap chargés par CDN côté navigateur ; seuls les biens portant
  `latitude`/`longitude` sont affichés. Les cinq biens de démonstration reçoivent des coordonnées de
  Lomé dans le seeder, et la base de démonstration a été mise à jour sans réinitialisation.
- **Vue nationale** : croisement réalisé par le web (auth fournit la hiérarchie des structures via
  `GET /api/auth/hierarchie` ; patrimoine fournit `par_structure` et `valeur_par_structure` ;
  inventaire fournit les campagnes avec leurs compteurs). Aucun service ne lit la base d'un autre.
  Le taux compare les constats au nombre de biens du ministère, sous-structures comprises.
- **Procès-verbaux** : générés à la demande depuis les données existantes (aucune table, donc rien
  à modifier ni à supprimer) ; PV d'inventaire par campagne et PV de sortie par bien sorti, sur une
  mise en page A4 imprimable avec blason, référence et blocs de signature. Mention explicite de
  document de démonstration.
- La matrice des rôles de la page Administration garde ses lignes † (validation d'un transfert,
  traitement d'un écart, PV, journal) ; journal et PV étant désormais codés, leurs lignes seront
  mises à jour lors de l'étape suivante (validation, traitement d'écart, création de comptes).

## 21. Validation, traitement d'écart et création de comptes

Trois écrans d'écriture issus de l'extension de périmètre (AGENTS.md, 13 nov. 2026).

- **Validation d'un transfert** : `mouvements` et `controles` restant en ajout seul, la décision
  n'écrit jamais dans le mouvement. Une table `validations_mouvement` (ajout seul, une ligne par
  mouvement) enregistre `validee` ou `refusee` avec son auteur et son motif. Une affectation ou un
  transfert est créé `en_attente` ; la structure du bien n'est mise à jour qu'à la validation, dans
  une transaction avec la décision. Le statut affiché (`statut_effectif`) est calculé à la lecture :
  décision si elle existe, statut initial sinon. Une sortie et une maintenance restent immédiates.
  Seul le rôle `responsable_service` peut décider ; un second appel renvoie 409.
- **Traitement d'un écart** : table `traitements_ecart` (ajout seul, un traitement par contrôle)
  avec l'action (régularisé, réformé, main levée, autre), le commentaire et l'auteur. Le constat
  d'origine n'est jamais modifié ; la liste affiche la décision, son auteur et sa date, et l'export
  CSV ajoute une colonne « Traitement ». Réservé au comptable ; le contrôleur reste en lecture.
- **Création de comptes et de structures** : deux routes d'auth réservées à l'administrateur.
  Le mot de passe est haché par le cast du modèle et n'est jamais renvoyé ni journalisé ; l'action
  `creation_compte` / `creation_structure` est tracée dans le journal d'auth. La matrice des rôles
  de la page Administration ne porte plus de lignes « maquette » : les droits affichés
  correspondent aux contrôles réellement appliqués par les services.
## 22. Gabarit commun des procès-verbaux : étape 1

Les aperçus imprimables et les variantes PDF réutilisent `layouts.impression`. Le gabarit
prévoit le format A4 avec marges de 18 mm, un en-tête républicain centré, le blason source non
modifié à 22 mm minimum, puis le titre et la référence centrés et un pied de page daté. Le corps
des PV reprend la typographie à empattements de la maquette, contrairement à Inter dans
l'interface : cet écart est volontaire et limité aux documents officiels imprimables.
`DateEnLettres::dateEnLettres()` et ses tests sont ajoutés. Les routes, références et données
métier des procès-verbaux restent inchangées à cette étape.
