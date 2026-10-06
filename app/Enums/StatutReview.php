<?php

namespace App\Enums;

enum StatutReview: string
{
    case PUBLIE = 'publie';
    case MASQUE = 'masque';

    public function label(): string
    {
        return match ($this) {
            self::PUBLIE => 'Publié',
            self::MASQUE => 'Masqué',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PUBLIE => 'green',
            self::MASQUE => 'gray',
        };
    }
}
