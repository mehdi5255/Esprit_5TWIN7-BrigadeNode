<?php

namespace App\Enums;

enum StatutVetement: string
{
    case DISPONIBLE = 'disponible';
    case EN_REPARATION = 'en_reparation';
    case EN_TRANSFORMATION = 'en_transformation';
    case EN_DON = 'en_don';
    case TERMINE = 'termine';

    public function label(): string
    {
        return match ($this) {
            self::DISPONIBLE => 'Disponible',
            self::EN_REPARATION => 'En réparation',
            self::EN_TRANSFORMATION => 'En transformation',
            self::EN_DON => 'En don',
            self::TERMINE => 'Terminé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DISPONIBLE => 'green',
            self::EN_REPARATION, self::EN_TRANSFORMATION => 'amber',
            self::EN_DON => 'blue',
            self::TERMINE => 'gray',
        };
    }
}
