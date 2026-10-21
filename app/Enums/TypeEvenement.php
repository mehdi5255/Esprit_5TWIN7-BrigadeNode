<?php

namespace App\Enums;

/** Rendez-vous d'une association : collecte (dépôt des dons) ou distribution (remise aux bénéficiaires). */
enum TypeEvenement: string
{
    case COLLECTE = 'collecte';
    case DISTRIBUTION = 'distribution';

    public function label(): string
    {
        return match ($this) {
            self::COLLECTE => 'Collecte',
            self::DISTRIBUTION => 'Distribution',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::COLLECTE => 'blue',
            self::DISTRIBUTION => 'green',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::COLLECTE => 'package-plus',
            self::DISTRIBUTION => 'hand-heart',
        };
    }
}
