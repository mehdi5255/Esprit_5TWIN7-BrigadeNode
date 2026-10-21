<?php

namespace App\Support;

/**
 * Modules de l'application : chacun déclare ses routes dans routes/modules/{module}.php.
 * Un module est actif quand son fichier de routes est présent (branche feature/* fusionnée).
 */
class Modules
{
    /** Ordre de chargement des modules. */
    public const LISTE = ['vetements', 'ateliers', 'reparations', 'transformations', 'dons'];

    public static function fichier(string $module): string
    {
        return base_path("routes/modules/{$module}.php");
    }

    public static function actif(string $module): bool
    {
        return is_file(self::fichier($module));
    }

    /** @return list<string> */
    public static function actifs(): array
    {
        return array_values(array_filter(self::LISTE, fn (string $module) => self::actif($module)));
    }
}
