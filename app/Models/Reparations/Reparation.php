<?php

namespace App\Models\Reparations;

use App\Support\Photo;
use Database\Factories\ReparationFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(ReparationFactory::class)]
class Reparation extends Model
{
    use HasFactory;

    protected $fillable = ['diagnostic', 'actions_realisees', 'pieces_utilisees', 'duree_reelle', 'photo_avant', 'photo_apres'];

    protected function casts(): array
    {
        return ['duree_reelle' => 'integer'];
    }

    protected static function booted(): void
    {
        static::deleted(function (Reparation $reparation) {
            Photo::delete($reparation->photo_avant);
            Photo::delete($reparation->photo_apres);
        });
    }

    public function demande(): BelongsTo
    {
        return $this->belongsTo(DemandeReparation::class, 'demande_reparation_id');
    }

    public function photoAvantUrl(): ?string
    {
        return Photo::url($this->photo_avant);
    }

    public function photoApresUrl(): ?string
    {
        return Photo::url($this->photo_apres);
    }
}
