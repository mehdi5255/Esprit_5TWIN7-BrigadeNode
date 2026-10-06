<?php

namespace App\Models\Dons;

use App\Enums\StatutDon;
use App\Enums\StatutStructure;
use App\Models\Concerns\EstNotable;
use App\Models\User;
use Database\Factories\AssociationFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(AssociationFactory::class)]
class Association extends Model
{
    use HasFactory, EstNotable;

    protected $fillable = ['responsable_id', 'nom', 'domaine_action', 'description', 'adresse', 'code_postal', 'ville', 'telephone', 'email', 'logo'];

    protected $attributes = ['statut' => 'en_attente'];

    protected function casts(): array
    {
        return ['statut' => StatutStructure::class];
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function besoins(): HasMany
    {
        return $this->hasMany(BesoinAssociation::class);
    }

    /** Besoins actifs, les plus urgents d'abord. */
    public function besoinsActifs(): HasMany
    {
        return $this->besoins()->where('actif', true)
            ->orderByRaw("case priorite when 'urgente' then 1 when 'haute' then 2 when 'moyenne' then 3 else 4 end");
    }

    public function demandesDon(): HasMany
    {
        return $this->hasMany(DemandeDon::class);
    }

    public function statistiques(): array
    {
        $dons = $this->demandesDon();

        return [
            'en_attente' => (clone $dons)->where('statut', StatutDon::EN_ATTENTE)->count(),
            'en_cours' => (clone $dons)->whereIn('statut', [StatutDon::ACCEPTEE, StatutDon::DEPOT_REMIS, StatutDon::EN_TRAITEMENT])->count(),
            'remis' => (clone $dons)->where('statut', StatutDon::REMIS_BENEFICIAIRE)->count(),
            'beneficiaires' => (int) (clone $dons)->where('statut', StatutDon::REMIS_BENEFICIAIRE)->sum('nombre_beneficiaires'),
            'besoins_actifs' => $this->besoins()->where('actif', true)->count(),
            'note' => $this->noteMoyenne(),
            'avis' => $this->nombreAvis(),
        ];
    }
}
