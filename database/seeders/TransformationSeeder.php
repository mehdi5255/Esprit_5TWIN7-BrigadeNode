<?php

namespace Database\Seeders;

use App\Models\Ateliers\Atelier;
use App\Models\Transformations\DemandeTransformation;
use App\Models\Transformations\Review;
use App\Models\Vetements\Vetement;
use Illuminate\Database\Seeder;

/** Module 3 — Demandes de transformation (upcycling). */
class TransformationSeeder extends Seeder
{
    public function run(): void
    {
        $refil = Atelier::firstWhere('nom', 'Studio Refil');

        // ── Démonstration, via le vrai workflow ──────────────
        $d = DemandeTransformation::creer(Vetement::firstWhere('nom', 'Robe fleurie'), ['atelier_id' => $refil->id, 'idee' => 'Transformer cette robe longue en jupe mi-longue avec une poche plaquée.', 'budget_max' => 60]);
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
