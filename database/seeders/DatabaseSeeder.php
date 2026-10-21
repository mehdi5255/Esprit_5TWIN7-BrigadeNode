<?php

namespace Database\Seeders;

use App\Support\RolesPermissions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Peuplement complet : php artisan migrate:fresh --seed
 * Mot de passe de tous les comptes : « password ».
 *
 * Chaque module a son seeder : données de démonstration fixes (comptes connus pour la
 * soutenance) puis données générées par les factories pour peupler les listes.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            UtilisateurSeeder::class,        // Tâche commune : comptes de démonstration
            CategorieVetementSeeder::class,  // Module 1 — Vêtements
            MatiereSeeder::class,            // Module 1 — Catalogue des matières
            AtelierSeeder::class,            // Module 5 — Ateliers & services
            AssociationSeeder::class,        // Module 4 — Associations & besoins
            VetementSeeder::class,           // Module 1 — Vêtements
            ReparationSeeder::class,         // Module 2 — Réparations
            TransformationSeeder::class,     // Module 3 — Transformations
            DonSeeder::class,                // Module 4 — Dons
        ]);

        // Rôles et permissions (Spatie) : les événements de modèle étant coupés pendant le seed,
        // chaque compte est rattaché ici au rôle Spatie correspondant à son type de compte.
        RolesPermissions::installer();
    }
}
