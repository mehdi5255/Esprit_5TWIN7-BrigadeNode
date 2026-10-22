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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[UseFactory(VetementFactory::class)]
class Vetement extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'categorie_id', 'nom', 'matiere', 'poids_grammes', 'composition_estimee', 'taille', 'couleur', 'etat', 'description', 'photo', 'statut'];

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
            'poids_grammes' => 'integer',
            'composition_estimee' => 'boolean',
        ];
    }

    /** Composition : relation N-N avec le pourcentage de chaque matière sur le pivot. */
    public function matieres(): BelongsToMany
    {
        return $this->belongsToMany(Matiere::class)->withPivot('pourcentage')->withTimestamps()->orderByPivot('pourcentage', 'desc');
    }

    /** Enregistre la composition : [matiere_id => pourcentage]. */
    public function definirComposition(array $composition): void
    {
        $this->matieres()->sync(collect($composition)->map(fn ($pourcentage) => ['pourcentage' => (int) $pourcentage])->all());
        $this->unsetRelation('matieres');
    }

    /** « 80 % Coton · 20 % Polyester » */
    public function compositionTexte(): ?string
    {
        return $this->matieres->isEmpty() ? null : $this->matieres->map(fn (Matiere $m) => "{$m->pivot->pourcentage} % {$m->nom}")->join(' · ');
    }

    /**
     * Contenu du QR code « passeport textile » : du texte lisible par n'importe quel appareil photo,
     * que le scan du formulaire sait relire (composition et poids) pour le prochain propriétaire.
     * Null sans composition.
     */
    public function passeportTexte(): ?string
    {
        if ($this->matieres->isEmpty()) {
            return null;
        }

        return collect([
            'TextileCycle - Passeport textile',
            "Nom : {$this->nom}",
            'Composition : '.$this->matieres->map(fn (Matiere $m) => "{$m->pivot->pourcentage}% {$m->nom}")->join(', '),
            $this->poids_grammes ? "Poids : {$this->poids_grammes} g" : null,
        ])->filter()->join("\n");
    }

    /**
     * Impact de fabrication d'un vêtement équivalent neuf, évité en le gardant en vie :
     * Σ poids (kg) × pourcentage × facteur de la matière. Null sans poids ou sans composition.
     *
     * @return array{co2: float, eau: int}|null
     */
    public function impact(): ?array
    {
        if (! $this->poids_grammes || $this->matieres->isEmpty()) {
            return null;
        }

        $kg = $this->poids_grammes / 1000;

        return [
            'co2' => round($this->matieres->sum(fn (Matiere $m) => $kg * $m->pivot->pourcentage / 100 * (float) $m->co2_kg_par_kg), 1),
            'eau' => (int) round($this->matieres->sum(fn (Matiere $m) => $kg * $m->pivot->pourcentage / 100 * $m->eau_litres_par_kg)),
        ];
    }

    /** Conseils d'entretien des matières du vêtement. */
    public function conseilsEntretien(): Collection
    {
        return $this->matieres->filter(fn (Matiere $m) => $m->entretien)->mapWithKeys(fn (Matiere $m) => [$m->nom => $m->entretien]);
    }

    /** Vêtement « sauvé » : au moins une réparation ou transformation récupérée, ou un don remis. */
    public function scopeSauves(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q
            ->whereHas('demandesReparation', fn ($d) => $d->where('statut', 'recupere'))
            ->orWhereHas('demandesTransformation', fn ($d) => $d->where('statut', 'recupere'))
            ->orWhereHas('demandesDon', fn ($d) => $d->where('statut', 'remis_beneficiaire')));
    }

    /**
     * Impact économisé par un ensemble de vêtements (ceux d'un particulier, ou toute la plateforme) :
     * chaque vêtement sauvé compte une fois, même s'il a été réparé plusieurs fois.
     *
     * @return array{vetements: int, co2: float, eau: int}
     */
    public static function impactEconomise(Builder $vetements): array
    {
        $sauves = $vetements->sauves()->with('matieres')->get();
        $impacts = $sauves->map->impact()->filter();

        return [
            'vetements' => $sauves->count(),
            'co2' => round($impacts->sum('co2'), 1),
            'eau' => (int) $impacts->sum('eau'),
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
