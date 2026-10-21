<?php

namespace App\Models\Vetements;

use App\Models\Dons\BesoinAssociation;
use App\Models\Transformations\Inspiration;
use Database\Factories\CategorieVetementFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(CategorieVetementFactory::class)]
class CategorieVetement extends Model
{
    use HasFactory;

    protected $fillable = ['libelle', 'description', 'icone', 'actif'];

    protected $attributes = ['icone' => 'shirt', 'actif' => true];

    protected function casts(): array
    {
        return ['actif' => 'boolean'];
    }

    public function vetements(): HasMany
    {
        return $this->hasMany(Vetement::class, 'categorie_id');
    }

    /** Modèles d'upcycling partant de cette catégorie (module 3). */
    public function inspirations(): HasMany
    {
        return $this->hasMany(Inspiration::class, 'categorie_id');
    }

    public function besoins(): HasMany
    {
        return $this->hasMany(BesoinAssociation::class, 'categorie_id');
    }

    public function scopeActives(Builder $query): void
    {
        $query->where('actif', true)->orderBy('libelle');
    }
}
