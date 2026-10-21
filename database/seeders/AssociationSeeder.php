<?php

namespace Database\Seeders;

use App\Models\Dons\Association;
use App\Models\Dons\BesoinAssociation;
use App\Models\User;
use App\Models\Vetements\CategorieVetement;
use Illuminate\Database\Seeder;

/** Module 4 — Associations et leurs besoins. */
class AssociationSeeder extends Seeder
{
    public function run(): void
    {
        $categorie = fn (string $libelle) => CategorieVetement::firstWhere('libelle', $libelle)->id;

        // ── Associations de démonstration ─────────────────────
        $vestiaire = Association::factory()->for(User::firstWhere('email', 'contact@vestiaire-solidaire.org'), 'responsable')->create([
            'nom' => 'Vestiaire Solidaire', 'domaine_action' => 'Aide aux personnes sans abri',
            'description' => 'Nous équipons chaque semaine plus de 200 personnes en vêtements chauds et chaussures.',
            'adresse' => '8 rue de Charenton', 'code_postal' => '75012', 'ville' => 'Paris', 'telephone' => '01 98 76 54 32', 'email' => 'contact@vestiaire-solidaire.org',
        ]);
        BesoinAssociation::factory()->for($vestiaire)->create(['titre' => 'Vêtements chauds adultes', 'categorie_id' => $categorie('Manteau'), 'description' => 'Manteaux, pulls épais et bonnets, en bon état.', 'quantite' => 100, 'priorite' => 'urgente']);
        BesoinAssociation::factory()->for($vestiaire)->create(['titre' => 'Chaussures de marche', 'categorie_id' => $categorie('Chaussures'), 'description' => 'Pointures 39 à 45.', 'quantite' => 40, 'priorite' => 'haute']);
        BesoinAssociation::factory()->satisfait()->for($vestiaire)->create(['titre' => 'Sacs à dos', 'categorie_id' => $categorie('Accessoire'), 'description' => null, 'quantite' => 30, 'priorite' => 'moyenne']);

        $enfance = Association::factory()->for(User::firstWhere('email', 'hugo@enfance-partage.org'), 'responsable')->create([
            'nom' => 'Enfance & Partage', 'domaine_action' => 'Soutien aux familles',
            'description' => 'Nous accompagnons les familles en difficulté et redistribuons des vêtements pour enfants de 2 à 12 ans.',
            'adresse' => '21 avenue de la République', 'code_postal' => '93100', 'ville' => 'Montreuil', 'telephone' => null, 'email' => 'hugo@enfance-partage.org',
        ]);
        BesoinAssociation::factory()->for($enfance)->create(['titre' => 'Vêtements enfants 2–12 ans', 'categorie_id' => $categorie('Enfant'), 'description' => 'Toutes saisons, propres et fonctionnels.', 'quantite' => 60, 'priorite' => 'haute']);

        // Compte en attente : l'association n'est pas encore visible.
        Association::factory()->enAttente()->for(User::firstWhere('email', 'ines@cravate-solidaire.org'), 'responsable')->create([
            'nom' => 'La Cravate Solidaire', 'domaine_action' => 'Insertion professionnelle', 'code_postal' => '75013', 'ville' => 'Paris',
        ]);

        // ── Associations générées ─────────────────────────────
        Association::factory(3)
            ->has(BesoinAssociation::factory(fake()->numberBetween(1, 3)), 'besoins')
            ->has(BesoinAssociation::factory()->satisfait(), 'besoins')
            ->create();
    }
}
