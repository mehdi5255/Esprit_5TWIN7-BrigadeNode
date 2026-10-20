<?php

namespace App\Models\Dons;

use App\Enums\OrientationDon;
use App\Enums\StatutDon;
use App\Enums\StatutVetement;
use App\Enums\TypeEvenement;
use App\Models\Concerns\AUnWorkflow;
use Database\Factories\DemandeDonFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

#[UseFactory(DemandeDonFactory::class)]
class DemandeDon extends Model
{
    use HasFactory, AUnWorkflow;

    public const PREFIXE = 'DON';
    public const LIBELLE = 'Don';

    protected $fillable = ['vetement_id', 'association_id', 'besoin_id', 'particulier_id', 'message', 'controle_ia'];

    protected $attributes = ['statut' => 'en_attente', 'depot_remis' => false];

    protected function casts(): array
    {
        return [
            'statut' => StatutDon::class,
            'orientation' => OrientationDon::class,
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

    /** Collecte et distribution par lesquelles passe le don (N-N, pivot demande_don_evenement). */
    public function evenements(): BelongsToMany
    {
        return $this->belongsToMany(Evenement::class, 'demande_don_evenement')->withTimestamps();
    }

    /** Collecte où le particulier dépose le don (null = dépôt au local). */
    public function collecte(): ?Evenement
    {
        return $this->evenements->firstWhere('type', TypeEvenement::COLLECTE);
    }

    /** Distribution lors de laquelle le don a été remis. */
    public function distribution(): ?Evenement
    {
        return $this->evenements->firstWhere('type', TypeEvenement::DISTRIBUTION);
    }

    /** Rattache le don à une collecte de son association, à venir et pas complète. */
    public function rattacherCollecte(Evenement $collecte): void
    {
        if ($collecte->association_id !== $this->association_id) {
            throw new DomainException("Cette collecte n'est pas organisée par l'association choisie.");
        }
        if (! $collecte->accepteDesDons()) {
            throw new DomainException($collecte->estComplet() ? 'Cette collecte est complète.' : "Cette collecte n'accepte plus de dons.");
        }

        $this->evenements()->syncWithoutDetaching([$collecte->id]);
        $this->unsetRelation('evenements');
    }

    public function statutVetementPendant(): StatutVetement
    {
        return StatutVetement::EN_DON;
    }

    public function statutFinal(): StatutDon
    {
        return StatutDon::REMIS_BENEFICIAIRE;
    }

    /**
     * Contrôle qualité à la réception : l'association décide du devenir du vêtement
     * (redistribution, boutique solidaire, recyclage), éventuellement sur proposition de l'IA.
     */
    public function orienter(OrientationDon $orientation, ?string $motif = null): void
    {
        if (! in_array($this->statut, [StatutDon::DEPOT_REMIS, StatutDon::EN_TRAITEMENT], true)) {
            throw new DomainException('Le don s’oriente une fois le vêtement reçu, avant sa remise.');
        }

        $this->forceFill(['orientation' => $orientation, 'orientation_motif' => $motif])->save();
    }

    /** Association prépare le don → EN_TRAITEMENT. */
    public function preparer(): void
    {
        $this->transition([StatutDon::DEPOT_REMIS], StatutDon::EN_TRAITEMENT, ['date_debut_traitement' => now()]);
    }

    /** Remise aux bénéficiaires → REMIS_BENEFICIAIRE, vêtement TERMINE + notification ; éventuellement lors d'une distribution. */
    public function remettre(string $beneficiaireInfo, int $nombreBeneficiaires, ?Evenement $distribution = null): void
    {
        if ($distribution && ($distribution->association_id !== $this->association_id || $distribution->estCollecte() || $distribution->annule)) {
            throw new DomainException("Choisissez une distribution de l'association, non annulée.");
        }

        DB::transaction(function () use ($beneficiaireInfo, $nombreBeneficiaires, $distribution) {
            if ($distribution) {
                $this->evenements()->syncWithoutDetaching([$distribution->id]);
                $this->unsetRelation('evenements');
            }
            $this->transition([StatutDon::EN_TRAITEMENT], StatutDon::REMIS_BENEFICIAIRE, [
                'date_remise' => now(),
                'beneficiaire_info' => $beneficiaireInfo,
                'nombre_beneficiaires' => $nombreBeneficiaires,
            ], StatutVetement::TERMINE, notifier: true);
        });
    }

    public function messageNotification(bool $pourStructure = false): string
    {
        $vetement = $this->vetement->nom;
        $association = $this->association->nom;

        if ($pourStructure) {
            return "Nouvelle proposition de don de {$this->particulier->name} : « {$vetement} ».";
        }

        return match ($this->statut) {
            StatutDon::ACCEPTEE => ($collecte = $this->collecte())
                ? "{$association} a accepté votre don « {$vetement} ». Apportez-le à la collecte « {$collecte->titre} » le {$collecte->debut->format('d/m/Y à H:i')}, {$collecte->lieu}."
                : "{$association} a accepté votre don « {$vetement} ». Vous pouvez l'apporter à l'association.",
            StatutDon::REFUSEE => "{$association} a refusé votre don « {$vetement} » : {$this->motif_refus}",
            StatutDon::REMIS_BENEFICIAIRE => "Merci ! « {$vetement} » a été remis à {$this->nombre_beneficiaires} bénéficiaire(s) par {$association}.",
            default => "Votre don « {$vetement} » est maintenant : {$this->statut->label()}.",
        };
    }
}
