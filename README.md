# TextileCycle

Plateforme qui met en relation les **particuliers**, les **ateliers** et les **associations** afin de donner une seconde vie aux vêtements grâce au dépôt, à la réparation, à la transformation ou au don.

Laravel 12 · PHP 8.2 · Blade · Eloquent · Tailwind CSS (CDN)

## Installation

Prérequis : PHP 8.2+, Composer, MySQL/MariaDB (XAMPP). Démarrer MySQL dans XAMPP, puis créer la base :

```sql
CREATE DATABASE textilecycle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
composer install
cp .env.example .env               # DB_CONNECTION=mysql, base « textilecycle », utilisateur root sans mot de passe
php artisan key:generate
php artisan migrate:fresh --seed   # tables + données de démonstration (factories & seeders)
php artisan storage:link           # photos et logos téléversés
php artisan serve
```

Application : http://127.0.0.1:8000 — les e-mails (mot de passe oublié) sont écrits dans `storage/logs/laravel.log` (`MAIL_MAILER=log`).

## Comptes de démonstration

Mot de passe de tous les comptes : `password`

| Rôle | E-mail | Espace |
|---|---|---|
| Admin | `admin@textilecycle.fr` | `/admin` |
| Particulier | `lina.martin@example.com`, `noah.bernard@example.com`, `emma.petit@example.com` | `/vetements` |
| Atelier | `contact@filrouge.fr` (réparation), `maya@studiorefil.fr` (upcycling) | `/atelier` |
| Atelier en attente de validation | `marc@coutureetco.fr` | `/atelier` |
| Association | `contact@vestiaire-solidaire.org`, `hugo@enfance-partage.org` | `/association` |
| Association en attente de validation | `ines@cravate-solidaire.org` | `/association` |

## Répartition des modules

La gestion des utilisateurs, l'authentification et l'intégration des templates sont des **tâches communes**.
Chaque module individuel porte sur **deux entités liées** avec un CRUD complet.

| Module | Étudiant | Entités (relation) | Contrôleurs | Factories / Seeder |
|---|---|---|---|---|
| 1 — Vêtements | _à compléter_ | `Vetement` → `CategorieVetement` (belongsTo) | `VetementController`, `Admin\CategorieVetementController` | `VetementFactory`, `CategorieVetementFactory` / `VetementSeeder`, `CategorieVetementSeeder` |
| 2 — Réparations | _à compléter_ | `DemandeReparation` → `Reparation` (hasOne), → `Vetement`, → `Atelier` | `DemandeReparationController`, `Atelier\ReparationController` | `DemandeReparationFactory`, `ReparationFactory` / `ReparationSeeder` |
| 3 — Transformations & avis | _à compléter_ | `DemandeTransformation` → `Review` (morphOne), → `Atelier` | `DemandeTransformationController`, `Atelier\TransformationController`, `ReviewController` | `DemandeTransformationFactory`, `ReviewFactory` / `TransformationSeeder` |
| 4 — Dons | _à compléter_ | `DemandeDon` → `BesoinAssociation` (belongsTo), → `Association` | `DemandeDonController`, `Association\DonController`, `Association\BesoinController` | `DemandeDonFactory`, `BesoinAssociationFactory`, `AssociationFactory` / `DonSeeder`, `AssociationSeeder` |
| 5 — Ateliers & services | _à compléter_ | `ServiceAtelier` → `Atelier` (belongsTo) | `Atelier\ServiceController`, `Atelier\ProfilController` | `ServiceAtelierFactory`, `AtelierFactory` / `AtelierSeeder` |

## Organisation du code

```
app/
├── Enums/                      Statuts et types (StatutDemande, StatutDon, TypeService…)
├── Models/                     Modèles Eloquent et leurs relations
│   └── Concerns/               AUnWorkflow (étapes communes des demandes), EstNotable (avis, logo)
├── Http/Controllers/           Front office (particulier) et pages publiques
│   ├── Admin/                  Back office administrateur
│   ├── Atelier/                Espace atelier
│   ├── Association/            Espace association
│   └── Auth/                   Connexion, inscription, mot de passe oublié
└── Notifications/              Notification de changement d'étape d'une demande
database/
├── factories/                  Une factory par entité (+ états : acceptee(), recuperee(), enAttente()…)
└── seeders/                    Un seeder par module, appelés par DatabaseSeeder
resources/views/
├── layouts/                    frontoffice.blade.php et backoffice.blade.php (héritage Blade)
├── partials/                   Navbar, sidebar, alertes, blocs partagés
├── components/                 badge, button, card, champ (formulaire), etoiles
├── frontoffice/  auth/  compte/        Pages du front office
└── admin/  atelier/  association/  backoffice/   Pages du back office
routes/
├── web.php                     Routes communes + macros partagées par les demandes
└── modules/                    Un fichier de routes par module (ajouté par sa branche)
lang/fr/                        Messages de validation et de réinitialisation en français
```

## Workflow Git

- `main` contient la **partie commune** (utilisateurs, authentification, administration, templates front office et back office, code partagé des demandes). Elle démarre seule : connexion, inscription, gestion des comptes.
- Chaque module est sur sa branche : `feature/vetements`, `feature/reparations`, `feature/transformations`, `feature/dons`, `feature/ateliers`. Une branche ne fait qu'**ajouter** les fichiers de son module (modèles, contrôleurs, migrations, factories, seeders, vues et `routes/modules/{module}.php`) : les fusions ne créent pas de conflit.
- Le code commun vérifie qu'un module est présent (`App\Support\Modules::actif('vetements')`, `Route::has(...)`) avant de l'utiliser : menus, tableaux de bord et seeders s'adaptent aux modules déjà fusionnés.
- Les modules dépendent les uns des autres (une réparation a besoin d'un vêtement et d'un atelier) : une branche seule n'est pas forcément utilisable. Ordre de fusion conseillé : `vetements` → `ateliers` → `reparations` → `transformations` → `dons`.
- `main` reste stable : on n'y pousse pas directement. Intégration par **pull request**, relue par un autre membre de l'équipe.
- Après un `git pull`, relancer `php artisan migrate:fresh --seed` si des migrations ou seeders ont changé.
