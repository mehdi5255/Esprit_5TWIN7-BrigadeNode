<?php

namespace App\Models\Reparations;

use App\Enums\StatutDevis;
use Database\Factories\DevisFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Proposition de prix de l'atelier : le particulier l'accepte (dépôt possible) ou la refuse (demande annulée). */
#[UseFactory(DevisFactory::class)]
class Devis extends Model
{
    use HasFactory;

    /** Durée de validité par défaut d'un devis. */
    public const VALIDITE_JOURS = 15;

    protected $table = 'devis';

    protected $fillable = ['main_oeuvre', 'delai_jours', 'valide_jusqu_au', 'statut', 'date_reponse'];

    protected $attributes = ['statut' => 'propose'];

    protected function casts(): array
    {
        return [
            'statut' => StatutDevis::class,
            'main_oeuvre' => 'decimal:2',
            'delai_jours' => 'integer',
            'valide_jusqu_au' => 'date',
            'date_reponse' => 'datetime',
        ];
    }

    public function demande(): BelongsTo
    {
        return $this->belongsTo(DemandeReparation::class, 'demande_reparation_id');
    }

    public function numero(): string
    {
        return 'DEV-'.$this->created_at->format('Y').'-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /** Un devis sans réponse après sa date de validité ne peut plus être accepté. */
    public function estExpire(): bool
    {
        return $this->statut === StatutDevis::PROPOSE && $this->valide_jusqu_au->endOfDay()->isPast();
    }

    public function peutEtreAccepte(): bool
    {
        return $this->statut === StatutDevis::PROPOSE && ! $this->estExpire();
    }

    public function libelleStatut(): string
    {
        return $this->estExpire() ? 'Expiré' : $this->statut->label();
    }

    public function couleurStatut(): string
    {
        return $this->estExpire() ? 'gray' : $this->statut->color();
    }
}
