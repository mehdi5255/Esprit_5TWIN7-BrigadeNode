<?php

namespace Database\Seeders;

use App\Enums\TypeService;
use App\Models\Ateliers\Atelier;
use App\Models\Transformations\DemandeTransformation;
use App\Models\Transformations\Inspiration;
use App\Models\Transformations\Review;
use App\Models\Vetements\CategorieVetement;
use App\Models\Vetements\Vetement;
use Illuminate\Database\Seeder;

/** Module 3 — Demandes de transformation (upcycling). */
class TransformationSeeder extends Seeder
{
    public function run(): void
    {
        $refil = Atelier::firstWhere('nom', 'Studio Refil');
        $categorie = fn (string $libelle) => CategorieVetement::firstWhere('libelle', $libelle)->id;
        $photo = fn (string $id) => "https://images.unsplash.com/{$id}?fit=crop&w=700&q=80";

        // ── Inspirations publiées par Studio Refil (une par catégorie, une masquée) ──
        $inspirations = [];
        foreach ([
            ['Robe', 'Robe longue → Jupe midi', 'Le haut est retiré, la taille reprise avec une ceinture élastique et une poche plaquée.', 45, 10, null],
            ['Haut', 'Chemise → Top court noué', 'Coupe courte nouée à la taille, manches raccourcies, boutonnage conservé.', 35, 7, 'photo-1603252110481-7ba873bf42ab'],
            ['Bas', 'Jean → Sac cabas', 'Sac doublé avec anses renforcées, les poches du jean deviennent des poches de rangement.', 50, 14, 'photo-1542272604-787c3835535d'],
            ['Manteau', 'Manteau → Gilet sans manches', 'Manches retirées, emmanchures bordées d’un biais assorti, longueur conservée.', 40, 10, 'photo-1544022613-e87ca75a784a'],
            ['Accessoire', 'Foulards → Sac besace', 'Plusieurs foulards assemblés et doublés pour un sac unique.', 30, 7, null],
        ] as [$libelle, $titre, $description, $prix, $delai, $image]) {
            $inspirations[$libelle] = $refil->inspirations()->create([
                'categorie_id' => $categorie($libelle), 'titre' => $titre, 'description' => $description,
                'prix_indicatif' => $prix, 'delai_jours' => $delai, 'photo' => $image ? $photo($image) : null,
            ]);
        }
        $refil->inspirations()->create(['categorie_id' => $categorie('Haut'), 'titre' => 'Pull → Coussin', 'description' => 'Modèle retiré temporairement de la galerie.', 'actif' => false]);
        // Un modèle pour chacun des autres ateliers qui proposent la transformation.
        Atelier::actifs()->proposant(TypeService::TRANSFORMATION)->whereKeyNot($refil->id)->get()
            ->each(fn (Atelier $atelier) => Inspiration::factory()->for($atelier)->create());

        // ── Démonstration, via le vrai workflow ──────────────
        // Demande partie d'une inspiration : l'atelier et l'idée viennent du modèle.
        $d = DemandeTransformation::creer(Vetement::firstWhere('nom', 'Robe fleurie'), ['atelier_id' => $refil->id, 'inspiration_id' => $inspirations['Robe']->id, 'idee' => $inspirations['Robe']->ideePreremplie(), 'budget_max' => 60]);
        $d->accepter();

        $d = DemandeTransformation::creer(Vetement::firstWhere('nom', 'Chemise ample'), ['atelier_id' => $refil->id, 'idee' => 'Faire un top court à partir de cette chemise trop grande.', 'budget_max' => 40]);
        $d->accepter(); $d->confirmerDepot(); $d->demarrerTraitement();
        $d->terminer(35, 'Top court à manches bouffantes, boutonnage conservé, ourlet roulotté.');
        $d->confirmerRecuperation();
        Review::factory()->pourDemande($d)->create(['note' => 4, 'commentaire' => 'Une transformation créative, un peu plus longue que prévu mais le résultat est superbe.']);

        // ── Générées par factory ─────────────────────────────
        DemandeTransformation::factory()->for(VetementSeeder::vetementGenere())->create();        // en attente
        DemandeTransformation::factory()->enTraitement()->for(VetementSeeder::vetementGenere())->create();
        DemandeTransformation::factory()->prete()->for(VetementSeeder::vetementGenere())->create();
        DemandeTransformation::factory()->annulee()->for(VetementSeeder::vetementGenere())->create();

        $demande = DemandeTransformation::factory()->recuperee()->for(VetementSeeder::vetementGenere())->create();
        Review::factory()->pourDemande($demande)->create();
    }
}
