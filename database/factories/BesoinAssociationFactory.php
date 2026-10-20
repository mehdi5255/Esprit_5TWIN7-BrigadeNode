<?php

namespace Database\Factories;

use App\Enums\PrioriteBesoin;
use App\Models\Dons\Association;
use App\Models\Dons\BesoinAssociation;
use App\Models\Vetements\CategorieVetement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BesoinAssociation>
 */
class BesoinAssociationFactory extends Factory
{
    protected $model = BesoinAssociation::class;

    /** Titre de besoin => catégorie de vêtements attendue (null : toutes catégories). */
    public const BESOINS = [
        'Vêtements chauds' => 'Manteau',
        'Tenues professionnelles' => 'Haut',
        'Vêtements enfants' => 'Enfant',
        'Chaussures' => 'Chaussures',
        'Manteaux d’hiver' => 'Manteau',
        'Linge de maison' => null,
    ];

    public function definition(): array
    {
        $titre = fake()->randomElement(array_keys(self::BESOINS));

        return [
            'association_id' => Association::factory(),
            // Catégorie cohérente avec le titre : un besoin « Chaussures » attend des chaussures.
            'categorie_id' => fn () => self::BESOINS[$titre] ? CategorieVetement::where('libelle', self::BESOINS[$titre])->value('id') : null,
            'titre' => $titre,
            'description' => fake()->optional()->sentence(),
            'quantite' => fake()->numberBetween(10, 150),
            'priorite' => fake()->randomElement(PrioriteBesoin::cases()),
            'actif' => true,
        ];
    }

    public function urgent(): static
    {
        return $this->state(['priorite' => PrioriteBesoin::URGENTE]);
    }

    public function satisfait(): static
    {
        return $this->state(['actif' => false]);
    }
}
