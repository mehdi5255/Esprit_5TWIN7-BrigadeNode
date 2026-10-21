<?php

namespace Database\Factories;

use App\Enums\TypeService;
use App\Models\Ateliers\Atelier;
use App\Models\Transformations\Inspiration;
use App\Models\Vetements\CategorieVetement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Modèle d'upcycling d'un atelier qui propose la transformation, pour une catégorie active.
 *
 * @extends Factory<Inspiration>
 */
class InspirationFactory extends Factory
{
    protected $model = Inspiration::class;

    public function definition(): array
    {
        [$titre, $description] = fake()->randomElement([
            ['Jean → Sac cabas', 'Sac doublé avec anses renforcées, poches du jean conservées.'],
            ['Chemise → Top court', 'Coupe courte, manches raccourcies, boutonnage conservé.'],
            ['Robe longue → Jupe midi', 'Le haut est retiré, la taille reprise avec une ceinture élastique.'],
            ['Pull → Bonnet et mitaines', 'Le pull feutré est découpé pour un ensemble chaud et doux.'],
            ['T-shirts → Tote bag', 'Plusieurs t-shirts assemblés en patchwork, sans couture visible.'],
            ['Veste → Gilet sans manches', 'Manches retirées et emmanchures bordées d’un biais.'],
        ]);

        return [
            'atelier_id' => fn () => Atelier::actifs()->proposant(TypeService::TRANSFORMATION)->reorder()->inRandomOrder()->value('id') ?? Atelier::factory(),
            'categorie_id' => fn () => CategorieVetement::actives()->inRandomOrder()->value('id') ?? CategorieVetement::factory(),
            'titre' => $titre,
            'description' => $description,
            'photo' => null,
            'prix_indicatif' => fake()->optional(0.8)->randomElement([20, 25, 35, 45, 60]),
            'delai_jours' => fake()->optional(0.8)->randomElement([3, 5, 7, 10, 14]),
            'actif' => true,
        ];
    }

    /** Inspiration retirée de la galerie par l'atelier. */
    public function masquee(): static
    {
        return $this->state(['actif' => false]);
    }
}
