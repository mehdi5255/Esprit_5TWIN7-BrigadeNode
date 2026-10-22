<?php

namespace Database\Seeders;

use App\Models\Vetements\Matiere;
use Illuminate\Database\Seeder;

/**
 * Module 1 — Catalogue des matières textiles.
 * Facteurs = ordres de grandeur d'impact de fabrication par kg (CO₂ et eau), inspirés de données publiques :
 * l'impact affiché sur le site est présenté comme une estimation.
 */
class MatiereSeeder extends Seeder
{
    public const CATALOGUE = [
        ['Coton', 'naturelle', ['CO', 'cotton', 'algodon', 'baumwolle', 'cotone'], 16, 10000, 'Lavage 30 à 40 °C, séchage à l’air libre pour éviter qu’il rétrécisse.'],
        ['Laine', 'naturelle', ['WO', 'WV', 'wool', 'lana', 'wolle', 'laine vierge', 'merinos'], 22, 1500, 'Lavage à la main ou programme laine à 30 °C, séchage à plat, jamais de sèche-linge.'],
        ['Lin', 'naturelle', ['LI', 'linen', 'lino', 'leinen'], 8, 2500, 'Lavage 40 °C, repassage sur tissu encore humide.'],
        ['Soie', 'naturelle', ['SE', 'SK', 'silk', 'seda', 'seide'], 30, 6000, 'Lavage à la main à l’eau froide, sans essorer, à l’abri du soleil.'],
        ['Cuir', 'naturelle', ['leather', 'cuero', 'leder'], 30, 17000, 'Nettoyer avec un chiffon doux, nourrir avec un baume, éviter la pluie.'],
        ['Polyester', 'synthetique', ['PES', 'PL', 'PE', 'polyester', 'poliester'], 14, 70, 'Lavage 30 °C dans un sac de lavage pour limiter les microplastiques.'],
        ['Polyamide', 'synthetique', ['PA', 'nylon', 'poliamida'], 18, 150, 'Lavage 30 °C, ne pas repasser à forte température.'],
        ['Élasthanne', 'synthetique', ['EL', 'EA', 'elastane', 'spandex', 'lycra', 'elastan'], 20, 100, 'Jamais de sèche-linge ni d’eau trop chaude : la fibre perd son élasticité.'],
        ['Acrylique', 'synthetique', ['PC', 'PAN', 'acrylic', 'acrilico'], 16, 100, 'Lavage 30 °C, séchage à plat pour garder la forme.'],
        ['Viscose', 'artificielle', ['VI', 'CV', 'viscosa', 'rayonne', 'rayon'], 12, 3000, 'Lavage délicat à 30 °C, la fibre est fragile une fois mouillée.'],
        ['Autre matière', 'autre', ['autre', 'other'], 15, 3000, null],
    ];

    /** Noms chinois des matières, pour lire les étiquettes « Made in China » (棉 70%…). */
    public const NOMS_CHINOIS = [
        'Coton' => ['棉', '纯棉', '全棉'],
        'Laine' => ['羊毛', '绵羊毛', '羊绒'],
        'Lin' => ['亚麻', '麻'],
        'Soie' => ['真丝', '桑蚕丝', '蚕丝'],
        'Cuir' => ['皮革', '真皮', '牛皮'],
        'Polyester' => ['聚酯纤维', '聚酯', '涤纶'],
        'Polyamide' => ['锦纶', '尼龙', '聚酰胺'],
        'Élasthanne' => ['氨纶', '弹性纤维'],
        'Acrylique' => ['腈纶', '聚丙烯腈'],
        'Viscose' => ['粘纤', '粘胶纤维', '粘胶', '黏胶纤维', '人造棉'],
        'Autre matière' => ['其他', '其它'],
    ];

    public function run(): void
    {
        foreach (self::CATALOGUE as [$nom, $type, $abreviations, $co2, $eau, $entretien]) {
            Matiere::updateOrCreate(['nom' => $nom], [
                'type' => $type, 'abreviations' => [...$abreviations, ...self::NOMS_CHINOIS[$nom] ?? []], 'co2_kg_par_kg' => $co2, 'eau_litres_par_kg' => $eau, 'entretien' => $entretien,
            ]);
        }
    }
}
