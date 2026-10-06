<?php

namespace App\Enums;

enum StatutDemande: string
{
    case EN_ATTENTE = 'en_attente';
    case ACCEPTEE = 'acceptee';
    case REFUSEE = 'refusee';
    case DEPOT_REMIS = 'depot_remis';
    case EN_TRAITEMENT = 'en_traitement';
    case PRET = 'pret';
    case RECUPERE = 'recupere';
    case ANNULEE = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::ACCEPTEE => 'Acceptée',
            self::REFUSEE => 'Refusée',
            self::DEPOT_REMIS => 'Dépôt remis',
            self::EN_TRAITEMENT => 'En traitement',
            self::PRET => 'Prêt',
            self::RECUPERE => 'Récupéré',
            self::ANNULEE => 'Annulée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'blue',
            self::ACCEPTEE, self::DEPOT_REMIS, self::EN_TRAITEMENT => 'amber',
            self::PRET, self::RECUPERE => 'green',
            self::REFUSEE => 'red',
            self::ANNULEE => 'gray',
        };
    }

    /** The demande is still in progress: its vêtement is locked. */
    public function estActive(): bool
    {
        return ! in_array($this, [self::REFUSEE, self::RECUPERE, self::ANNULEE], true);
    }

    /** Steps shown in the tracking timeline, in order. */
    public static function etapes(): array
    {
        return [self::EN_ATTENTE, self::ACCEPTEE, self::DEPOT_REMIS, self::EN_TRAITEMENT, self::PRET, self::RECUPERE];
    }
}
