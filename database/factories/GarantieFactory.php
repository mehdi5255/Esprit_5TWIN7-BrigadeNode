<?php

namespace Database\Factories;

use App\Models\Reparations\Facture;
use App\Models\Reparations\Garantie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Garantie accordée au paiement d'une facture. Normalement créée par DemandeReparationFactory
 * (état recuperee), à partir de la date de récupération.
 *
 * @extends Factory<Garantie>
 */
class GarantieFactory extends Factory
{
    protected $model = Garantie::class;

    public function definition(): array
    {
        $debut = now()->subDays(fake()->numberBetween(1, 20));

        return [
            'facture_id' => Facture::factory()->payee(),
            'duree_mois' => Garantie::DUREE_MOIS,
            'date_debut' => $debut,
            'date_fin' => $debut->copy()->addMonths(Garantie::DUREE_MOIS),
            'conditions' => 'Couvre la réparation effectuée pendant '.Garantie::DUREE_MOIS.' mois, hors usure normale, accident ou mauvais entretien.',
        ];
    }

    /** Garantie dont la date de fin est passée. */
    public function expiree(): static
    {
        return $this->state(fn () => ['date_debut' => now()->subMonths(4), 'date_fin' => now()->subMonth()]);
    }
}
