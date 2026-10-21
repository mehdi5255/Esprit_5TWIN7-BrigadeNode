# Pousser son module dans sa branche (cmd Windows)

Dépôt : https://github.com/mehdi5255/Esprit_5TWIN7-BrigadeNode.git

Vous avez déjà cloné/pullé `main`. Chacun crée **sa branche** qui contient **uniquement les fichiers de son module**, puis la pousse avec **son compte GitHub**. Pas de merge.

- Tapez les commandes dans **cmd**, **une ligne à la fois**, dans le dossier du dépôt (`Esprit_5TWIN7-BrigadeNode`).
- Les fichiers du module sont repris directement depuis `main` : rien à copier à la main.
- ⚠️ N'utilisez **jamais** `git add .` ou `git add -A` dans votre branche : cela enverrait `vendor`, `.env`, etc. Copiez les `git add` exactement comme ci-dessous.

| Module | Branche | Fichiers attendus |
|---|---|---|
| 1 — Vêtements & catégories | `feature/vetements` | 21 |
| 2 — Réparations | `feature/reparations` | 17 |
| 3 — Transformations & avis | `feature/transformations` | ✅ fait par Mehdi |
| 4 — Dons, besoins & associations | `feature/dons` | 33 |
| 5 — Ateliers & services | `feature/ateliers` | 22 |

---

## Étape 0 — Une seule fois : votre identité Git

Avec l'e-mail **de votre compte GitHub** (sinon les commits ne vous sont pas attribués) :

```
git config --global user.name "Prénom Nom"
git config --global user.email "email-du-compte-github@exemple.com"
```

## Étape 1 — Se placer sur `main` à jour

```
cd %USERPROFILE%\Desktop\Esprit_5TWIN7-BrigadeNode
git checkout main
git pull
```

(Si votre dépôt est ailleurs que sur le Bureau, adaptez le `cd`.)

## Étape 2 — Le bloc de VOTRE module

Copiez **uniquement le bloc de votre module**, ligne par ligne.

### Module 1 — Vêtements (`feature/vetements`)

```
git checkout --orphan feature/vetements
git rm -rf . -q
git checkout main -- app/Enums/EtatVetement.php app/Enums/StatutVetement.php app/Models/Vetements app/Http/Controllers/Vetements database/migrations/2026_09_26_172229_create_categorie_vetements_table.php database/migrations/2026_09_26_172231_create_vetements_table.php database/factories/CategorieVetementFactory.php database/factories/VetementFactory.php database/seeders/CategorieVetementSeeder.php database/seeders/VetementSeeder.php resources/views/front/vetements resources/views/back/vetements routes/modules/vetements.php
git rm -r --cached . -q
git add app/Enums app/Models database/migrations
git commit -m "Module vetements : enums EtatVetement/StatutVetement, modèles Vetement et CategorieVetement, migrations"
git add app/Http/Controllers routes
git commit -m "Module vetements : CRUD des vêtements, catégories (admin), validation et routes"
git add resources/views
git commit -m "Module vetements : vues front et back"
git add database/factories database/seeders
git commit -m "Module vetements : factories et seeders avec relations"
```

### Module 2 — Réparations (`feature/reparations`)

```
git checkout --orphan feature/reparations
git rm -rf . -q
git checkout main -- app/Enums/TypeReparation.php app/Models/Reparations app/Http/Controllers/Reparations database/migrations/2026_09_26_172232_create_demande_reparations_table.php database/migrations/2026_09_26_172233_create_reparations_table.php database/factories/DemandeReparationFactory.php database/factories/ReparationFactory.php database/seeders/ReparationSeeder.php resources/views/front/reparations resources/views/back/reparations routes/modules/reparations.php
git rm -r --cached . -q
git add app/Enums app/Models database/migrations
git commit -m "Module reparations : enum TypeReparation, modèles DemandeReparation et Reparation, migrations"
git add app/Http/Controllers routes
git commit -m "Module reparations : demandes particulier, traitement atelier, validation et routes"
git add resources/views
git commit -m "Module reparations : vues front et back"
git add database/factories database/seeders
git commit -m "Module reparations : factories et seeder avec relations"
```

### Module 4 — Dons (`feature/dons`)

```
git checkout --orphan feature/dons
git rm -rf . -q
git checkout main -- app/Enums/StatutDon.php app/Enums/PrioriteBesoin.php app/Models/Dons app/Http/Controllers/Dons database/migrations/2026_09_26_172237_create_associations_table.php database/factories/AssociationFactory.php database/factories/BesoinAssociationFactory.php database/factories/DemandeDonFactory.php database/seeders/AssociationSeeder.php database/seeders/DonSeeder.php resources/views/front/dons resources/views/back/dons routes/modules/dons.php
git rm -r --cached . -q
git add app/Enums app/Models database/migrations
git commit -m "Module dons : enums StatutDon et PrioriteBesoin, modèles DemandeDon, BesoinAssociation et Association, migrations"
git add app/Http/Controllers routes
git commit -m "Module dons : dons particulier, espace association (besoins, profil, traitement), validation et routes"
git add resources/views
git commit -m "Module dons : vues front et back"
git add database/factories database/seeders
git commit -m "Module dons : factories et seeders avec relations"
```

### Module 5 — Ateliers (`feature/ateliers`)

```
git checkout --orphan feature/ateliers
git rm -rf . -q
git checkout main -- app/Enums/TypeService.php app/Models/Ateliers app/Http/Controllers/Ateliers database/migrations/2026_09_26_172230_create_ateliers_table.php database/migrations/2026_09_26_172235_create_service_ateliers_table.php database/factories/AtelierFactory.php database/factories/ServiceAtelierFactory.php database/seeders/AtelierSeeder.php resources/views/front/ateliers resources/views/back/ateliers routes/modules/ateliers.php
git rm -r --cached . -q
git add app/Enums app/Models database/migrations
git commit -m "Module ateliers : enum TypeService, modèles Atelier et ServiceAtelier, migrations"
git add app/Http/Controllers routes
git commit -m "Module ateliers : profil, CRUD des services, tableau de bord, pages publiques et routes"
git add resources/views
git commit -m "Module ateliers : vues front et back"
git add database/factories database/seeders
git commit -m "Module ateliers : factories et seeder avec relations"
```

## Étape 3 — Vérifier avant de pousser

```
git ls-files | find /c /v ""
git log --oneline
```

- Le premier nombre doit être **21** (vetements), **17** (reparations), **33** (dons) ou **22** (ateliers).
- `git log` doit afficher **4 commits**.
- `git status` peut lister `vendor/`, `.env`, `node_modules/`… en « Untracked » : c'est **normal**, ne les ajoutez pas.

## Étape 4 — Pousser (remplacer NOM par votre module)

```
git push -u origin feature/NOM
```

Le push se termine par `* [new branch] feature/NOM -> feature/NOM`. Si GitHub demande de se connecter : **votre** compte.

## Étape 5 — Revenir sur main

```
git checkout main
```

Tout le projet réapparaît : c'est normal, vous êtes de retour sur `main`.

---

## En cas de problème

| Message | Solution |
|---|---|
| `fatal: a branch named 'feature/NOM' already exists` | Vous avez déjà essayé : `git checkout main` puis `git branch -D feature/NOM`, et recommencez l'étape 2. |
| `error: pathspec 'main' did not match` | Faire `git fetch origin` puis remplacer `main` par `origin/main` dans la ligne `git checkout main -- …`. |
| `error: src refspec feature/NOM does not match any` | Aucun commit n'a été fait : vérifier l'étape 3. |
| `Permission denied` / `403` au push | Invitation non acceptée (https://github.com/mehdi5255/Esprit_5TWIN7-BrigadeNode/invitations) ou connecté avec un autre compte. |
| `error: Your local changes … would be overwritten` à l'étape 1 | Vous avez modifié des fichiers sur `main` : `git stash`, puis recommencez l'étape 1. |
