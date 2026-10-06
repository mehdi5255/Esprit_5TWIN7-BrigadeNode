<?php

namespace Database\Seeders;

use App\Models\Dons\Association;
use App\Models\Dons\DemandeDon;
use App\Models\Transformations\Review;
use App\Models\Vetements\Vetement;
use Illuminate\Database\Seeder;

/** Module 4 — Demandes de don. */
class DonSeeder extends Seeder
{
    public function run(): void
    {
        $vestiaire = Association::firstWhere('nom', 'Vestiaire Solidaire');
        $enfance = Association::firstWhere('nom', 'Enfance & Partage');
        $besoinChaud = $vestiaire->besoins()->firstWhere('titre', 'Vêtements chauds adultes');

        // ── Démonstration, via le vrai workflow ──────────────
        $d = DemandeDon::creer(Vetement::firstWhere('nom', 'Doudoune noire'), ['association_id' => $vestiaire->id, 'besoin_id' => $besoinChaud->id, 'message' => 'Doudoune portée un hiver, lavée.']);
        $d->accepter(); $d->confirmerDepot(); $d->preparer();
        $d->remettre('Distribution du samedi matin au centre d’accueil de jour.', 1);
        Review::factory()->pourDemande($d)->create(['note' => 5, 'commentaire' => 'Accueil adorable et suivi transparent, merci pour votre travail !']);

        $d = DemandeDon::creer(Vetement::firstWhere('nom', 'Lot de pyjamas enfant'), ['association_id' => $enfance->id, 'besoin_id' => $enfance->besoins()->value('id')]);
        $d->accepter(); $d->confirmerDepot();

        DemandeDon::creer(Vetement::firstWhere('nom', 'Pull irlandais'), ['association_id' => $vestiaire->id, 'besoin_id' => $besoinChaud->id, 'message' => 'Pull chaud en très bon état.']);

        // ── Générées par factory ─────────────────────────────
        DemandeDon::factory()->for(VetementSeeder::vetementGenere())->create();                   // en attente
        DemandeDon::factory()->acceptee()->for(VetementSeeder::vetementGenere())->create();
        DemandeDon::factory()->refusee()->for(VetementSeeder::vetementGenere())->create();

        for ($i = 0; $i < 2; $i++) {
            $demande = DemandeDon::factory()->remis()->for(VetementSeeder::vetementGenere())->create();
            Review::factory()->pourDemande($demande)->create();
        }
    }
}
