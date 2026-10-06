<?php

namespace App\Models\Dons;

use App\Enums\PrioriteBesoin;
use App\Enums\StatutDon;
use App\Models\Vetements\CategorieVetement;
use Database\Factories\BesoinAssociationFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(BesoinAssociationFactory::class)]
class BesoinAssociation extends Model
{
    use HasFactory;

    protected $fillable = ['categorie_id', 'titre', 'description', 'quantite', 'priorite', 'actif'];

    protected $attributes = ['actif' => true, 'priorite' => 'moyenne'];

    protected function casts(): array
    {
        return [
            'priorite' => PrioriteBesoin::class,
            'quantite' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieVetement::class, 'categorie_id');
    }

    public function dons(): HasMany
    {
        return $this->hasMany(DemandeDon::class, 'besoin_id');
    }

    /** Nombre de vêtements déjà reçus pour ce besoin. */
    public function collectes(): int
    {
        return $this->dons_recus ?? $this->dons()->whereIn('statut', [StatutDon::DEPOT_REMIS, StatutDon::EN_TRAITEMENT, StatutDon::REMIS_BENEFICIAIRE])->count();
    }

    public function progression(): int
    {
        return $this->quantite ? min(100, (int) round($this->collectes() / $this->quantite * 100)) : 0;
    }
}
