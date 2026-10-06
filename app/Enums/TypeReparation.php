<?php

namespace App\Enums;

enum TypeReparation: string
{
    case COUTURE = 'couture';
    case RETOUCHE = 'retouche';
    case FERMETURE = 'fermeture';
    case TROU = 'trou';
    case BOUTON = 'bouton';
    case AUTRE = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::COUTURE => 'Couture',
            self::RETOUCHE => 'Retouche',
            self::FERMETURE => 'Fermeture éclair',
            self::TROU => 'Trou / accroc',
            self::BOUTON => 'Bouton',
            self::AUTRE => 'Autre',
        };
    }
}
