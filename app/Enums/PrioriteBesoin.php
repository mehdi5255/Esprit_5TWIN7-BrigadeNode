<?php

namespace App\Enums;

enum PrioriteBesoin: string
{
    case BASSE = 'basse';
    case MOYENNE = 'moyenne';
    case HAUTE = 'haute';
    case URGENTE = 'urgente';

    public function label(): string
    {
        return match ($this) {
            self::BASSE => 'Basse',
            self::MOYENNE => 'Moyenne',
            self::HAUTE => 'Prioritaire',
            self::URGENTE => 'Urgent',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BASSE => 'gray',
            self::MOYENNE => 'blue',
            self::HAUTE => 'amber',
            self::URGENTE => 'red',
        };
    }

    /** Ordre de tri : les plus urgents d'abord. */
    public function poids(): int
    {
        return match ($this) {
            self::URGENTE => 4,
            self::HAUTE => 3,
            self::MOYENNE => 2,
            self::BASSE => 1,
        };
    }
}
