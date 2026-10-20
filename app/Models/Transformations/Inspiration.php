<?php

namespace App\Models\Transformations;

use App\Enums\StatutStructure;
use App\Enums\TypeService;
use App\Models\Ateliers\Atelier;
use App\Models\Vetements\CategorieVetement;
use App\Support\Photo;
use Database\Factories\InspirationFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle d'upcycling publié par un atelier (ex. « Jean → Sac cabas »).
 * Le particulier peut s'en inspirer : sa demande de transformation est alors préremplie
 * et garde le lien vers l'inspiration.
 */
#[UseFactory(InspirationFactory::class)]
class Inspiration extends Model
{
    use HasFactory;

    protected $fillable = ['categorie_id', 'titre', 'description', 'photo', 'prix_indicatif', 'delai_jours', 'actif'];

    protected $attributes = ['actif' => true];

    protected static function booted(): void
    {
        static::deleted(fn (Inspiration $inspiration) => Photo::delete($inspiration->photo));
    }

    protected function casts(): array
    {
        return [
            'prix_indicatif' => 'decimal:2',
            'delai_jours' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieVetement::class);
    }

    /** Demandes de transformation inspirées de ce modèle. */
    public function demandes(): HasMany
    {
        return $this->hasMany(DemandeTransformation::class);
    }

    /** Visible des particuliers : active, d'un atelier validé qui propose toujours la transformation. */
    public function scopeVisibles(Builder $query): void
    {
        $query->where('actif', true)->whereHas('atelier', fn (Builder $q) => $q
            ->where('statut', StatutStructure::ACTIF)
            ->proposant(TypeService::TRANSFORMATION));
    }

    public function estVisible(): bool
    {
        return static::visibles()->whereKey($this->id)->exists();
    }

    public function photoUrl(): ?string
    {
        return Photo::url($this->photo);
    }

    public function prixFormate(): string
    {
        return $this->prix_indicatif !== null ? 'à partir de '.number_format((float) $this->prix_indicatif, 2, ',', ' ').' €' : 'Sur devis';
    }

    /** Texte proposé dans le champ « idée » quand le particulier choisit ce modèle. */
    public function ideePreremplie(): string
    {
        return "Inspiré de « {$this->titre} » : {$this->description}";
    }
}
