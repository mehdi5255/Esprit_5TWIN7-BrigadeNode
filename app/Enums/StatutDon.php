<?php

namespace App\Enums;

enum StatutDon: string
{
    case EN_ATTENTE = 'en_attente';
    case ACCEPTEE = 'acceptee';
    case REFUSEE = 'refusee';
    case DEPOT_REMIS = 'depot_remis';
    case EN_TRAITEMENT = 'en_traitement';
    case REMIS_BENEFICIAIRE = 'remis_beneficiaire';
    case ANNULEE = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::ACCEPTEE => 'Acceptée',
            self::REFUSEE => 'Refusée',
            self::DEPOT_REMIS => 'Dépôt remis',
            self::EN_TRAITEMENT => 'En préparation',
            self::REMIS_BENEFICIAIRE => 'Remis aux bénéficiaires',
            self::ANNULEE => 'Annulée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'blue',
            self::ACCEPTEE, self::DEPOT_REMIS, self::EN_TRAITEMENT => 'amber',
            self::REMIS_BENEFICIAIRE => 'green',
            self::REFUSEE => 'red',
            self::ANNULEE => 'gray',
        };
    }

    /** Steps shown in the tracking timeline, in order. */
    public static function etapes(): array
    {
        return [self::EN_ATTENTE, self::ACCEPTEE, self::DEPOT_REMIS, self::EN_TRAITEMENT, self::REMIS_BENEFICIAIRE];
    }

    public function estTerminal(): bool
    {
        return in_array($this, [self::REFUSEE, self::REMIS_BENEFICIAIRE, self::ANNULEE], true);
    }
}
