<?php

namespace App\Models\Reparations;

use App\Enums\StatutDemande;
use App\Enums\StatutVetement;
use App\Enums\TypeReparation;
use App\Models\Ateliers\Atelier;
use App\Models\Concerns\AUnWorkflow;
use Database\Factories\DemandeReparationFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

#[UseFactory(DemandeReparationFactory::class)]
class DemandeReparation extends Model
{
    use HasFactory, AUnWorkflow;

    public const PREFIXE = 'REP';
    public const LIBELLE = 'Réparation';

    protected $fillable = ['vetement_id', 'atelier_id', 'particulier_id', 'type_reparation', 'description', 'budget_max', 'date_souhaitee'];

    protected $attributes = ['statut' => 'en_attente', 'depot_remis' => false];

    protected function casts(): array
    {
        return [
            'type_reparation' => TypeReparation::class,
            'statut' => StatutDemande::class,
            'budget_max' => 'decimal:2',
            'cout_final' => 'decimal:2',
            'depot_remis' => 'boolean',
            'date_souhaitee' => 'datetime',
            'date_acceptation' => 'datetime',
            'date_depot_effectif' => 'datetime',
            'date_debut_traitement' => 'datetime',
            'date_fin_traitement' => 'datetime',
            'date_recuperation' => 'datetime',
        ];
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    public function structure(): BelongsTo
    {
        return $this->atelier();
    }

    public function reparation(): HasOne
    {
        return $this->hasOne(Reparation::class);
    }

    public function statutVetementPendant(): StatutVetement
    {
        return StatutVetement::EN_REPARATION;
    }

    public function statutFinal(): StatutDemande
    {
        return StatutDemande::RECUPERE;
    }

    /** 6. Responsable démarre le traitement → EN_TRAITEMENT + création de la Reparation. */
    public function demarrerTraitement(array $reparation = []): void
    {
        DB::transaction(function () use ($reparation) {
            $this->transition([StatutDemande::DEPOT_REMIS], StatutDemande::EN_TRAITEMENT, ['date_debut_traitement' => now()]);
            $this->reparation()->create($reparation);
        });
    }

    /** 7. Responsable termine → PRET, coût final renseigné + notification. */
    public function terminer(float $coutFinal, array $reparation = []): void
    {
        DB::transaction(function () use ($coutFinal, $reparation) {
            $this->transition([StatutDemande::EN_TRAITEMENT], StatutDemande::PRET, [
                'date_fin_traitement' => now(),
                'cout_final' => $coutFinal,
            ], notifier: true);
            $this->reparation()->firstOrCreate()->update($reparation);
        });
    }

    /** 9. Responsable confirme la récupération → RECUPERE, vêtement DISPONIBLE. */
    public function confirmerRecuperation(): void
    {
        $this->transition([StatutDemande::PRET], StatutDemande::RECUPERE, ['date_recuperation' => now()], StatutVetement::DISPONIBLE, notifier: true);
    }

    public function messageNotification(bool $pourStructure = false): string
    {
        $vetement = $this->vetement->nom;
        $atelier = $this->atelier->nom;

        if ($pourStructure) {
            return "Nouvelle demande de réparation de {$this->particulier->name} pour « {$vetement} ».";
        }

        return match ($this->statut) {
            StatutDemande::ACCEPTEE => "{$atelier} a accepté votre demande de réparation pour « {$vetement} ». Vous pouvez déposer le vêtement.",
            StatutDemande::REFUSEE => "{$atelier} a refusé votre demande de réparation pour « {$vetement} » : {$this->motif_refus}",
            StatutDemande::PRET => "« {$vetement} » est réparé ! Vous pouvez le récupérer chez {$atelier}.",
            StatutDemande::RECUPERE => "Réparation de « {$vetement} » terminée. Donnez votre avis sur {$atelier} !",
            default => "Votre demande de réparation pour « {$vetement} » est maintenant : {$this->statut->label()}.",
        };
    }
}
