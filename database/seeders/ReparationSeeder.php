<?php

namespace Database\Seeders;

use App\Enums\TypeReparation;
use App\Models\Ateliers\Atelier;
use App\Models\Reparations\DemandeReparation;
use App\Models\Transformations\Review;
use App\Models\Vetements\Vetement;
use Illuminate\Database\Seeder;

/** Module 2 — Demandes de réparation. */
class ReparationSeeder extends Seeder
{
    public function run(): void
    {
        $filRouge = Atelier::firstWhere('nom', 'Atelier Fil Rouge');
        $vetement = fn (string $nom) => Vetement::firstWhere('nom', $nom);

        // ── Démonstration : on passe par le vrai workflow (dates, notifications, statut du vêtement) ──
        $creer = fn (string $nom, TypeReparation $type, string $description, ?float $budget = null) => DemandeReparation::creer($vetement($nom), [
            'atelier_id' => $filRouge->id, 'type_reparation' => $type, 'description' => $description, 'budget_max' => $budget,
        ]);

        $d = $creer('Jean droit indigo', TypeReparation::COUTURE, "La couture de l'entrejambe est décousue sur 5 cm.", 25);
        $d->accepter(); $d->confirmerDepot(); $d->demarrerTraitement(['diagnostic' => 'Couture entrejambe lâchée, tissu sain autour.']);

        $d = $creer('Manteau camel', TypeReparation::FERMETURE, 'La fermeture éclair se bloque à mi-hauteur.', 40);
        $d->accepter(); $d->confirmerDepot(); $d->demarrerTraitement(['diagnostic' => 'Curseur de fermeture usé.']);
        $d->terminer(32, ['actions_realisees' => 'Remplacement du curseur et graissage de la fermeture.', 'pieces_utilisees' => '1 curseur métal n°5', 'duree_reelle' => 45]);
        $d->confirmerRecuperation();
        Review::factory()->pourDemande($d)->create(['note' => 5, 'commentaire' => 'Travail très soigné et accueil chaleureux. Mon manteau est comme neuf !', 'reponse' => 'Merci Lina, au plaisir de vous revoir à l’atelier !', 'date_reponse' => now()]);

        $creer('Chemise en lin', TypeReparation::BOUTON, 'Deux boutons manquants au niveau du col.', 10);

        $d = $creer('Pull en laine', TypeReparation::TROU, 'Petit trou au coude gauche.', 20);
        $d->accepter(); $d->confirmerDepot(); $d->demarrerTraitement(['diagnostic' => 'Trou de 1 cm, maille filée autour.']);
        $d->terminer(18, ['actions_realisees' => 'Reprise en maille avec laine assortie.', 'duree_reelle' => 60]);

        $d = $creer('Veste en jean', TypeReparation::RETOUCHE, 'Raccourcir les manches de 3 cm.');
        $d->refuser('Nous ne travaillons pas le denim épais à surpiqûres doubles ; essayez Studio Refil.');

        // ── Générées par factory, une par étape, sur les vêtements des particuliers générés ──
        DemandeReparation::factory()->for(VetementSeeder::vetementGenere())->create();                // en attente
        // Acceptée il y a 10 jours sans dépôt : apparaît « en retard » sur la page Dépôts.
        DemandeReparation::factory()->acceptee()->for(VetementSeeder::vetementGenere())->create(['date_acceptation' => now()->subDays(10)]);
        DemandeReparation::factory()->enTraitement()->for(VetementSeeder::vetementGenere())->create();
        DemandeReparation::factory()->prete()->for(VetementSeeder::vetementGenere())->create();
        DemandeReparation::factory()->refusee()->for(VetementSeeder::vetementGenere())->create();

        // Réparations terminées, chacune notée par son particulier.
        for ($i = 0; $i < 3; $i++) {
            $demande = DemandeReparation::factory()->recuperee()->for(VetementSeeder::vetementGenere())->create();
            Review::factory()->pourDemande($demande)->create();
        }
    }

}
