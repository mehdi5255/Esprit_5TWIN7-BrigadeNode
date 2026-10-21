<?php

namespace App\Models\Vetements;

use App\Enums\TypeMatiere;
use Database\Factories\MatiereFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Matière textile du catalogue (géré par l'admin). Relation N-N avec les vêtements,
 * le pourcentage de la matière dans chaque vêtement étant stocké sur le pivot.
 * Les facteurs CO₂ / eau sont des ordres de grandeur par kg fabriqué : l'impact affiché est une estimation.
 */
#[UseFactory(MatiereFactory::class)]
class Matiere extends Model
{
    use HasFactory;

    /** Composition et poids typiques par catégorie, quand l'étiquette est perdue. */
    public const COMPOSITIONS_TYPIQUES = [
        'Haut' => [250, ['Coton' => 100]],
        'Bas' => [600, ['Coton' => 98, 'Élasthanne' => 2]],
        'Robe' => [350, ['Viscose' => 100]],
        'Manteau' => [1200, ['Laine' => 70, 'Polyamide' => 30]],
        'Chaussures' => [800, ['Cuir' => 100]],
        'Enfant' => [150, ['Coton' => 100]],
        'Accessoire' => [200, ['Polyester' => 100]],
    ];

    protected $table = 'matieres';

    protected $fillable = ['nom', 'type', 'abreviations', 'co2_kg_par_kg', 'eau_litres_par_kg', 'entretien', 'actif'];

    protected $attributes = ['actif' => true];

    protected function casts(): array
    {
        return [
            'type' => TypeMatiere::class,
            'abreviations' => 'array',
            'co2_kg_par_kg' => 'decimal:2',
            'eau_litres_par_kg' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function vetements(): BelongsToMany
    {
        return $this->belongsToMany(Vetement::class)->withPivot('pourcentage')->withTimestamps();
    }

    public function scopeActives(Builder $query): void
    {
        $query->where('actif', true)->orderBy('nom');
    }

    /** Mots qui désignent la matière sur une étiquette, normalisés (sans accents, en minuscules). */
    public function motsCles(): array
    {
        return collect([$this->nom, ...($this->abreviations ?? [])])
            ->map(fn ($mot) => Str::lower(Str::ascii(trim($mot))))
            ->filter()->unique()->values()->all();
    }

    /**
     * Composition typique d'une catégorie : [poids en g, [matiere_id => pourcentage]].
     * Catégorie inconnue : 400 g de « Autre matière ».
     */
    public static function compositionTypique(?string $categorie): array
    {
        [$poids, $parNom] = self::COMPOSITIONS_TYPIQUES[$categorie] ?? [400, ['Autre matière' => 100]];
        $ids = static::whereIn('nom', array_keys($parNom))->pluck('id', 'nom');

        return [$poids, collect($parNom)->mapWithKeys(fn ($pourcentage, $nom) => [$ids[$nom] ?? null => $pourcentage])->filter(fn ($p, $id) => $id)->all()];
    }
}
