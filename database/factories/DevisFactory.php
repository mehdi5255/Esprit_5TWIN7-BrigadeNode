<?php

namespace Database\Factories;

use App\Models\Reparations\DemandeReparation;
use App\Models\Reparations\Devis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Devis d'une demande de réparation. Normalement créé par DemandeReparationFactory
 * (états acceptee et suivants) ; utilisé seul, il part d'une nouvelle demande en attente.
 *
 * @extends Factory<Devis>
 */
class DevisFactory extends Factory
{
    protected $model = Devis::class;

    public function definition(): array
    {
        return [
            'demande_reparation_id' => DemandeReparation::factory(),
            'main_oeuvre' => fake()->randomElement([12, 15, 18, 22, 25, 30, 35]),
            'delai_jours' => fake()->randomElement([2, 3, 5, 7]),
            'valide_jusqu_au' => now()->addDays(Devis::VALIDITE_JOURS),
            'statut' => 'propose',
        ];
    }

    public function accepte(): static
    {
        return $this->state(fn () => ['statut' => 'accepte', 'date_reponse' => fake()->dateTimeBetween('-20 days', '-1 day')]);
    }

    public function refuse(): static
    {
        return $this->state(fn () => ['statut' => 'refuse', 'date_reponse' => fake()->dateTimeBetween('-20 days', '-1 day')]);
    }

    /** Devis resté sans réponse après sa date de validité. */
    public function expire(): static
    {
        return $this->state(fn () => ['statut' => 'propose', 'valide_jusqu_au' => now()->subDays(2)]);
    }
}
