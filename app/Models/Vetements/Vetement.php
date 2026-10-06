<?php

namespace App\Models\Vetements;

use App\Enums\EtatVetement;
use App\Enums\StatutVetement;
use App\Models\Dons\DemandeDon;
use App\Models\Reparations\DemandeReparation;
use App\Models\Transformations\DemandeTransformation;
use App\Models\User;
use App\Support\Photo;
use Database\Factories\VetementFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[UseFactory(VetementFactory::class)]
class Vetement extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'categorie_id', 'nom', 'matiere', 'taille', 'couleur', 'etat', 'description', 'photo', 'statut'];

    protected $attributes = ['statut' => 'disponible'];

    protected static function booted(): void
    {
        // Les demandes liées sont supprimées en base par cascade : on passe d'abord par
        // Eloquent pour que leurs photos soient aussi effacées du disque.
        static::deleting(function (Vetement $vetement) {
            $vetement->demandesTransformation->each->delete();
            $vetement->demandesReparation->each(fn ($demande) => $demande->reparation?->delete());
        });

        // La photo stockée disparaît avec le vêtement.
        static::deleted(fn (Vetement $vetement) => Photo::delete($vetement->photo));
    }

    protected function casts(): array
    {
        return [
            'etat' => EtatVetement::class,
            'statut' => StatutVetement::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieVetement::class, 'categorie_id');
    }

    public function demandesReparation(): HasMany
    {
        return $this->hasMany(DemandeReparation::class);
    }

    public function demandesTransformation(): HasMany
    {
        return $this->hasMany(DemandeTransformation::class);
    }

    public function demandesDon(): HasMany
    {
        return $this->hasMany(DemandeDon::class);
    }

    /** Toutes les demandes du vêtement (réparations, transformations, dons), la plus récente d'abord. */
    public function historique(): Collection
    {
        return collect()
            ->concat($this->demandesReparation()->with('atelier')->get())
            ->concat($this->demandesTransformation()->with('atelier')->get())
            ->concat($this->demandesDon()->with('association')->get())
            ->sortByDesc('created_at')
            ->values();
    }

    public function scopeDisponibles(Builder $query): void
    {
        $query->where('statut', StatutVetement::DISPONIBLE);
    }

    public function estDisponible(): bool
    {
        return $this->statut === StatutVetement::DISPONIBLE;
    }

    public function photoUrl(): ?string
    {
        return Photo::url($this->photo);
    }
}
