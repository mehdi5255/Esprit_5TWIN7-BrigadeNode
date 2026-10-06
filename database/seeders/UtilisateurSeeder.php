<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** Comptes de démonstration (tâche commune). Mot de passe : « password ». */
class UtilisateurSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create(['name' => 'Admin TextileCycle', 'email' => 'admin@textilecycle.fr']);

        User::factory()->particulier()->create(['name' => 'Lina Martin', 'email' => 'lina.martin@example.com']);
        User::factory()->particulier()->create(['name' => 'Noah Bernard', 'email' => 'noah.bernard@example.com']);
        User::factory()->particulier()->create(['name' => 'Emma Petit', 'email' => 'emma.petit@example.com']);

        User::factory()->atelier()->create(['name' => 'Sophie Durand', 'email' => 'contact@filrouge.fr']);
        User::factory()->atelier()->create(['name' => 'Maya Leroy', 'email' => 'maya@studiorefil.fr']);
        User::factory()->atelier()->enAttente()->create(['name' => 'Marc Simon', 'email' => 'marc@coutureetco.fr']);

        User::factory()->association()->create(['name' => 'Claire Moreau', 'email' => 'contact@vestiaire-solidaire.org']);
        User::factory()->association()->create(['name' => 'Hugo Lambert', 'email' => 'hugo@enfance-partage.org']);
        User::factory()->association()->enAttente()->create(['name' => 'Inès Faure', 'email' => 'ines@cravate-solidaire.org']);
    }
}
