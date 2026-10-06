<?php

namespace Database\Factories;

use App\Enums\StatutVetement;
use App\Enums\TypeReparation;
use App\Enums\TypeService;
use App\Models\Ateliers\Atelier;
use App\Models\Reparations\DemandeReparation;
use App\Models\Reparations\Reparation;
use App\Models\Vetements\Vetement;
use Database\Factories\Concerns\EtatsDeDemande;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DemandeReparation>
 */
class DemandeReparationFactory extends Factory
{
    protected $model = DemandeReparation::class;

    use EtatsDeDemande;

    public function definition(): array
    {
        return [
            'vetement_id' => Vetement::factory(),
            'particulier_id' => $this->proprietaireDuVetement(),
            // Un atelier actif qui propose la réparation, ou un nouvel atelier.
            'atelier_id' => fn () => Atelier::actifs()->proposant(TypeService::REPARATION)->reorder()->inRandomOrder()->value('id') ?? Atelier::factory(),
            'type_reparation' => fake()->randomElement(TypeReparation::cases()),
            'description' => fake()->randomElement(['Couture décousue sur le côté.', 'Fermeture éclair bloquée.', 'Petit trou au coude.', 'Bouton manquant.', 'Ourlet à reprendre.']),
            'budget_max' => fake()->optional(0.7)->randomElement([10, 15, 20, 30, 40]),
            'date_souhaitee' => fake()->optional()->dateTimeBetween('+3 days', '+1 month'),
            'statut' => 'en_attente',
        ];
    }

    public function prete(): static
    {
        return $this->enTraitement()->state(fn (array $attributes) => [
            'statut' => 'pret',
            'date_fin_traitement' => fake()->dateTimeBetween('-4 days', '-2 days'),
            'cout_final' => $attributes['budget_max'] ?? fake()->randomElement([10, 15, 20, 25]),
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
        return $this->afterCreating(function (DemandeReparation $demande) {
            $this->synchroniserVetement($demande, ['recupere']);

            // Une réparation démarrée a sa fiche technique.
            if (in_array($demande->statut->value, ['en_traitement', 'pret', 'recupere'], true)) {
                Reparation::factory()->for($demande, 'demande')->create(
                    $demande->statut->value === 'en_traitement' ? ['actions_realisees' => null, 'duree_reelle' => null] : []
                );
            }
        });
    }

    protected function statutVetementPendant(): StatutVetement
    {
        return StatutVetement::EN_REPARATION;
    }
}
