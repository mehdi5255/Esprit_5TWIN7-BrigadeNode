<?php

namespace App\Models\Transformations;

use App\Enums\StatutReview;
use App\Models\User;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[UseFactory(ReviewFactory::class)]
class Review extends Model
{
    use HasFactory;

    protected $fillable = ['note', 'commentaire'];

    protected $attributes = ['statut' => 'publie'];

    protected function casts(): array
    {
        return [
            'statut' => StatutReview::class,
            'note' => 'integer',
            'date_reponse' => 'datetime',
        ];
    }

    public function particulier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'particulier_id');
    }

    /** Atelier ou Association noté(e). */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Demande terminée à l'origine de l'avis. */
    public function demande(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePublies(Builder $query): void
    {
        $query->where('statut', StatutReview::PUBLIE);
    }
}
