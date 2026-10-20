<?php

namespace App\Enums;

/** Origine d'une matière textile. */
enum TypeMatiere: string
{
    case NATURELLE = 'naturelle';
    case SYNTHETIQUE = 'synthetique';
    case ARTIFICIELLE = 'artificielle';
    case AUTRE = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::NATURELLE => 'Naturelle',
            self::SYNTHETIQUE => 'Synthétique',
            self::ARTIFICIELLE => 'Artificielle',
            self::AUTRE => 'Autre',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NATURELLE => 'green',
            self::SYNTHETIQUE => 'amber',
            self::ARTIFICIELLE => 'blue',
            self::AUTRE => 'gray',
        };
    }
}
