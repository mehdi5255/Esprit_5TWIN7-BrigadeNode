<?php

namespace Database\Factories;

use App\Enums\TypeService;
use App\Models\Ateliers\Atelier;
use App\Models\Ateliers\ServiceAtelier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceAtelier>
 */
class ServiceAtelierFactory extends Factory
{
    protected $model = ServiceAtelier::class;

    private const NOMS = [
        'reparation' => ['Ourlet', 'Reprise de couture', 'Remplacement de fermeture', 'Pose de boutons', 'Stoppage', 'Reprise de doublure'],
        'transformation' => ['Robe en jupe', 'Chemise en top', 'Jean en short', 'Jean en sac', 'Veste sans manches', 'Patchwork sur mesure'],
    ];

    public function definition(): array
    {
        $type = fake()->randomElement(TypeService::cases());

        return [
            'atelier_id' => Atelier::factory(),
            'type' => $type,
            'nom' => fake()->randomElement(self::NOMS[$type->value]),
            'description' => fake()->optional()->sentence(),
            'prix' => fake()->randomElement([5, 10, 12, 15, 20, 25, 35, 45, 60]),
            'duree' => fake()->randomElement([15, 30, 45, 60, 120, 180]),
            'delai_jours' => fake()->numberBetween(1, 14),
            'actif' => true,
        ];
    }

    public function reparation(): static
    {
        return $this->state(fn () => ['type' => TypeService::REPARATION, 'nom' => fake()->randomElement(self::NOMS['reparation'])]);
    }

    public function transformation(): static
    {
        return $this->state(fn () => ['type' => TypeService::TRANSFORMATION, 'nom' => fake()->randomElement(self::NOMS['transformation'])]);
    }

    public function inactif(): static
    {
        return $this->state(['actif' => false]);
    }
}
