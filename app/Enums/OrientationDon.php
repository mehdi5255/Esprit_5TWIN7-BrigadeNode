<?php

namespace App\Enums;

/** Module 4 — Devenir d'un don reçu, décidé par l'association (avec l'aide du contrôle qualité IA). */
enum OrientationDon: string
{
    case REDISTRIBUTION = 'redistribution';
    case BOUTIQUE = 'boutique';
    case RECYCLAGE = 'recyclage';

    public function label(): string
    {
        return match ($this) {
            self::REDISTRIBUTION => 'Redistribution',
            self::BOUTIQUE => 'Boutique solidaire',
            self::RECYCLAGE => 'Recyclage des fibres',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::REDISTRIBUTION => 'Donné directement à un bénéficiaire.',
            self::BOUTIQUE => 'Vendu à petit prix pour financer l’association.',
            self::RECYCLAGE => 'Trop abîmé pour être porté : ses fibres seront recyclées.',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::REDISTRIBUTION => 'green',
            self::BOUTIQUE => 'blue',
            self::RECYCLAGE => 'amber',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::REDISTRIBUTION => 'hand-heart',
            self::BOUTIQUE => 'store',
            self::RECYCLAGE => 'recycle',
        };
    }

    /** Texte proposé pour la remise, selon l'orientation. */
    public function remiseProposee(): ?string
    {
        return match ($this) {
            self::REDISTRIBUTION => null,
            self::BOUTIQUE => 'Mis en vente à la boutique solidaire de l’association.',
            self::RECYCLAGE => 'Confié à la filière de recyclage des fibres textiles.',
        };
    }
}
