<?php

namespace Database\Factories;

use App\Models\Dons\Association;
use App\Models\Dons\Evenement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Collecte (par défaut) ou distribution d'une association, à venir.
 *
 * @extends Factory<Evenement>
 */
class EvenementFactory extends Factory
{
    protected $model = Evenement::class;

    public function definition(): array
    {
        $debut = now()->addDays(fake()->numberBetween(3, 40))->setTime(fake()->randomElement([9, 10, 14]), 0);

        return [
            'association_id' => Association::factory(),
            'type' => 'collecte',
            'titre' => fake()->randomElement(['Collecte de quartier', 'Grande collecte de vêtements', 'Collecte solidaire du samedi']),
            'description' => fake()->optional()->randomElement(['Apportez vos vêtements propres et en bon état.', 'Un café vous attend pendant le dépôt.']),
            'lieu' => fake()->streetAddress().', '.fake()->city(),
            'debut' => $debut,
            'fin' => $debut->copy()->addHours(fake()->numberBetween(2, 7)),
            'places' => fake()->optional(0.7)->randomElement([10, 20, 30, 50]),
            'annule' => false,
        ];
    }

    public function distribution(): static
    {
        return $this->state(fn () => [
            'type' => 'distribution',
            'titre' => fake()->randomElement(['Maraude du jeudi', 'Distribution du samedi matin', 'Vestiaire solidaire']),
            'places' => null,
        ]);
    }

    /** Événement déjà terminé. */
    public function passe(): static
    {
        return $this->state(function () {
            $debut = now()->subDays(fake()->numberBetween(5, 30))->setTime(10, 0);

            return ['debut' => $debut, 'fin' => $debut->copy()->addHours(4)];
        });
    }

    public function annule(): static
    {
        return $this->state(['annule' => true]);
    }
}
