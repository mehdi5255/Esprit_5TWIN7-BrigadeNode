<?php

use App\Models\Vetements\Matiere;
use Database\Seeders\MatiereSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Ajoute les noms chinois des matières à leurs abréviations, pour scanner les étiquettes en chinois.
 * Les matières déjà en base sont complétées sans rien effacer (abréviations ajoutées par l'admin conservées).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (MatiereSeeder::NOMS_CHINOIS as $nom => $noms) {
            if ($matiere = Matiere::firstWhere('nom', $nom)) {
                $matiere->update(['abreviations' => array_values(array_unique([...$matiere->abreviations ?? [], ...$noms]))]);
            }
        }
    }

    public function down(): void
    {
        foreach (MatiereSeeder::NOMS_CHINOIS as $nom => $noms) {
            if ($matiere = Matiere::firstWhere('nom', $nom)) {
                $matiere->update(['abreviations' => array_values(array_diff($matiere->abreviations ?? [], $noms))]);
            }
        }
    }
};
