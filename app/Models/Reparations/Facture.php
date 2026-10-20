<?php

namespace App\Models\Reparations;

use App\Enums\StatutFacture;
use Database\Factories\FactureFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Facture émise à la fin de la réparation ; une fois payée, elle ouvre droit à une garantie. */
#[UseFactory(FactureFactory::class)]
class Facture extends Model
{
    use HasFactory;

    protected $fillable = ['montant_devis', 'supplement', 'motif_supplement', 'montant_total', 'statut', 'date_emission', 'date_paiement'];

    protected $attributes = ['statut' => 'emise', 'supplement' => 0];

    protected function casts(): array
    {
        return [
            'statut' => StatutFacture::class,
            'montant_devis' => 'decimal:2',
            'supplement' => 'decimal:2',
            'montant_total' => 'decimal:2',
            'date_emission' => 'datetime',
            'date_paiement' => 'datetime',
        ];
    }

    public function demande(): BelongsTo
    {
        return $this->belongsTo(DemandeReparation::class, 'demande_reparation_id');
    }

    public function garantie(): HasOne
    {
        return $this->hasOne(Garantie::class);
    }

    public function numero(): string
    {
        return 'FAC-'.$this->created_at->format('Y').'-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function estPayee(): bool
    {
        return $this->statut === StatutFacture::PAYEE;
    }
}
