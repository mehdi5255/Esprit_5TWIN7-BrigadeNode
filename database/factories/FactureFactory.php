<?php

namespace Database\Factories;

use App\Models\Reparations\DemandeReparation;
use App\Models\Reparations\Facture;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Facture d'une réparation terminée. Normalement créée par DemandeReparationFactory
 * (états prete et recuperee), avec le montant du devis et le coût final de la demande.
 *
 * @extends Factory<Facture>
 */
class FactureFactory extends Factory
{
    protected $model = Facture::class;

    public function definition(): array
    {
        $devis = fake()->randomElement([15, 18, 22, 25, 30]);
        $supplement = fake()->boolean(25) ? fake()->randomElement([2.5, 4, 6]) : 0;

        return [
            'demande_reparation_id' => DemandeReparation::factory(),
            'montant_devis' => $devis,
            'supplement' => $supplement,
            'motif_supplement' => $supplement > 0 ? fake()->randomElement(['Pièce supplémentaire nécessaire.', 'Renfort de la couture voisine.', 'Fil spécial assorti à commander.']) : null,
            'montant_total' => $devis + $supplement,
            'statut' => 'emise',
            'date_emission' => fake()->dateTimeBetween('-5 days', '-1 day'),
        ];
    }

    public function payee(): static
    {
        return $this->state(fn () => ['statut' => 'payee', 'date_paiement' => fake()->dateTimeBetween('-1 day', 'now')]);
    }
}
