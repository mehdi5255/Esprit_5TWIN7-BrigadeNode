<?php

namespace Database\Factories;

use App\Enums\StatutStructure;
use App\Models\Dons\Association;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Association>
 */
class AssociationFactory extends Factory
{
    protected $model = Association::class;

    public function definition(): array
    {
        return [
            'responsable_id' => User::factory()->association(),
            'nom' => fake()->randomElement(['Solidarité', 'Entraide', 'Partage', 'Le Vestiaire', 'Cœur']).' '.fake()->unique()->city(),
            'domaine_action' => fake()->randomElement(['Aide aux personnes sans abri', 'Soutien aux familles', 'Insertion professionnelle', 'Aide aux réfugiés', 'Protection de l’enfance']),
            'description' => fake()->paragraph(),
            'adresse' => fake()->streetAddress(),
            'code_postal' => fake()->postcode(),
            'ville' => fake()->city(),
            'telephone' => fake()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
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
