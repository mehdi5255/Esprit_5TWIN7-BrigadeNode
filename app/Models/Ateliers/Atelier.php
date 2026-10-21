<?php

namespace App\Models\Ateliers;

use App\Enums\StatutDemande;
use App\Enums\StatutStructure;
use App\Enums\TypeService;
use App\Models\Concerns\EstNotable;
use App\Models\Reparations\DemandeReparation;
use App\Models\Transformations\DemandeTransformation;
use App\Models\Transformations\Inspiration;
use App\Models\User;
use Database\Factories\AtelierFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(AtelierFactory::class)]
class Atelier extends Model
{
    use EstNotable, HasFactory;

    protected $fillable = ['responsable_id', 'nom', 'specialite', 'description', 'adresse', 'code_postal', 'ville', 'telephone', 'email', 'horaires', 'logo'];

    protected $attributes = ['statut' => 'en_attente'];

    protected function casts(): array
    {
        return ['statut' => StatutStructure::class];
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(ServiceAtelier::class);
    }

    public function demandesReparation(): HasMany
    {
        return $this->hasMany(DemandeReparation::class);
    }

    /** Modèles d'upcycling publiés par l'atelier (module 3). */
    public function inspirations(): HasMany
    {
        return $this->hasMany(Inspiration::class);
    }

    public function demandesTransformation(): HasMany
    {
        return $this->hasMany(DemandeTransformation::class);
    }

    /** Ateliers ayant au moins un service actif du type donné. */
    public function scopeProposant(Builder $query, TypeService $type): void
    {
        $query->whereHas('services', fn ($q) => $q->where('type', $type)->where('actif', true));
    }

    public function propose(TypeService $type): bool
    {
        return $this->services->where('actif', true)->contains('type', $type);
    }

    /** Types de service actifs, pour affichage. */
    public function typesProposes(): array
    {
        return $this->services->where('actif', true)->pluck('type')->unique()->values()->all();
    }

    /** Statistiques de l'atelier, affichées sur son tableau de bord et côté admin. */
    public function statistiques(): array
    {
        $enCours = [StatutDemande::ACCEPTEE, StatutDemande::DEPOT_REMIS, StatutDemande::EN_TRAITEMENT, StatutDemande::PRET];
        $stats = ['en_attente' => 0, 'en_cours' => 0, 'realisees' => 0, 'refusees' => 0, 'chiffre' => 0.0];

        foreach ([$this->demandesReparation(), $this->demandesTransformation()] as $relation) {
            $stats['en_attente'] += (clone $relation)->where('statut', StatutDemande::EN_ATTENTE)->count();
            $stats['en_cours'] += (clone $relation)->whereIn('statut', $enCours)->count();
            $stats['realisees'] += (clone $relation)->where('statut', StatutDemande::RECUPERE)->count();
            $stats['refusees'] += (clone $relation)->where('statut', StatutDemande::REFUSEE)->count();
            $stats['chiffre'] += (float) (clone $relation)->where('statut', StatutDemande::RECUPERE)->sum('cout_final');
        }

        $stats['note'] = $this->noteMoyenne();
        $stats['avis'] = $this->nombreAvis();

        return $stats;
    }
}
