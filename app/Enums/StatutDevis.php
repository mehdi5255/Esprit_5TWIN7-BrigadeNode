<?php

namespace App\Enums;

/** Réponse du particulier au devis de l'atelier. « Expiré » est calculé (Devis::estExpire()). */
enum StatutDevis: string
{
    case PROPOSE = 'propose';
    case ACCEPTE = 'accepte';
    case REFUSE = 'refuse';

    public function label(): string
    {
        return match ($this) {
            self::PROPOSE => 'En attente de réponse',
            self::ACCEPTE => 'Accepté',
            self::REFUSE => 'Refusé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PROPOSE => 'amber',
            self::ACCEPTE => 'green',
            self::REFUSE => 'red',
        };
    }
}
