<?php

namespace Database\Seeders;

use App\Models\Vetements\CategorieVetement;
use Illuminate\Database\Seeder;

/** Module 1 — Catégories de vêtements. */
class CategorieVetementSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Haut', 'T-shirts, chemises, pulls', 'shirt'],
            ['Bas', 'Pantalons, jeans, jupes, shorts', 'ruler'],
            ['Robe', 'Robes et combinaisons', 'sparkles'],
            ['Manteau', 'Vestes, manteaux, doudounes', 'cloud-snow'],
            ['Chaussures', 'Baskets, bottes, sandales', 'footprints'],
            ['Enfant', 'Vêtements 0–14 ans', 'baby'],
            ['Accessoire', 'Sacs, ceintures, écharpes', 'shopping-bag'],
        ] as [$libelle, $description, $icone]) {
            CategorieVetement::factory()->create(compact('libelle', 'description', 'icone'));
        }

        // Une catégorie désactivée : invisible pour les particuliers, gérée par l'admin.
        CategorieVetement::factory()->inactive()->create(['libelle' => 'Déguisement', 'description' => 'Costumes et déguisements', 'icone' => 'sparkles']);
    }
}
