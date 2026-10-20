<?php

namespace Database\Seeders;

use App\Enums\TypeReparation;
use App\Enums\TypeService;
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

        // ── Historique : devis acceptés des mois passés, base de l'aide au chiffrage (estimation par devis comparables) ──
        $autres = Atelier::actifs()->proposant(TypeService::REPARATION)->whereKeyNot($filRouge->id)->get();
        foreach ([
            [TypeReparation::FERMETURE, 'Curseur de fermeture usé, glissière intacte.', 18, true],
            [TypeReparation::FERMETURE, 'Fermeture éclair cassée, dents arrachées sur 5 cm.', 28, true],
            [TypeReparation::FERMETURE, 'Fermeture de blouson à remplacer entièrement.', 35, false],
            [TypeReparation::FERMETURE, 'Fermeture éclair de jean bloquée, curseur cassé.', 22, false],
            [TypeReparation::COUTURE, 'Couture latérale décousue sur 10 cm.', 12, true],
            [TypeReparation::COUTURE, 'Entrejambe déchiré, tissu fragilisé autour.', 20, true],
            [TypeReparation::COUTURE, 'Couture d’épaule lâchée.', 10, false],
            [TypeReparation::TROU, 'Petit trou au coude, maille filée.', 15, true],
            [TypeReparation::TROU, 'Accroc de 3 cm sur le devant, tissu sain autour.', 18, false],
            [TypeReparation::BOUTON, 'Deux boutons manquants.', 6, true],
            [TypeReparation::BOUTON, 'Boutonnière effilochée à reprendre.', 9, false],
            [TypeReparation::RETOUCHE, 'Ourlet défait à reprendre sur tout le tour.', 12, true],
            [TypeReparation::RETOUCHE, 'Taille à reprendre de 2 cm.', 22, false],
        ] as [$type, $diagnostic, $montant, $chezFilRouge]) {
            $ancienne = DemandeReparation::factory()->recuperee()->for(VetementSeeder::vetementGenere())->create([
                'atelier_id' => $chezFilRouge || $autres->isEmpty() ? $filRouge->id : $autres->random()->id,
                'type_reparation' => $type, 'description' => $diagnostic, 'cout_final' => $montant, 'budget_max' => null,
            ]);
            $ancienne->reparation->update(['diagnostic' => $diagnostic]);
        }

        // ── Démonstration : on passe par le vrai workflow (dates, notifications, statut du vêtement) ──
        $creer = fn (string $nom, TypeReparation $type, string $description, ?float $budget = null) => DemandeReparation::creer($vetement($nom), [
            'atelier_id' => $filRouge->id, 'type_reparation' => $type, 'description' => $description, 'budget_max' => $budget,
        ]);

        // Accepté, déposé, diagnostiqué, devis accepté : réparation en cours.
        $d = $creer('Jean droit indigo', TypeReparation::COUTURE, "La couture de l'entrejambe est décousue sur 5 cm.", 25);
        $d->accepter(); $d->confirmerDepot();
        $d->etablirDevis(['diagnostic' => 'Couture entrejambe lâchée, tissu sain autour.'], ['main_oeuvre' => 15, 'delai_jours' => 3]);
        $d->accepterDevis(); $d->demarrerTraitement();

        // Parcours complet : devis → facture avec supplément → paiement → garantie active (Lina peut l'utiliser en démo).
        $d = $creer('Manteau camel', TypeReparation::FERMETURE, 'La fermeture éclair se bloque à mi-hauteur.', 40);
        $d->accepter(); $d->confirmerDepot();
        $d->etablirDevis(['diagnostic' => 'Curseur de fermeture usé, glissière en bon état.'], ['main_oeuvre' => 30, 'delai_jours' => 5]);
        $d->accepterDevis(); $d->demarrerTraitement();
        $d->terminer(
            ['supplement' => 2.5, 'motif_supplement' => 'Curseur renforcé en métal, plus résistant que le modèle prévu.'],
            ['actions_realisees' => 'Remplacement du curseur et graissage de la fermeture.', 'pieces_utilisees' => '1 curseur métal n°5', 'duree_reelle' => 45],
        );
        $d->confirmerRecuperation();
        Review::factory()->pourDemande($d)->create(['note' => 5, 'commentaire' => 'Travail très soigné et accueil chaleureux. Mon manteau est comme neuf !', 'reponse' => 'Merci Lina, au plaisir de vous revoir à l’atelier !', 'date_reponse' => now()]);

        // Déposée et diagnostiquée : Lina doit accepter ou refuser le devis (démo particulier).
        $d = $creer('Chemise en lin', TypeReparation::BOUTON, 'Deux boutons manquants au niveau du col.', 10);
        $d->accepter(); $d->confirmerDepot();
        $d->etablirDevis(['diagnostic' => 'Deux boutons manquants au col, boutonnières en bon état.'], ['main_oeuvre' => 6, 'delai_jours' => 2]);

        // En attente : Fil Rouge doit accepter la demande (démo atelier).
        $creer('Veste en velours', TypeReparation::RETOUCHE, "L'ourlet du bas s'est défait sur le côté droit.", 15);

        // Déposée, pas encore diagnostiquée : Fil Rouge établit le devis avec l'aide au chiffrage (démo atelier).
        DemandeReparation::factory()->depotRemis()->for(VetementSeeder::vetementGenere())->create([
            'atelier_id' => $filRouge->id, 'type_reparation' => TypeReparation::FERMETURE,
            'description' => 'La fermeture éclair de mon blouson ne remonte plus.', 'budget_max' => 30,
        ]);

        // Prête : facture émise, à payer à la récupération.
        $d = $creer('Pull en laine', TypeReparation::TROU, 'Petit trou au coude gauche.', 20);
        $d->accepter(); $d->confirmerDepot();
        $d->etablirDevis(['diagnostic' => 'Trou de 1 cm, maille filée autour.'], ['main_oeuvre' => 15, 'delai_jours' => 4]);
        $d->accepterDevis(); $d->demarrerTraitement();
        $d->terminer([], ['actions_realisees' => 'Reprise en maille avec laine assortie.', 'duree_reelle' => 60]);

        $d = $creer('Veste en jean', TypeReparation::RETOUCHE, 'Raccourcir les manches de 3 cm.');
        $d->refuser('Nous ne travaillons pas le denim épais à surpiqûres doubles ; essayez Studio Refil.');

        // ── Générées par factory, une par étape, sur les vêtements des particuliers générés ──
        DemandeReparation::factory()->for(VetementSeeder::vetementGenere())->create();                // en attente
        // Acceptée il y a 10 jours sans dépôt : apparaît « en retard » sur la page Dépôts.
        DemandeReparation::factory()->acceptee()->for(VetementSeeder::vetementGenere())->create(['date_acceptation' => now()->subDays(10)]);
        DemandeReparation::factory()->enTraitement()->for(VetementSeeder::vetementGenere())->create();
        DemandeReparation::factory()->prete()->for(VetementSeeder::vetementGenere())->create();
        DemandeReparation::factory()->refusee()->for(VetementSeeder::vetementGenere())->create();

        // Réparations terminées (facture payée + garantie), chacune notée par son particulier.
        for ($i = 0; $i < 3; $i++) {
            $demande = DemandeReparation::factory()->recuperee()->for(VetementSeeder::vetementGenere())->create();
            Review::factory()->pourDemande($demande)->create();
        }

        // Retour sous garantie : le dernier vêtement réparé revient chez le même atelier, sans frais.
        DemandeReparation::ouvrirRetour($demande->facture->garantie, 'La couture reprise a de nouveau lâché après quelques lavages.');

        // Devis refusé après diagnostic : la demande est annulée, le particulier récupère son vêtement.
        $d = DemandeReparation::factory()->depotRemis()->for(VetementSeeder::vetementGenere())->create();
        $d->etablirDevis(['diagnostic' => 'Doublure entièrement à remplacer.'], ['main_oeuvre' => 45]);
        $d->refuserDevis();
    }

}
