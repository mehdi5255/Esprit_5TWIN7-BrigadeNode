<?php

namespace App\Models\Dons;

use App\Enums\StatutDon;
use App\Enums\StatutStructure;
use App\Enums\TypeEvenement;
use App\Notifications\EvenementAnnule;
use Database\Factories\EvenementFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

/**
 * Collecte ou distribution organisée par une association.
 * Relation N-N avec les dons (pivot demande_don_evenement) : une collecte reçoit plusieurs dons,
 * et un même don est déposé à une collecte puis remis lors d'une distribution.
 */
#[UseFactory(EvenementFactory::class)]
class Evenement extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'titre', 'description', 'lieu', 'latitude', 'longitude', 'debut', 'fin', 'places', 'annule'];

    protected $attributes = ['annule' => false];

    protected function casts(): array
    {
        return [
            'type' => TypeEvenement::class,
            'debut' => 'datetime',
            'fin' => 'datetime',
            'places' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'annule' => 'boolean',
        ];
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }

    public function dons(): BelongsToMany
    {
        return $this->belongsToMany(DemandeDon::class, 'demande_don_evenement')->withTimestamps();
    }

    /** Pas encore terminé et pas annulé. */
    public function scopeAVenir(Builder $query): void
    {
        $query->where('annule', false)->where('fin', '>=', now());
    }

    /** Affiché au public : à venir, d'une association validée. */
    public function scopeVisibles(Builder $query): void
    {
        $query->aVenir()->whereHas('association', fn (Builder $q) => $q->where('statut', StatutStructure::ACTIF));
    }

    public function scopeDeType(Builder $query, TypeEvenement $type): void
    {
        $query->where('type', $type);
    }

    public function estCollecte(): bool
    {
        return $this->type === TypeEvenement::COLLECTE;
    }

    public function estPasse(): bool
    {
        return $this->fin->isPast();
    }

    /** Dons qui occupent une place : tous sauf les dons refusés ou annulés. */
    public function donsActifs(): BelongsToMany
    {
        return $this->dons()->whereNotIn('statut', [StatutDon::REFUSEE, StatutDon::ANNULEE]);
    }

    public function placesRestantes(): ?int
    {
        return $this->places === null ? null : max(0, $this->places - $this->donsActifs()->count());
    }

    public function estComplet(): bool
    {
        return $this->placesRestantes() === 0;
    }

    /** Une collecte peut encore recevoir un don : à venir, non annulée, pas complète. */
    public function accepteDesDons(): bool
    {
        return $this->estCollecte() && ! $this->annule && ! $this->estPasse() && ! $this->estComplet();
    }

    public function statutLibelle(): string
    {
        return match (true) {
            $this->annule => 'Annulé',
            $this->estPasse() => 'Terminé',
            $this->debut->isPast() => 'En cours',
            default => 'À venir',
        };
    }

    public function statutCouleur(): string
    {
        return match ($this->statutLibelle()) {
            'Annulé' => 'red',
            'Terminé' => 'gray',
            'En cours' => 'amber',
            default => 'green',
        };
    }

    /** « sam. 15 nov. 2026, 10:00 – 17:00 » */
    /** Le lieu a été placé sur la carte par l'association. */
    public function estLocalise(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /** Itinéraire jusqu'au lieu dans Google Maps (position GPS si connue, sinon l'adresse saisie). */
    public function lienItineraire(): string
    {
        $destination = $this->estLocalise() ? "{$this->latitude},{$this->longitude}" : $this->lieu;

        return 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode($destination);
    }

    /** Le lieu dans OpenStreetMap. */
    public function lienCarte(): ?string
    {
        return $this->estLocalise() ? "https://www.openstreetmap.org/?mlat={$this->latitude}&mlon={$this->longitude}#map=17/{$this->latitude}/{$this->longitude}" : null;
    }

    public function horaire(): string
    {
        $fin = $this->fin->isSameDay($this->debut) ? $this->fin->format('H:i') : $this->fin->translatedFormat('d M Y, H:i');

        return $this->debut->translatedFormat('D d M Y, H:i').' – '.$fin;
    }

    /**
     * Annulation : une collecte libère les dons pas encore déposés (ils seront déposés au local)
     * et prévient les particuliers concernés.
     */
    public function annuler(): void
    {
        if ($this->annule) {
            throw new DomainException('Cet événement est déjà annulé.');
        }

        $concernes = DB::transaction(function () {
            $this->update(['annule' => true]);
            if (! $this->estCollecte()) {
                return collect();
            }
            $dons = $this->dons()->whereIn('statut', [StatutDon::EN_ATTENTE, StatutDon::ACCEPTEE])->with(['particulier', 'vetement'])->get();
            $this->dons()->detach($dons->modelKeys());

            return $dons;
        });

        $concernes->each(fn (DemandeDon $don) => $don->particulier->notify(new EvenementAnnule($this, $don)));
    }
}
