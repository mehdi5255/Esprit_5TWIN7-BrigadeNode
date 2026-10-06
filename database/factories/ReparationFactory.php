<?php

namespace Database\Factories;

use App\Models\Reparations\DemandeReparation;
use App\Models\Reparations\Reparation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Fiche technique d'une réparation. Normalement créée par DemandeReparationFactory
 * (états enTraitement / prete / recuperee) ; utilisée seule, elle part d'une demande
 * « dépôt remis » qu'elle fait passer en traitement.
 *
 * @extends Factory<Reparation>
 */
class ReparationFactory extends Factory
{
    protected $model = Reparation::class;

    public function definition(): array
    {
        return [
            'demande_reparation_id' => DemandeReparation::factory()->depotRemis(),
            'diagnostic' => fake()->randomElement(['Couture lâchée, tissu sain autour.', 'Curseur de fermeture usé.', 'Accroc de 2 cm, maille filée.', 'Fil cassé sur 10 cm.']),
            'actions_realisees' => fake()->randomElement(['Reprise à la machine avec fil assorti.', 'Remplacement du curseur.', 'Stoppage invisible.', 'Pose de deux boutons neufs.']),
            'pieces_utilisees' => fake()->optional()->randomElement(['Fil coton', '1 curseur métal', '2 boutons', 'Pièce thermocollante']),
            'duree_reelle' => fake()->randomElement([15, 30, 45, 60, 90]),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Reparation $reparation) {
            if ($reparation->demande->statut->value === 'depot_remis') {
                $reparation->demande->forceFill(['statut' => 'en_traitement', 'date_debut_traitement' => now()])->save();
            }
        });
    }
}
