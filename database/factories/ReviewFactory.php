<?php

namespace Database\Factories;

use App\Enums\StatutReview;
use App\Models\Reparations\DemandeReparation;
use App\Models\Transformations\Review;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * Avis sur une demande terminée. Par défaut, crée une réparation récupérée ;
 * utiliser pourDemande($demande) pour noter une demande existante.
 *
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'note' => fake()->randomElement([3, 4, 4, 5, 5, 5]),
            'commentaire' => fake()->optional(0.8)->randomElement([
                'Travail soigné, je recommande.',
                'Très bon accueil et délais respectés.',
                'Résultat au-delà de mes attentes !',
                'Correct, un peu plus long que prévu.',
                'Équipe à l’écoute, merci !',
            ]),
            'statut' => StatutReview::PUBLIE,
        ];
    }

    /** Rattache l'avis à une demande terminée : l'auteur et la structure notée en découlent. */
    public function pourDemande(Model $demande): static
    {
        return $this->state(fn () => [
            'demande_type' => $demande->getMorphClass(),
            'demande_id' => $demande->id,
            'particulier_id' => $demande->particulier_id,
            'reviewable_type' => $demande->structureModel()->getMorphClass(),
            'reviewable_id' => $demande->structureModel()->id,
        ]);
    }

    public function masque(): static
    {
        return $this->state(['statut' => StatutReview::MASQUE]);
    }

    public function configure(): static
    {
        // Sans demande fournie, on en crée une terminée pour que l'avis soit cohérent.
        return $this->afterMaking(function (Review $review) {
            if (! $review->demande_id) {
                $demande = DemandeReparation::factory()->recuperee()->create();
                $review->forceFill([
                    'demande_type' => $demande->getMorphClass(),
                    'demande_id' => $demande->id,
                    'particulier_id' => $demande->particulier_id,
                    'reviewable_type' => $demande->atelier->getMorphClass(),
                    'reviewable_id' => $demande->atelier_id,
                ]);
            }
        });
    }
}
