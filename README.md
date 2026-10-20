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
| 1 — Vêtements | _à compléter_ | `Vetement` → `CategorieVetement` (belongsTo), `Vetement` ↔ `Matiere` (belongsToMany, pourcentage sur le pivot `matiere_vetement`) | `VetementController`, `Admin\CategorieVetementController`, `Admin\MatiereController` | `VetementFactory`, `CategorieVetementFactory`, `MatiereFactory` / `VetementSeeder`, `CategorieVetementSeeder`, `MatiereSeeder` |
| 2 — Réparations | _à compléter_ | `DemandeReparation` → `Reparation` (hasOne), → `Devis` (hasOne), → `Facture` (hasOne) → `Garantie` (hasOne) → retours `DemandeReparation` (hasMany) | `DemandeReparationController`, `Atelier\ReparationController`, `Atelier\FactureController` | `DemandeReparationFactory`, `ReparationFactory`, `DevisFactory`, `FactureFactory`, `GarantieFactory` / `ReparationSeeder` |
| 3 — Transformations & avis | _à compléter_ | `DemandeTransformation` → `Review` (morphOne), → `Atelier` | `DemandeTransformationController`, `Atelier\TransformationController`, `ReviewController` | `DemandeTransformationFactory`, `ReviewFactory` / `TransformationSeeder` |
| 4 — Dons | _à compléter_ | `DemandeDon` → `BesoinAssociation` (belongsTo), → `Association` | `DemandeDonController`, `Association\DonController`, `Association\BesoinController` | `DemandeDonFactory`, `BesoinAssociationFactory`, `AssociationFactory` / `DonSeeder`, `AssociationSeeder` |
| 5 — Ateliers & services | _à compléter_ | `ServiceAtelier` → `Atelier` (belongsTo) | `Atelier\ServiceController`, `Atelier\ProfilController` | `ServiceAtelierFactory`, `AtelierFactory` / `AtelierSeeder` |

## Organisation du code

```
app/
├── Enums/                      Statuts et types (StatutDemande, StatutDon, TypeService…)
├── Models/                     Partie commune : User
│   ├── Concerns/               AUnWorkflow (étapes communes des demandes), EstNotable (avis, logo)
│   ├── Vetements/              Module 1 : Vetement, CategorieVetement
│   ├── Reparations/            Module 2 : DemandeReparation, Reparation
│   ├── Transformations/        Module 3 : DemandeTransformation, Review
│   ├── Dons/                   Module 4 : DemandeDon, BesoinAssociation, Association
│   └── Ateliers/               Module 5 : Atelier, ServiceAtelier
├── Http/Controllers/           Partie commune : Controller, Compte, Notification, Depot
│   ├── Concerns/               ExecuteDesTransitions, ListeDesDemandes
│   ├── Auth/                   Connexion, inscription, mot de passe oublié
│   ├── Admin/                  Back office admin commun (tableau de bord, utilisateurs, structures, supervision, rapports)
│   ├── Vetements/              Module 1 : VetementController, Admin/CategorieVetementController
│   ├── Reparations/            Module 2 : DemandeReparationController, Atelier/ReparationController
│   ├── Transformations/        Module 3 : DemandeTransformationController, Atelier/TransformationController,
│   │                           ReviewController, AvisStructureController, Admin/ReviewController
│   ├── Dons/                   Module 4 : DemandeDonController, AssociationController,
│   │                           Association/ (Dashboard, Don, Besoin, Profil)
│   └── Ateliers/               Module 5 : AtelierController, Atelier/ (Dashboard, Profil, Service)
├── Notifications/              Notification de changement d'étape d'une demande
└── Support/                    Photo, Depots, Modules (modules actifs = fichiers de routes présents)
routes/
├── web.php                     Partie commune (auth, compte, admin) + macros demandesParticulier(),
│                               demandesStructure(), supervision() + chargement des modules
└── modules/                    Un fichier par module : vetements, ateliers, reparations,
                                transformations, dons
database/
├── factories/                  Une factory par entité (+ états : acceptee(), recuperee(), enAttente()…)
└── seeders/                    Un seeder par module, appelés par DatabaseSeeder
resources/views/
├── layouts/                    front.blade.php et back.blade.php : parents (@yield, @stack('styles'), @stack('scripts'))
├── partials/
│   ├── front/                  navbar, footer, alerts, avis + blocs des formulaires de demande
│   ├── back/                   navbar, sidebar, footer, alerts + demandes/ (en-tête, décision, liste, avis)
│   └── demandes/               timeline, avis, compte rendu… partagés front + back
├── components/                 <x-badge>, <x-button>, <x-card>, <x-champ>, <x-etoiles> (@props)
├── front/                      Pages visiteur / particulier (@extends('layouts.front'))
│   ├── commun/                 accueil, auth, mon compte, notifications
│   └── vetements/  reparations/  transformations/  dons/  ateliers/      un dossier par module
└── back/                       Pages de gestion (@extends('layouts.back'))
    ├── commun/                 tableau de bord admin, utilisateurs, structures, dépôts, rapports
    └── vetements/  reparations/  transformations/  dons/  ateliers/      un dossier par module
public/
├── front/js/                   tailwind.config.js (thème du front), front.js
├── back/js/                    tailwind.config.js (thème du back), back.js (icônes, menu de la sidebar)
└── js/                         validation.js, confirmation.js (partagés)
lang/fr/                        Messages de validation et de réinitialisation en français
```

## Workflow Git

- `main` reste stable : on n'y pousse pas directement.
- Chaque étudiant travaille sur sa branche : `feature/vetements`, `feature/reparations`, `feature/transformations`, `feature/dons`, `feature/ateliers`.
- Intégration par **pull request**, relue par un autre membre de l'équipe.
- Après un `git pull`, relancer `php artisan migrate:fresh --seed` si des migrations ou seeders ont changé.
