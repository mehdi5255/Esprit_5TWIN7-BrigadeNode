<?php

namespace Database\Factories;

use App\Enums\EtatVetement;
use App\Enums\StatutVetement;
use App\Models\User;
use App\Models\Vetements\CategorieVetement;
use App\Models\Vetements\Vetement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vetement>
 */
class VetementFactory extends Factory
{
    protected $model = Vetement::class;

    private const PIECES = ['Jean', 'Pull', 'Chemise', 'Robe', 'Veste', 'Manteau', 'T-shirt', 'Jupe', 'Pantalon', 'Blouson', 'Gilet', 'Short'];

    private const QUALIFICATIFS = ['en coton', 'en laine', 'en lin', 'fleuri', 'rayé', 'vintage', 'oversize', 'ajusté', 'en velours', 'en denim'];

    public function definition(): array
    {
        return [
            // Réutilise une catégorie existante quand il y en a, sinon en crée une.
            'categorie_id' => fn () => CategorieVetement::inRandomOrder()->value('id') ?? CategorieVetement::factory(),
            'user_id' => User::factory()->particulier(),
            'nom' => fake()->randomElement(self::PIECES).' '.fake()->randomElement(self::QUALIFICATIFS),
            'matiere' => fake()->randomElement(['Coton', 'Laine', 'Lin', 'Polyester', 'Denim', 'Viscose', 'Soie']),
            'taille' => fake()->randomElement(['XS', 'S', 'M', 'L', 'XL', '38', '40', '42']),
            'couleur' => fake()->randomElement(['Bleu', 'Noir', 'Blanc', 'Gris', 'Rouge', 'Vert', 'Beige', 'Camel']),
            'etat' => fake()->randomElement(EtatVetement::cases()),
            'description' => fake()->optional(0.6)->sentence(),
            'statut' => StatutVetement::DISPONIBLE,
        ];
    }
}
