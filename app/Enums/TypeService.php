<?php

namespace App\Enums;

enum TypeService: string
{
    case REPARATION = 'reparation';
    case TRANSFORMATION = 'transformation';

    public function label(): string
    {
        return match ($this) {
            self::REPARATION => 'Réparation',
            self::TRANSFORMATION => 'Transformation / upcycling',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::REPARATION => 'amber',
            self::TRANSFORMATION => 'blue',
        };
    }
}
