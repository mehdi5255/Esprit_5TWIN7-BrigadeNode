<?php

namespace App\Models\Dons;

use App\Enums\StatutDon;
use App\Enums\StatutVetement;
use App\Models\Concerns\AUnWorkflow;
use Database\Factories\DemandeDonFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(DemandeDonFactory::class)]
class DemandeDon extends Model
{
    use HasFactory, AUnWorkflow;

    public const PREFIXE = 'DON';
    public const LIBELLE = 'Don';

    protected $fillable = ['vetement_id', 'association_id', 'besoin_id', 'particulier_id', 'message'];

    protected $attributes = ['statut' => 'en_attente', 'depot_remis' => false];

    protected function casts(): array
    {
        return [
            'statut' => StatutDon::class,
            'depot_remis' => 'boolean',
            'date_acceptation' => 'datetime',
            'date_depot_effectif' => 'datetime',
            'date_debut_traitement' => 'datetime',
            'date_remise' => 'datetime',
            'nombre_beneficiaires' => 'integer',
        ];
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }

    public function structure(): BelongsTo
    {
        return $this->association();
    }

    public function besoin(): BelongsTo
    {
        return $this->belongsTo(BesoinAssociation::class, 'besoin_id');
    }

    public function statutVetementPendant(): StatutVetement
    {
        return StatutVetement::EN_DON;
    }

    public function statutFinal(): StatutDon
    {
        return StatutDon::REMIS_BENEFICIAIRE;
    }

    /** Association prépare le don → EN_TRAITEMENT. */
    public function preparer(): void
    {
        $this->transition([StatutDon::DEPOT_REMIS], StatutDon::EN_TRAITEMENT, ['date_debut_traitement' => now()]);
    }

    /** Remise aux bénéficiaires → REMIS_BENEFICIAIRE, vêtement TERMINE + notification. */
    public function remettre(string $beneficiaireInfo, int $nombreBeneficiaires): void
    {
        $this->transition([StatutDon::EN_TRAITEMENT], StatutDon::REMIS_BENEFICIAIRE, [
            'date_remise' => now(),
            'beneficiaire_info' => $beneficiaireInfo,
            'nombre_beneficiaires' => $nombreBeneficiaires,
        ], StatutVetement::TERMINE, notifier: true);
    }

    public function messageNotification(bool $pourStructure = false): string
    {
        $vetement = $this->vetement->nom;
        $association = $this->association->nom;

        if ($pourStructure) {
            return "Nouvelle proposition de don de {$this->particulier->name} : « {$vetement} ».";
        }

        return match ($this->statut) {
            StatutDon::ACCEPTEE => "{$association} a accepté votre don « {$vetement} ». Vous pouvez l'apporter à l'association.",
            StatutDon::REFUSEE => "{$association} a refusé votre don « {$vetement} » : {$this->motif_refus}",
            StatutDon::REMIS_BENEFICIAIRE => "Merci ! « {$vetement} » a été remis à {$this->nombre_beneficiaires} bénéficiaire(s) par {$association}.",
            default => "Votre don « {$vetement} » est maintenant : {$this->statut->label()}.",
        };
    }
}
