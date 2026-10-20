<?php

namespace App\Models\Reparations;

use Database\Factories\GarantieFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Garantie accordée sur une réparation payée. Tant qu'elle est active, le particulier peut
 * demander un retour gratuit : chaque retour est une nouvelle DemandeReparation (1-N).
 */
#[UseFactory(GarantieFactory::class)]
class Garantie extends Model
{
    use HasFactory;

    /** Durée par défaut d'une garantie. */
    public const DUREE_MOIS = 3;

    protected $fillable = ['duree_mois', 'date_debut', 'date_fin', 'conditions'];

    protected function casts(): array
    {
        return [
            'duree_mois' => 'integer',
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }

    /** Demandes de réparation ouvertes sous cette garantie. */
    public function retours(): HasMany
    {
        return $this->hasMany(DemandeReparation::class);
    }

    /** Réparation d'origine, couverte par la garantie. */
    public function demandeOrigine(): DemandeReparation
    {
        return $this->facture->demande;
    }

    public function estActive(): bool
    {
        return $this->date_fin->endOfDay()->isFuture();
    }

    public function libelleStatut(): string
    {
        return $this->estActive() ? 'Active' : 'Expirée';
    }
}
