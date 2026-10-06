<?php

namespace App\Enums;

enum EtatVetement: string
{
    case NEUF = 'neuf';
    case TRES_BON = 'tres_bon';
    case BON = 'bon';
    case MOYEN = 'moyen';
    case MAUVAIS = 'mauvais';

    public function label(): string
    {
        return match ($this) {
            self::NEUF => 'Neuf',
            self::TRES_BON => 'Très bon',
            self::BON => 'Bon',
            self::MOYEN => 'Moyen',
            self::MAUVAIS => 'Mauvais',
        };
    }
}
