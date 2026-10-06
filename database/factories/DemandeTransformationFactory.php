<?php

namespace Database\Factories;

use App\Enums\StatutVetement;
use App\Enums\TypeService;
use App\Models\Ateliers\Atelier;
use App\Models\Transformations\DemandeTransformation;
use App\Models\Vetements\Vetement;
use Database\Factories\Concerns\EtatsDeDemande;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DemandeTransformation>
 */
class DemandeTransformationFactory extends Factory
{
    protected $model = DemandeTransformation::class;

    use EtatsDeDemande;

    public function definition(): array
    {
        return [
            'vetement_id' => Vetement::factory(),
            'particulier_id' => $this->proprietaireDuVetement(),
            // Un atelier actif spécialisé en transformation, ou un nouvel atelier.
            'atelier_id' => fn () => Atelier::actifs()->proposant(TypeService::TRANSFORMATION)->reorder()->inRandomOrder()->value('id') ?? Atelier::factory(),
            'idee' => fake()->randomElement(['Transformer en jupe courte.', 'En faire un sac cabas.', 'Raccourcir en top cropped.', 'Ajouter des empiècements en patchwork.', 'Retirer les manches pour un gilet.']),
            'budget_max' => fake()->optional(0.7)->randomElement([30, 40, 50, 60, 80]),
            'date_souhaitee' => fake()->optional()->dateTimeBetween('+1 week', '+2 months'),
            'statut' => 'en_attente',
        ];
    }

    public function prete(): static
    {
        return $this->enTraitement()->state(fn (array $attributes) => [
            'statut' => 'pret',
            'date_fin_traitement' => fake()->dateTimeBetween('-4 days', '-2 days'),
            'produit_final' => fake()->randomElement(['Jupe mi-longue doublée.', 'Sac cabas avec anses renforcées.', 'Top court à manches bouffantes.', 'Gilet sans manches boutonné.']),
            'cout_final' => $attributes['budget_max'] ?? fake()->randomElement([35, 45, 55]),
        ]);
    }

    public function recuperee(): static
    {
        return $this->prete()->state(fn () => [
            'statut' => 'recupere',
            'date_recuperation' => fake()->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    public function configure(): static
    {
        return $this->afterCreating(fn (DemandeTransformation $demande) => $this->synchroniserVetement($demande, ['recupere']));
    }

    protected function statutVetementPendant(): StatutVetement
    {
        return StatutVetement::EN_TRANSFORMATION;
    }
}
