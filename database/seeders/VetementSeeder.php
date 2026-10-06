<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vetements\CategorieVetement;
use App\Models\Vetements\Vetement;
use Illuminate\Database\Seeder;

/** Module 1 — Vêtements des particuliers. */
class VetementSeeder extends Seeder
{
    public function run(): void
    {
        $photo = fn (string $id) => "https://images.unsplash.com/{$id}?fit=crop&w=700&q=80";
        $categorie = fn (string $libelle) => CategorieVetement::firstWhere('libelle', $libelle)->id;

        // ── Vêtements de démonstration (les demandes des autres modules s'appuient dessus) ──
        foreach ([
            ['lina.martin@example.com', 'Manteau camel', 'Manteau', ['matiere' => 'Laine', 'taille' => 'M', 'couleur' => 'Camel', 'etat' => 'tres_bon', 'photo' => $photo('photo-1544022613-e87ca75a784a'), 'description' => 'Manteau long, doublure intérieure un peu usée.']],
            ['lina.martin@example.com', 'Jean droit indigo', 'Bas', ['matiere' => 'Denim', 'taille' => '38', 'couleur' => 'Indigo', 'etat' => 'bon', 'photo' => $photo('photo-1542272604-787c3835535d')]],
            ['lina.martin@example.com', 'Chemise en lin', 'Haut', ['matiere' => 'Lin', 'taille' => 'S', 'couleur' => 'Blanc', 'etat' => 'tres_bon', 'photo' => $photo('photo-1603252110481-7ba873bf42ab')]],
            ['lina.martin@example.com', 'Robe fleurie', 'Robe', ['matiere' => 'Viscose', 'taille' => 'M', 'couleur' => 'Multicolore', 'etat' => 'bon']],
            ['lina.martin@example.com', 'Pull irlandais', 'Haut', ['matiere' => 'Laine', 'taille' => 'L', 'couleur' => 'Écru', 'etat' => 'bon']],
            ['lina.martin@example.com', 'Veste en velours', 'Manteau', ['matiere' => 'Velours', 'taille' => 'M', 'couleur' => 'Bordeaux', 'etat' => 'bon']],
            ['noah.bernard@example.com', 'Pull en laine', 'Haut', ['matiere' => 'Laine', 'taille' => 'L', 'couleur' => 'Gris', 'etat' => 'moyen']],
            ['noah.bernard@example.com', 'Veste en jean', 'Manteau', ['matiere' => 'Denim', 'taille' => 'L', 'couleur' => 'Bleu', 'etat' => 'bon']],
            ['noah.bernard@example.com', 'Doudoune noire', 'Manteau', ['taille' => 'XL', 'couleur' => 'Noir', 'etat' => 'tres_bon']],
            ['emma.petit@example.com', 'Chemise ample', 'Haut', ['matiere' => 'Coton', 'taille' => 'M', 'couleur' => 'Bleu ciel', 'etat' => 'bon']],
            ['emma.petit@example.com', 'Lot de pyjamas enfant', 'Enfant', ['taille' => '6 ans', 'etat' => 'tres_bon']],
        ] as [$email, $nom, $libelle, $details]) {
            Vetement::factory()->for(User::firstWhere('email', $email))->create(
                ['nom' => $nom, 'categorie_id' => $categorie($libelle), 'description' => null, 'matiere' => null, 'couleur' => null] + $details
            );
        }

        // ── Particuliers générés, chacun avec un dressing de 3 à 5 pièces ──
        User::factory(6)->particulier()->create()
            ->each(fn (User $particulier) => Vetement::factory(fake()->numberBetween(3, 5))->for($particulier)->create());
    }

    /**
     * Un vêtement disponible d'un particulier généré (pas des comptes de démonstration),
     * ou un nouveau vêtement si tous sont déjà engagés dans une demande.
     */
    public static function vetementGenere(): Vetement
    {
        return Vetement::disponibles()
            ->whereHas('user', fn ($q) => $q->where('email', 'not like', '%@example.com'))
            ->inRandomOrder()
            ->first() ?? Vetement::factory()->create();
    }
}
