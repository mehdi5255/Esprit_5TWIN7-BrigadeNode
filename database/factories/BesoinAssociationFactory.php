<?php

namespace Database\Factories;

use App\Enums\PrioriteBesoin;
use App\Models\Dons\Association;
use App\Models\Dons\BesoinAssociation;
use App\Models\Vetements\CategorieVetement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BesoinAssociation>
 */
class BesoinAssociationFactory extends Factory
{
    protected $model = BesoinAssociation::class;

    public function definition(): array
    {
        return [
            'association_id' => Association::factory(),
            'categorie_id' => fn () => CategorieVetement::inRandomOrder()->value('id'),
            'titre' => fake()->randomElement(['Vêtements chauds', 'Tenues professionnelles', 'Vêtements enfants', 'Chaussures', 'Manteaux d’hiver', 'Linge de maison']),
            'description' => fake()->optional()->sentence(),
            'quantite' => fake()->numberBetween(10, 150),
            'priorite' => fake()->randomElement(PrioriteBesoin::cases()),
            'actif' => true,
        ];
    }

    public function urgent(): static
    {
        return $this->state(['priorite' => PrioriteBesoin::URGENTE]);
    }

    public function satisfait(): static
    {
        return $this->state(['actif' => false]);
    }
}
