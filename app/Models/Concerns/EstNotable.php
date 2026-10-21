<?php

namespace App\Models\Concerns;

use App\Enums\StatutReview;
use App\Enums\StatutStructure;
use App\Models\Transformations\Review;
use App\Support\Photo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** Structure (Atelier ou Association) qui reçoit des avis et a un logo. */
trait EstNotable
{
    protected static function bootEstNotable(): void
    {
        static::deleted(fn ($structure) => Photo::delete($structure->logo));
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /** Ajoute note_moyenne et nombre_avis (avis publiés uniquement). */
    public function scopeAvecNote(Builder $query): void
    {
        $query->withAvg(['reviews as note_moyenne' => fn ($q) => $q->where('statut', StatutReview::PUBLIE)], 'note')
            ->withCount(['reviews as nombre_avis' => fn ($q) => $q->where('statut', StatutReview::PUBLIE)]);
    }

    public function noteMoyenne(): ?float
    {
        $note = $this->note_moyenne ?? $this->reviews()->where('statut', StatutReview::PUBLIE)->avg('note');

        return $note !== null ? round((float) $note, 1) : null;
    }

    public function nombreAvis(): int
    {
        return $this->nombre_avis ?? $this->reviews()->where('statut', StatutReview::PUBLIE)->count();
    }

    public function logoUrl(): ?string
    {
        return Photo::url($this->logo);
    }

    public function adresseComplete(): string
    {
        return collect([$this->adresse, trim($this->code_postal.' '.$this->ville)])->filter()->join(', ');
    }

    public function estActif(): bool
    {
        return $this->statut === StatutStructure::ACTIF;
    }

    public function scopeActifs(Builder $query): void
    {
        $query->where('statut', StatutStructure::ACTIF)->orderBy('nom');
    }
}
