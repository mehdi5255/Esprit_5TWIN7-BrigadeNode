# Guide Git de l'équipe — intégration des modules TextileCycle

Dépôt : https://github.com/mehdi5255/Esprit_5TWIN7-BrigadeNode.git

Chaque membre travaille **sur sa machine, avec son compte GitHub**, à partir de sa copie du dossier `textilecycle_complet`.
Personne ne pousse le module d'un autre : chacun ne copie et ne commite que **ses** fichiers.

## Vue d'ensemble

| Étape | Branche | Qui | Contenu | Fichiers |
|---|---|---|---|---|
| 0 | `main` | Mehdi (propriétaire du dépôt) | Partie commune | ~120 (dont 82 dans app/database/routes/vues) |
| 1 | `feature/vetements` | membre du module 1 | Vêtements & catégories | 21 |
| 2 | `feature/ateliers` | membre du module 5 | Ateliers & services | 22 |
| 3 | `feature/reparations` | membre du module 2 | Réparations | 17 |
| 4 | `feature/transformations` | membre du module 3 | Transformations & avis | 23 |
| 5 | `feature/dons` | membre du module 4 | Dons, besoins & associations | 33 |

- L'étape 0 doit être poussée **en premier** (le dépôt est vide).
- Ensuite, les 5 modules peuvent être faits **en même temps** : ils ne touchent pas les mêmes fichiers, donc aucun conflit.
- Les Pull Requests sont **fusionnées dans l'ordre 1 → 5** (clés étrangères : `vetements` → `categorie_vetements`, `demande_reparations` → `ateliers`…).
- Tant que les 5 modules ne sont pas fusionnés, `main` ne démarre pas complètement (la partie commune fait référence aux modules). C'est normal : seul l'état final compte pour la démo.

> Toutes les commandes sont à taper dans **Git Bash** (installé avec Git pour Windows).

---

## Préparation (chaque membre, une seule fois)

1. Accepter l'invitation au dépôt (e-mail GitHub ou https://github.com/mehdi5255/Esprit_5TWIN7-BrigadeNode/invitations).
2. Configurer son identité (le même e-mail que le compte GitHub) :

```bash
git config --global user.name "Prénom Nom"
git config --global user.email "email-du-compte-github@exemple.com"
```

3. Vérifier que sa copie du projet est **la dernière version** : le fichier `resources/views/back/ateliers/services/show.blade.php` doit exister.

Dans les commandes ci-dessous, `P` est le chemin de **votre copie** du projet. Adaptez-le (Git Bash écrit `C:\` sous la forme `/c/`) :

```bash
P="/c/Users/VOTRE_NOM/Downloads/textilecycle_complet"
```

---

## Étape 0 — Partie commune sur `main` (Mehdi)

```bash
cd ~/Desktop
git clone https://github.com/mehdi5255/Esprit_5TWIN7-BrigadeNode.git
cd Esprit_5TWIN7-BrigadeNode
C=$(pwd)
P="/c/Users/VOTRE_NOM/Downloads/textilecycle_complet"
```

Copier les fichiers communs (et seulement eux) :

```bash
cd "$P"
cp --parents .editorconfig .env.example .gitattributes .gitignore README.md GUIDE-GIT-EQUIPE.md artisan composer.json composer.lock package.json package-lock.json phpunit.xml vite.config.js "$C"/
cp -r --parents bootstrap/app.php bootstrap/providers.php config lang tests resources/css resources/js "$C"/
cp --parents $(find storage bootstrap/cache -name .gitignore) "$C"/
cp -r --parents public/.htaccess public/favicon.ico public/index.php public/robots.txt public/js public/front public/back "$C"/
cp -r --parents resources/views/layouts resources/views/partials resources/views/components resources/views/front/commun resources/views/back/commun "$C"/
cp --parents app/Enums/StatutCompte.php app/Enums/StatutDemande.php app/Enums/StatutStructure.php "$C"/
cp -r --parents app/Http/Middleware app/Http/Controllers/Auth app/Http/Controllers/Admin app/Http/Controllers/Concerns "$C"/
cp --parents app/Http/Controllers/Controller.php app/Http/Controllers/CompteController.php app/Http/Controllers/NotificationController.php app/Http/Controllers/DepotController.php "$C"/
cp -r --parents app/Models/User.php app/Models/Concerns app/Notifications app/Providers app/Support "$C"/
cp --parents database/.gitignore database/factories/UserFactory.php database/factories/Concerns/EtatsDeDemande.php database/seeders/DatabaseSeeder.php database/seeders/UtilisateurSeeder.php "$C"/
cp --parents database/migrations/0001_01_01_000000_create_users_table.php database/migrations/0001_01_01_000001_create_cache_table.php database/migrations/0001_01_01_000002_create_jobs_table.php database/migrations/2026_09_26_171800_add_role_and_statut_to_users_table.php database/migrations/2026_09_26_172234_create_notifications_table.php "$C"/
cp --parents routes/web.php routes/console.php "$C"/
cd "$C"
echo ".claude/" >> .gitignore
```

Commits (plusieurs commits avec des messages clairs) :

```bash
git add .editorconfig .env.example .gitattributes .gitignore artisan composer.json composer.lock package.json package-lock.json phpunit.xml vite.config.js bootstrap config lang tests storage public/.htaccess public/favicon.ico public/index.php public/robots.txt resources/css resources/js
git commit -m "Initialisation du projet Laravel 12 (configuration, dépendances, traduction fr)"

git add app/Models/User.php app/Enums/StatutCompte.php app/Http/Middleware app/Http/Controllers/Controller.php app/Http/Controllers/Auth app/Http/Controllers/CompteController.php database/migrations/0001_01_01_000000_create_users_table.php database/migrations/2026_09_26_171800_add_role_and_statut_to_users_table.php database/factories/UserFactory.php database/seeders/UtilisateurSeeder.php resources/views/front/commun/auth resources/views/front/commun/compte
git commit -m "Partie commune : utilisateurs, rôles, authentification et mon compte"

git add resources/views/layouts resources/views/partials resources/views/components resources/views/front/commun/home.blade.php public/js public/front public/back
git commit -m "Intégration des templates : layouts front/back, partials, composants Blade et assets"

git add app/Models/Concerns app/Enums/StatutDemande.php app/Enums/StatutStructure.php app/Notifications app/Http/Controllers/Concerns app/Http/Controllers/NotificationController.php app/Http/Controllers/DepotController.php app/Support database/migrations/0001_01_01_000001_create_cache_table.php database/migrations/0001_01_01_000002_create_jobs_table.php database/migrations/2026_09_26_172234_create_notifications_table.php database/factories/Concerns resources/views/front/commun/notifications
git commit -m "Parcours commun des demandes (trait AUnWorkflow), notifications et dépôts"

git add -A
git commit -m "Back-office admin, routes communes et chargement des modules"

git status
```

`git status` doit afficher « nothing to commit, working tree clean ». Puis :

```bash
git branch -M main
git push -u origin main
```

Conseil : sur GitHub → **Settings → Branches → Add rule** sur `main`, cocher « Require a pull request before merging ». Ainsi, tous les modules passent par une Pull Request.

---

## Étapes 1 à 5 — Un module par membre

Le principe est le même pour les 5 modules. On commence **après** le push de `main` :

```bash
cd ~/Desktop
git clone https://github.com/mehdi5255/Esprit_5TWIN7-BrigadeNode.git
cd Esprit_5TWIN7-BrigadeNode
git checkout -b feature/NOM_DU_MODULE origin/main
C=$(pwd)
P="/c/Users/VOTRE_NOM/Downloads/textilecycle_complet"
```

(Si vous aviez déjà cloné : `git fetch`, puis `git checkout -b feature/NOM_DU_MODULE origin/main`.)

Ensuite, copier **ses** fichiers, faire les commits indiqués, puis :

```bash
git status
git push -u origin feature/NOM_DU_MODULE
```

`git status` doit afficher « nothing to commit, working tree clean » avant le push.

### Étape 1 — `feature/vetements` (module 1 : Vêtements & catégories)

```bash
cd "$P"
cp -r --parents app/Enums/EtatVetement.php app/Enums/StatutVetement.php app/Models/Vetements app/Http/Controllers/Vetements database/migrations/2026_09_26_172229_create_categorie_vetements_table.php database/migrations/2026_09_26_172231_create_vetements_table.php database/factories/CategorieVetementFactory.php database/factories/VetementFactory.php database/seeders/CategorieVetementSeeder.php database/seeders/VetementSeeder.php resources/views/front/vetements resources/views/back/vetements routes/modules/vetements.php "$C"/
cd "$C"

git add app/Enums app/Models/Vetements database/migrations
git commit -m "Module vêtements : enums EtatVetement/StatutVetement, modèles Vetement et CategorieVetement, migrations"

git add app/Http/Controllers/Vetements routes/modules/vetements.php
git commit -m "Module vêtements : CRUD des vêtements, gestion des catégories (admin), validation et routes"

git add resources/views
git commit -m "Module vêtements : vues front (liste, détail, formulaire) et back (catégories)"

git add -A
git commit -m "Module vêtements : factories et seeders avec relations"
```

### Étape 2 — `feature/ateliers` (module 5 : Ateliers & services)

```bash
cd "$P"
cp -r --parents app/Enums/TypeService.php app/Models/Ateliers app/Http/Controllers/Ateliers database/migrations/2026_09_26_172230_create_ateliers_table.php database/migrations/2026_09_26_172235_create_service_ateliers_table.php database/factories/AtelierFactory.php database/factories/ServiceAtelierFactory.php database/seeders/AtelierSeeder.php resources/views/front/ateliers resources/views/back/ateliers routes/modules/ateliers.php "$C"/
cd "$C"

git add app/Enums app/Models/Ateliers database/migrations
git commit -m "Module ateliers : enum TypeService, modèles Atelier et ServiceAtelier, migrations"

git add app/Http/Controllers/Ateliers routes/modules/ateliers.php
git commit -m "Module ateliers : profil, CRUD des services, tableau de bord, pages publiques et routes"

git add resources/views
git commit -m "Module ateliers : vues espace atelier (services, profil, tableau de bord) et pages publiques"

git add -A
git commit -m "Module ateliers : factories et seeder avec relations"
```

### Étape 3 — `feature/reparations` (module 2 : Réparations)

```bash
cd "$P"
cp -r --parents app/Enums/TypeReparation.php app/Models/Reparations app/Http/Controllers/Reparations database/migrations/2026_09_26_172232_create_demande_reparations_table.php database/migrations/2026_09_26_172233_create_reparations_table.php database/factories/DemandeReparationFactory.php database/factories/ReparationFactory.php database/seeders/ReparationSeeder.php resources/views/front/reparations resources/views/back/reparations routes/modules/reparations.php "$C"/
cd "$C"

git add app/Enums app/Models/Reparations database/migrations
git commit -m "Module réparations : enum TypeReparation, modèles DemandeReparation et Reparation (hasOne), migrations"

git add app/Http/Controllers/Reparations routes/modules/reparations.php
git commit -m "Module réparations : demandes côté particulier et traitement côté atelier, validation et routes"

git add resources/views
git commit -m "Module réparations : vues front (liste, création, modification, suivi) et back (traitement)"

git add -A
git commit -m "Module réparations : factories et seeder avec relations"
```

### Étape 4 — `feature/transformations` (module 3 : Transformations & avis)

```bash
cd "$P"
cp -r --parents app/Enums/StatutReview.php app/Models/Transformations app/Http/Controllers/Transformations database/migrations/2026_09_26_172236_create_demande_transformations_table.php database/migrations/2026_09_26_172238_create_reviews_table.php database/factories/DemandeTransformationFactory.php database/factories/ReviewFactory.php database/seeders/TransformationSeeder.php resources/views/front/transformations resources/views/back/transformations routes/modules/transformations.php "$C"/
cd "$C"

git add app/Enums app/Models/Transformations database/migrations
git commit -m "Module transformations : enum StatutReview, modèles DemandeTransformation et Review (polymorphe), migrations"

git add app/Http/Controllers/Transformations routes/modules/transformations.php
git commit -m "Module transformations : demandes d'upcycling, avis, réponses des structures, modération admin et routes"

git add resources/views
git commit -m "Module transformations : vues front (projets, mes avis) et back (traitement, avis, modération)"

git add -A
git commit -m "Module transformations : factories et seeder avec relations"
```

### Étape 5 — `feature/dons` (module 4 : Dons, besoins & associations)

```bash
cd "$P"
cp -r --parents app/Enums/StatutDon.php app/Enums/PrioriteBesoin.php app/Models/Dons app/Http/Controllers/Dons database/migrations/2026_09_26_172237_create_associations_table.php database/factories/AssociationFactory.php database/factories/BesoinAssociationFactory.php database/factories/DemandeDonFactory.php database/seeders/AssociationSeeder.php database/seeders/DonSeeder.php resources/views/front/dons resources/views/back/dons routes/modules/dons.php "$C"/
cd "$C"

git add app/Enums app/Models/Dons database/migrations
git commit -m "Module dons : enums StatutDon et PrioriteBesoin, modèles DemandeDon, BesoinAssociation et Association, migrations"

git add app/Http/Controllers/Dons routes/modules/dons.php
git commit -m "Module dons : dons côté particulier, espace association (besoins, profil, traitement) et routes"

git add resources/views
git commit -m "Module dons : vues front (dons, associations, besoins) et back (espace association)"

git add -A
git commit -m "Module dons : factories et seeders avec relations"
```

---

## Pull Request et merge (sur GitHub)

Pour chaque branche, après le push :

1. Sur la page du dépôt, cliquer **Compare & pull request** (ou **Pull requests → New pull request**). Base : `main`, compare : `feature/…`.
2. Titre : par exemple « Module 1 — Vêtements et catégories ». Dans la description, indiquer ce que contient le module (entités, CRUD, relations).
3. À droite, dans **Reviewers**, choisir un autre membre.
4. Le relecteur ouvre l'onglet **Files changed**, laisse un commentaire (même court), puis clique **Review changes → Approve**.
5. Fusionner avec **Merge pull request → Create a merge commit**, **dans l'ordre** : vetements → ateliers → reparations → transformations → dons.

---

## Vérification finale (tout le monde)

Une fois les 5 PR fusionnées, chacun récupère `main` :

```bash
git checkout main
git pull
```

Puis faire le test « démo depuis Git » **dans un nouveau dossier**, comme le jour de la soutenance :

```bash
cd ~/Desktop
git clone https://github.com/mehdi5255/Esprit_5TWIN7-BrigadeNode.git demo-textilecycle
cd demo-textilecycle
composer install
cp .env.example .env
php artisan key:generate
```

Créer la base `textilecycle` dans phpMyAdmin (XAMPP, MySQL démarré), puis :

```bash
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

Ouvrir http://127.0.0.1:8000 et se connecter avec `admin@textilecycle.fr` / `password`.

---

## Après l'intégration : commits réguliers

Pour toute correction ou amélioration de votre module :

```bash
git checkout main
git pull
git checkout -b fix/vetements-message-validation
```

Faire la modification, puis :

```bash
git add -A
git commit -m "Vêtements : message d'erreur plus clair sur la taille"
git push -u origin fix/vetements-message-validation
```

Puis ouvrir une Pull Request, la faire relire et la fusionner. Plusieurs petits commits réguliers et réels valent mieux qu'un gros commit.

## En cas de problème

| Message | Solution |
|---|---|
| `cp: cannot stat '…'` | Le chemin `P` est faux, ou votre copie du projet n'est pas la dernière version. |
| `Permission denied` au push | Vous n'avez pas accepté l'invitation au dépôt, ou vous êtes connecté avec un autre compte. |
| `rejected … fetch first` | Faire `git pull --rebase origin NOM_DE_LA_BRANCHE`, puis refaire le push. |
| Les images ne s'affichent pas | `php artisan storage:link` |
| `Class … not found` après un pull | `composer dump-autoload` |
