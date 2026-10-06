<?php

namespace Database\Factories;

use App\Models\Vetements\CategorieVetement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategorieVetement>
 */
class CategorieVetementFactory extends Factory
{
    protected $model = CategorieVetement::class;

    public function definition(): array
    {
        return [
            'libelle' => ucfirst(fake()->unique()->word()),
            'description' => fake()->sentence(),
            'icone' => fake()->randomElement(['shirt', 'ruler', 'sparkles', 'cloud-snow', 'footprints', 'shopping-bag', 'baby']),
            'actif' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['actif' => false]);
    }
}
