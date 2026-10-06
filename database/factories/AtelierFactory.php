<?php

namespace Database\Factories;

use App\Enums\StatutStructure;
use App\Models\Ateliers\Atelier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Atelier>
 */
class AtelierFactory extends Factory
{
    protected $model = Atelier::class;

    public function definition(): array
    {
        return [
            'responsable_id' => User::factory()->atelier(),
            'nom' => 'Atelier '.fake()->unique()->lastName(),
            'specialite' => fake()->randomElement(['Retouches et réparations', 'Upcycling créatif', 'Couture sur mesure', 'Réparation de cuir', 'Broderie et personnalisation']),
            'description' => fake()->paragraph(),
            'adresse' => fake()->streetAddress(),
            'code_postal' => fake()->postcode(),
            'ville' => fake()->city(),
            'telephone' => fake()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'horaires' => "Mar–Ven : 10h–19h\nSam : 10h–17h",
            'statut' => StatutStructure::ACTIF,
        ];
    }

    public function enAttente(): static
    {
        return $this->state(['statut' => StatutStructure::EN_ATTENTE]);
    }

    public function desactive(): static
    {
        return $this->state(['statut' => StatutStructure::DESACTIVE]);
    }
}
