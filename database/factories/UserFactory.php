<?php

namespace Database\Factories;

use App\Enums\StatutCompte;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state : un particulier actif (mot de passe « password »).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'particulier',
            'statut' => StatutCompte::ACTIF,
        ];
    }

    public function particulier(): static
    {
        return $this->state(['role' => 'particulier']);
    }

    public function atelier(): static
    {
        return $this->state(['role' => 'atelier']);
    }

    public function association(): static
    {
        return $this->state(['role' => 'association']);
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    /** Compte professionnel en attente de validation par l'admin. */
    public function enAttente(): static
    {
        return $this->state(['statut' => StatutCompte::EN_ATTENTE]);
    }

    public function bloque(): static
    {
        return $this->state(['statut' => StatutCompte::BLOQUE]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
