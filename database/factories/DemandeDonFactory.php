<?php

namespace Database\Factories;

use App\Enums\StatutVetement;
use App\Models\Dons\Association;
use App\Models\Dons\BesoinAssociation;
use App\Models\Dons\DemandeDon;
use App\Models\Vetements\Vetement;
use Database\Factories\Concerns\EtatsDeDemande;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DemandeDon>
 */
class DemandeDonFactory extends Factory
{
    protected $model = DemandeDon::class;

    use EtatsDeDemande;

    public function definition(): array
    {
        return [
            'vetement_id' => Vetement::factory(),
            'particulier_id' => $this->proprietaireDuVetement(),
            'association_id' => fn () => Association::actifs()->reorder()->inRandomOrder()->value('id') ?? Association::factory(),
            // Un besoin actif de cette association, ou un don libre.
            'besoin_id' => fn (array $attributes) => BesoinAssociation::where('association_id', $attributes['association_id'])->where('actif', true)->inRandomOrder()->value('id'),
            'message' => fake()->optional()->sentence(),
            'statut' => 'en_attente',
        ];
    }

    public function remis(): static
    {
        return $this->enTraitement()->state(fn () => [
            'statut' => 'remis_beneficiaire',
            'date_remise' => fake()->dateTimeBetween('-3 days', 'now'),
            'beneficiaire_info' => fake()->randomElement(['Distribution du samedi matin.', 'Maraude du jeudi soir.', 'Vestiaire solidaire mensuel.']),
            'nombre_beneficiaires' => fake()->numberBetween(1, 4),
        ]);
    }

    public function configure(): static
    {
        return $this->afterCreating(fn (DemandeDon $demande) => $this->synchroniserVetement($demande, ['remis_beneficiaire']));
    }

    protected function statutVetementPendant(): StatutVetement
    {
        return StatutVetement::EN_DON;
    }

    /** Un vêtement donné et remis quitte le dressing : il est TERMINE. */
    protected function statutVetementFinal(): StatutVetement
    {
        return StatutVetement::TERMINE;
    }
}
