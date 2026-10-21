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

        // ── Événements : collectes et distributions (positions GPS relevées sur OpenStreetMap) ──────────
        $le = fn (int $jours, int $heure) => now()->addDays($jours)->setTime($heure, 0);
        $collecteHiver = $vestiaire->evenements()->create(['type' => 'collecte', 'titre' => 'Grande collecte d’hiver', 'description' => 'Manteaux, pulls, écharpes : apportez vos vêtements chauds propres et en bon état.', 'lieu' => 'Place de la Mairie, Montreuil', 'latitude' => 48.862186, 'longitude' => 2.4421691, 'debut' => $le(10, 10), 'fin' => $le(10, 17), 'places' => 20]);
        $vestiaire->evenements()->create(['type' => 'collecte', 'titre' => 'Collecte express du mercredi', 'lieu' => '8 rue de la Solidarité, Paris 20e', 'latitude' => 48.8839492, 'longitude' => 2.3918663, 'debut' => $le(5, 14), 'fin' => $le(5, 18), 'places' => 2]);
        $vestiaire->evenements()->create(['type' => 'distribution', 'titre' => 'Maraude du jeudi soir', 'description' => 'Distribution aux personnes sans abri autour de la gare.', 'lieu' => 'Gare du Nord, Paris 10e', 'latitude' => 48.8795666, 'longitude' => 2.3572124, 'debut' => $le(12, 19), 'fin' => $le(12, 22)]);
        $vestiaire->evenements()->create(['type' => 'collecte', 'titre' => 'Collecte au marché (annulée)', 'lieu' => 'Marché de la Croix-de-Chavaux, Montreuil', 'latitude' => 48.8579778, 'longitude' => 2.43575, 'debut' => $le(8, 9), 'fin' => $le(8, 13), 'annule' => true]);
        $collectePassee = $vestiaire->evenements()->create(['type' => 'collecte', 'titre' => 'Collecte de rentrée', 'lieu' => 'Place de la Mairie, Montreuil', 'latitude' => 48.862186, 'longitude' => 2.4421691, 'debut' => $le(-20, 10), 'fin' => $le(-20, 17), 'places' => 30]);
        $distributionPassee = $vestiaire->evenements()->create(['type' => 'distribution', 'titre' => 'Distribution du samedi matin', 'lieu' => 'Centre d’accueil de jour, Montreuil', 'latitude' => 48.8625988, 'longitude' => 2.441569, 'debut' => $le(-7, 9), 'fin' => $le(-7, 12)]);
        $enfance->evenements()->create(['type' => 'collecte', 'titre' => 'Bourse aux vêtements enfants', 'description' => 'Vêtements 0–14 ans, jouets non acceptés.', 'lieu' => 'École Jules-Ferry, Saint-Denis', 'latitude' => 48.9357141, 'longitude' => 2.3586273, 'debut' => $le(20, 9), 'fin' => $le(20, 16), 'places' => 40]);

        // ── Démonstration, via le vrai workflow ──────────────
        // Déposé à la collecte de rentrée, remis lors de la distribution du samedi : le don passe par deux événements (N-N).
        $d = DemandeDon::creer(Vetement::firstWhere('nom', 'Doudoune noire'), ['association_id' => $vestiaire->id, 'besoin_id' => $besoinChaud->id, 'message' => 'Doudoune portée un hiver, lavée.']);
        $d->evenements()->attach($collectePassee);
        $d->accepter();
        $d->confirmerDepot();
        $d->preparer();
        $d->remettre('Distribution du samedi matin au centre d’accueil de jour.', 1, $distributionPassee);
        Review::factory()->pourDemande($d)->create(['note' => 5, 'commentaire' => 'Accueil adorable et suivi transparent, merci pour votre travail !']);

        $d = DemandeDon::creer(Vetement::firstWhere('nom', 'Lot de pyjamas enfant'), ['association_id' => $enfance->id, 'besoin_id' => $enfance->besoins()->value('id')]);
        $d->accepter();
        $d->confirmerDepot();

        // Lina dépose son pull lors de la grande collecte d'hiver (en attente d'acceptation).
        $d = DemandeDon::creer(Vetement::firstWhere('nom', 'Pull irlandais'), ['association_id' => $vestiaire->id, 'besoin_id' => $besoinChaud->id, 'message' => 'Pull chaud en très bon état.']);
        $d->rattacherCollecte($collecteHiver);

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
