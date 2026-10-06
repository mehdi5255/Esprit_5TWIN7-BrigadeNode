<?php

namespace App\Enums;

/** Statut d'un Atelier ou d'une Association, piloté par l'admin. */
enum StatutStructure: string
{
    case EN_ATTENTE = 'en_attente';
    case ACTIF = 'actif';
    case DESACTIVE = 'desactive';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente de validation',
            self::ACTIF => 'Actif',
            self::DESACTIVE => 'Désactivé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'amber',
            self::ACTIF => 'green',
            self::DESACTIVE => 'gray',
        };
    }
}
