<?php

namespace App\Models\Transformations;

use App\Enums\StatutDemande;
use App\Enums\StatutVetement;
use App\Models\Ateliers\Atelier;
use App\Models\Concerns\AUnWorkflow;
use App\Support\Photo;
use Database\Factories\DemandeTransformationFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(DemandeTransformationFactory::class)]
class DemandeTransformation extends Model
{
    use HasFactory, AUnWorkflow;

    public const PREFIXE = 'TRA';
    public const LIBELLE = 'Transformation';

    protected $fillable = ['vetement_id', 'atelier_id', 'inspiration_id', 'apercu_ia', 'particulier_id', 'idee', 'budget_max', 'date_souhaitee'];

    protected $attributes = ['statut' => 'en_attente', 'depot_remis' => false];

    protected static function booted(): void
    {
        static::deleted(function (DemandeTransformation $demande) {
            Photo::delete($demande->photo_avant);
            Photo::delete($demande->photo_apres);
            Photo::delete($demande->apercu_ia);
        });
    }

    protected function casts(): array
    {
        return [
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

    /** Modèle d'upcycling dont la demande s'inspire (null = idée libre). */
    public function inspiration(): BelongsTo
    {
        return $this->belongsTo(Inspiration::class);
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    public function structure(): BelongsTo
    {
        return $this->atelier();
    }

    public function statutVetementPendant(): StatutVetement
    {
        return StatutVetement::EN_TRANSFORMATION;
    }

    public function statutFinal(): StatutDemande
    {
        return StatutDemande::RECUPERE;
    }

    /** Atelier démarre la confection → EN_TRAITEMENT, photo « avant » éventuelle. */
    public function demarrerTraitement(?string $photoAvant = null): void
    {
        $this->transition([StatutDemande::DEPOT_REMIS], StatutDemande::EN_TRAITEMENT, array_filter([
            'date_debut_traitement' => now(),
            'photo_avant' => $photoAvant,
        ]));
    }

    /** Atelier termine → PRET, produit final décrit + photo « après » + notification. */
    public function terminer(float $coutFinal, string $produitFinal, ?string $photoApres = null): void
    {
        $this->transition([StatutDemande::EN_TRAITEMENT], StatutDemande::PRET, array_filter([
            'date_fin_traitement' => now(),
            'cout_final' => $coutFinal,
            'produit_final' => $produitFinal,
            'photo_apres' => $photoApres,
        ], fn ($v) => $v !== null), notifier: true);
    }

    /** Récupération du produit final → RECUPERE, vêtement DISPONIBLE (sous sa nouvelle forme). */
    public function confirmerRecuperation(): void
    {
        $this->transition([StatutDemande::PRET], StatutDemande::RECUPERE, ['date_recuperation' => now()], StatutVetement::DISPONIBLE, notifier: true);
    }

    public function photoAvantUrl(): ?string
    {
        return Photo::url($this->photo_avant);
    }

    public function photoApresUrl(): ?string
    {
        return Photo::url($this->photo_apres);
    }

    /** Aperçu généré par l'IA, joint par le particulier comme référence. */
    public function apercuIaUrl(): ?string
    {
        return Photo::url($this->apercu_ia);
    }

    public function messageNotification(bool $pourStructure = false): string
    {
        $vetement = $this->vetement->nom;
        $atelier = $this->atelier->nom;

        if ($pourStructure) {
            return "Nouvelle demande de transformation de {$this->particulier->name} pour « {$vetement} ».";
        }

        return match ($this->statut) {
            StatutDemande::ACCEPTEE => "{$atelier} a accepté votre projet de transformation pour « {$vetement} ». Vous pouvez déposer le vêtement.",
            StatutDemande::REFUSEE => "{$atelier} a refusé votre projet de transformation pour « {$vetement} » : {$this->motif_refus}",
            StatutDemande::PRET => "Votre transformation de « {$vetement} » est prête ! Découvrez le résultat et récupérez-la chez {$atelier}.",
            StatutDemande::RECUPERE => "Transformation de « {$vetement} » terminée. Donnez votre avis sur {$atelier} !",
            default => "Votre projet de transformation pour « {$vetement} » est maintenant : {$this->statut->label()}.",
        };
    }
}
