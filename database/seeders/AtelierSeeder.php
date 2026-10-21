<?php

namespace Database\Seeders;

use App\Models\Ateliers\Atelier;
use App\Models\Ateliers\ServiceAtelier;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Module 5 — Ateliers et leurs services. */
class AtelierSeeder extends Seeder
{
    public function run(): void
    {
        // ── Ateliers de démonstration ──────────────────────────
        $filRouge = Atelier::factory()->for(User::firstWhere('email', 'contact@filrouge.fr'), 'responsable')->create([
            'nom' => 'Atelier Fil Rouge', 'specialite' => 'Réparations et retouches couture',
            'description' => 'Atelier de quartier spécialisé en réparation textile et retouches depuis 2012. Coutures, ourlets, fermetures éclair : on redonne vie à vos pièces préférées.',
            'adresse' => '12 rue des Artisans', 'code_postal' => '75011', 'ville' => 'Paris', 'telephone' => '01 23 45 67 89', 'email' => 'contact@filrouge.fr',
            'horaires' => "Mar–Ven : 10h–19h\nSam : 10h–17h\nFermé dim. et lun.",
        ]);
        $this->services($filRouge, [
            ['reparation', 'Reprise de couture', 'Coutures décousues, entrejambe, doublure.', 12, 30, 3],
            ['reparation', 'Remplacement de fermeture éclair', 'Pantalon, jupe ou blouson.', 25, 45, 5],
            ['reparation', 'Reprise de trou / accroc', 'Stoppage invisible ou pièce décorative.', 15, 40, 4],
            ['reparation', 'Ourlet', 'Pantalon, jean ou jupe.', 10, 20, 2],
            ['reparation', 'Boutons', 'Remplacement ou consolidation.', 5, 10, 1],
        ]);

        $refil = Atelier::factory()->for(User::firstWhere('email', 'maya@studiorefil.fr'), 'responsable')->create([
            'nom' => 'Studio Refil', 'specialite' => 'Upcycling et créations sur mesure',
            'description' => 'Studio de création qui transforme vos vêtements oubliés en pièces uniques : robes devenues jupes, chemises devenues tops, jeans devenus sacs.',
            'adresse' => '4 place de la Mairie', 'code_postal' => '93100', 'ville' => 'Montreuil', 'telephone' => null, 'email' => 'maya@studiorefil.fr',
            'horaires' => 'Mer–Sam : 11h–19h',
        ]);
        $this->services($refil, [
            ['transformation', 'Robe en jupe', 'Raccourcissement et reprise de la taille.', 45, 180, 10],
            ['transformation', 'Chemise en top', 'Coupe courte, finitions et boutonnage.', 35, 120, 7],
            ['transformation', 'Jean en sac cabas', 'Sac doublé avec anses renforcées.', 50, 240, 14],
            ['reparation', 'Retouche simple', 'Ajustement d’une pièce avant ou après transformation.', 15, 30, 3],
        ]);

        // Compte en attente : l'atelier n'est pas encore visible.
        $coutureEtCo = Atelier::factory()->enAttente()->for(User::firstWhere('email', 'marc@coutureetco.fr'), 'responsable')->create([
            'nom' => 'Couture & Co', 'code_postal' => '75020', 'ville' => 'Paris', 'email' => 'marc@coutureetco.fr',
        ]);
        ServiceAtelier::factory()->reparation()->for($coutureEtCo)->create(['nom' => 'Retouche', 'prix' => 12]);

        // ── Ateliers générés ──────────────────────────────────
        Atelier::factory(4)->create()->each(function (Atelier $atelier) {
            ServiceAtelier::factory(fake()->numberBetween(2, 4))->reparation()->for($atelier)->create();
            if (fake()->boolean()) {
                ServiceAtelier::factory(2)->transformation()->for($atelier)->create();
            }
            ServiceAtelier::factory()->inactif()->for($atelier)->create();
        });
        Atelier::factory()->enAttente()->has(ServiceAtelier::factory(2)->reparation(), 'services')
            ->for(User::factory()->atelier()->enAttente(), 'responsable')->create();
    }

    /** @param  list<array{0: string, 1: string, 2: string, 3: int, 4: int, 5: int}>  $services */
    private function services(Atelier $atelier, array $services): void
    {
        foreach ($services as [$type, $nom, $description, $prix, $duree, $delai]) {
            ServiceAtelier::factory()->for($atelier)->create(['type' => $type, 'nom' => $nom, 'description' => $description, 'prix' => $prix, 'duree' => $duree, 'delai_jours' => $delai]);
        }
    }
}
