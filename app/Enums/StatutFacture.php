<?php

namespace App\Enums;

enum StatutFacture: string
{
    case EMISE = 'emise';
    case PAYEE = 'payee';

    public function label(): string
    {
        return match ($this) {
            self::EMISE => 'À payer',
            self::PAYEE => 'Payée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EMISE => 'amber',
            self::PAYEE => 'green',
        };
    }
}
