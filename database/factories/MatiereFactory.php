<?php

namespace Database\Factories;

use App\Models\Vetements\Matiere;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Matière du catalogue. Le vrai catalogue est créé par MatiereSeeder ; la factory sert aux tests.
 *
 * @extends Factory<Matiere>
 */
class MatiereFactory extends Factory
{
    protected $model = Matiere::class;

    public function definition(): array
    {
        return [
            'nom' => ucfirst(fake()->unique()->word()).' '.fake()->numberBetween(1, 999),
            'type' => fake()->randomElement(['naturelle', 'synthetique', 'artificielle']),
            'abreviations' => [],
            'co2_kg_par_kg' => fake()->randomFloat(2, 5, 30),
            'eau_litres_par_kg' => fake()->numberBetween(50, 10000),
            'entretien' => null,
            'actif' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['actif' => false]);
    }
}
