<?php

namespace App\Support;

use App\Models\Association;
use App\Models\Atelier;
use App\Models\DemandeDon;
use App\Models\DemandeReparation;
use App\Models\DemandeTransformation;
use App\Models\Review;
use App\Models\Vetement;

/**
 * Modules présents dans le code.
 *
 * La branche main ne contient que la partie commune ; chaque branche feature/* ajoute
 * un module. Le code commun vérifie qu'un module est présent avant d'utiliser ses modèles,
 * pour que l'application démarre quels que soient les modules déjà fusionnés.
 */
class Modules
{
    /** module => modèle principal du module */
    public const MODELES = [
        'vetements' => Vetement::class,
        'reparations' => DemandeReparation::class,
        'transformations' => DemandeTransformation::class,
        'avis' => Review::class,
        'dons' => DemandeDon::class,
        'associations' => Association::class,
        'ateliers' => Atelier::class,
    ];

    public static function actif(string $module): bool
    {
        return class_exists(self::MODELES[$module]);
    }

    /** Ne garde que les entrées dont le module est présent : [module => valeur]. */
    public static function filtrer(array $parModule): array
    {
        return array_filter($parModule, fn ($module) => self::actif($module), ARRAY_FILTER_USE_KEY);
    }
}
