<?php

namespace App\Enums;

enum StatutCompte: string
{
    case EN_ATTENTE = 'en_attente';
    case ACTIF = 'actif';
    case BLOQUE = 'bloque';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente de validation',
            self::ACTIF => 'Actif',
            self::BLOQUE => 'Bloqué',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'amber',
            self::ACTIF => 'green',
            self::BLOQUE => 'red',
        };
    }
}
