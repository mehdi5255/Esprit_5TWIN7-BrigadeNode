<?php

namespace App\Models\Reparations;

use App\Enums\StatutDemande;
use App\Enums\StatutDevis;
use App\Enums\StatutFacture;
use App\Enums\StatutVetement;
use App\Enums\TypeReparation;
use App\Models\Ateliers\Atelier;
use App\Models\Concerns\AUnWorkflow;
use App\Notifications\DemandeMiseAJour;
use App\Notifications\ReparationPrete;
use App\Support\Photo;
use Database\Factories\DemandeReparationFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * Demande de réparation. En plus du parcours commun (AUnWorkflow), elle passe par :
 * acceptation → dépôt → diagnostic et devis (accepté par le particulier avant la réparation) → facture → garantie.
 * Un retour sous garantie est une nouvelle demande liée à la garantie, sans frais.
 */
#[UseFactory(DemandeReparationFactory::class)]
class DemandeReparation extends Model
{
    use HasFactory, AUnWorkflow;

    public const PREFIXE = 'REP';
    public const LIBELLE = 'Réparation';

    protected $fillable = ['vetement_id', 'atelier_id', 'garantie_id', 'particulier_id', 'type_reparation', 'description', 'photo_probleme', 'budget_max', 'date_souhaitee'];

    protected $attributes = ['statut' => 'en_attente', 'depot_remis' => false];

    protected static function booted(): void
    {
        static::deleted(fn (DemandeReparation $demande) => Photo::delete($demande->photo_probleme));
    }

    /** Photo de l'abîmement envoyée par le particulier. */
    public function photoProblemeUrl(): ?string
    {
        return Photo::url($this->photo_probleme);
    }

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

    public function devis(): HasOne
    {
        return $this->hasOne(Devis::class);
    }

    public function facture(): HasOne
    {
        return $this->hasOne(Facture::class);
    }

    /** Garantie utilisée par cette demande quand c'est un retour sous garantie. */
    public function garantie(): BelongsTo
    {
        return $this->belongsTo(Garantie::class);
    }

    public function estRetourSousGarantie(): bool
    {
        return $this->garantie_id !== null;
    }

    /** Garantie obtenue grâce à cette réparation (après paiement de la facture). */
    public function garantieAccordee(): ?Garantie
    {
        return $this->facture?->garantie;
    }

    public function statutVetementPendant(): StatutVetement
    {
        return StatutVetement::EN_REPARATION;
    }

    public function statutFinal(): StatutDemande
    {
        return StatutDemande::RECUPERE;
    }

    /**
     * 2. L'atelier accepte la demande, sans devis : il ne peut chiffrer qu'après avoir vu le vêtement.
     *    (Méthode commune du trait AUnWorkflow : EN_ATTENTE → ACCEPTEE + notification.)
     * 3. Le particulier dépose le vêtement : DEPOT_REMIS (méthode commune confirmerDepot).
     */

    /**
     * 4. Au dépôt, l'atelier diagnostique le vêtement et établit le devis (statut toujours DEPOT_REMIS).
     *    La fiche technique Reparation est créée avec le diagnostic. Un retour sous garantie reçoit un devis à 0 €, accepté d'office.
     */
    public function etablirDevis(array $diagnostic, array $devis = []): void
    {
        if ($this->statut !== StatutDemande::DEPOT_REMIS) {
            throw new DomainException('Le devis s’établit une fois le vêtement déposé et diagnostiqué.');
        }
        if ($this->devis()->exists()) {
            throw new DomainException('Un devis existe déjà pour cette demande : modifiez-le si besoin.');
        }

        DB::transaction(function () use ($diagnostic, $devis) {
            $this->reparation()->updateOrCreate([], $diagnostic);
            $this->setRelation('devis', $this->devis()->create($this->estRetourSousGarantie()
                ? ['main_oeuvre' => 0, 'delai_jours' => $devis['delai_jours'] ?? null, 'valide_jusqu_au' => now(), 'statut' => StatutDevis::ACCEPTE, 'date_reponse' => now()]
                : [
                    'main_oeuvre' => $devis['main_oeuvre'] ?? 0,
                    'delai_jours' => $devis['delai_jours'] ?? null,
                    'valide_jusqu_au' => $devis['valide_jusqu_au'] ?? now()->addDays(Devis::VALIDITE_JOURS),
                ]));
        });

        $this->particulier->notify(new DemandeMiseAJour($this));
    }

    /** L'atelier corrige son devis tant que le particulier n'a pas répondu (ou prolonge un devis expiré). */
    public function modifierDevis(array $devis): void
    {
        if ($this->devis?->statut !== StatutDevis::PROPOSE) {
            throw new DomainException('Le particulier a déjà répondu à ce devis : il ne peut plus être modifié.');
        }

        $this->devis->update($devis);
    }

    /** 5a. Le particulier accepte le devis : l'atelier peut démarrer la réparation. */
    public function accepterDevis(): void
    {
        if ($this->statut !== StatutDemande::DEPOT_REMIS || ! $this->devis) {
            throw new DomainException('Aucun devis en attente de réponse pour cette demande.');
        }
        if ($this->devis->estExpire()) {
            throw new DomainException('Ce devis a expiré le '.$this->devis->valide_jusqu_au->format('d/m/Y').' : il ne peut plus être accepté.');
        }
        if (! $this->devis->peutEtreAccepte()) {
            throw new DomainException('Vous avez déjà répondu à ce devis.');
        }

        $this->devis->update(['statut' => StatutDevis::ACCEPTE, 'date_reponse' => now()]);
        $this->atelier->responsable?->notify(new DemandeMiseAJour($this, pourStructure: true));
    }

    /** 5b. Le particulier refuse le devis : la demande est annulée et il récupère son vêtement, sans frais. */
    public function refuserDevis(): void
    {
        if ($this->devis?->statut !== StatutDevis::PROPOSE) {
            throw new DomainException('Aucun devis en attente de réponse pour cette demande.');
        }

        DB::transaction(function () {
            $this->devis->update(['statut' => StatutDevis::REFUSE, 'date_reponse' => now()]);
            $this->transition([StatutDemande::DEPOT_REMIS], StatutDemande::ANNULEE, vetement: StatutVetement::DISPONIBLE);
        });

        $this->atelier->responsable?->notify(new DemandeMiseAJour($this, pourStructure: true));
    }

    /** 6. L'atelier démarre la réparation → EN_TRAITEMENT, seulement après acceptation du devis. */
    public function demarrerTraitement(array $reparation = []): void
    {
        if ($this->statut === StatutDemande::DEPOT_REMIS && $this->devis?->statut !== StatutDevis::ACCEPTE) {
            throw new DomainException('La réparation démarre une fois le devis accepté par le particulier.');
        }

        DB::transaction(function () use ($reparation) {
            $this->transition([StatutDemande::DEPOT_REMIS], StatutDemande::EN_TRAITEMENT, ['date_debut_traitement' => now()]);
            $fiche = $this->reparation()->firstOrCreate();
            if ($reparation = array_filter($reparation, fn ($valeur) => $valeur !== null)) {
                $fiche->update($reparation);
            }
        });
    }

    /**
     * 7. L'atelier termine → PRET, et la facture est émise :
     *    montant du devis + supplément éventuel (avec motif). Pas de supplément sous garantie.
     */
    public function terminer(array $facture = [], array $reparation = []): void
    {
        $supplement = $this->estRetourSousGarantie() ? 0.0 : round((float) ($facture['supplement'] ?? 0), 2);
        $montantDevis = (float) ($this->devis?->main_oeuvre ?? 0);
        $total = $montantDevis + $supplement;

        DB::transaction(function () use ($reparation, $supplement, $montantDevis, $total, $facture) {
            $this->transition([StatutDemande::EN_TRAITEMENT], StatutDemande::PRET, [
                'date_fin_traitement' => now(),
                'cout_final' => $total,
            ], notifier: true);
            $this->reparation()->firstOrCreate()->update($reparation);
            $this->facture()->create([
                'montant_devis' => $montantDevis,
                'supplement' => $supplement,
                'motif_supplement' => $supplement > 0 ? ($facture['motif_supplement'] ?? null) : null,
                'montant_total' => $total,
                'date_emission' => now(),
            ]);
        });

        // E-mail au particulier : son vêtement l'attend. Un souci d'envoi ne bloque pas l'atelier.
        rescue(fn () => $this->particulier->notify(new ReparationPrete($this)));
    }

    /** 8-9. Récupération et paiement → RECUPERE, facture payée, garantie créée (sauf pour un retour). */
    public function confirmerRecuperation(): void
    {
        DB::transaction(function () {
            $this->transition([StatutDemande::PRET], StatutDemande::RECUPERE, ['date_recuperation' => now()], StatutVetement::DISPONIBLE);

            if ($facture = $this->facture) {
                $facture->update(['statut' => StatutFacture::PAYEE, 'date_paiement' => now()]);

                if (! $this->estRetourSousGarantie()) {
                    $facture->garantie()->create([
                        'duree_mois' => Garantie::DUREE_MOIS,
                        'date_debut' => now(),
                        'date_fin' => now()->addMonths(Garantie::DUREE_MOIS),
                        'conditions' => "Couvre la réparation effectuée ({$this->type_reparation->label()}) pendant ".Garantie::DUREE_MOIS.' mois, hors usure normale, accident ou mauvais entretien.',
                    ]);
                }
            }
        });

        $this->particulier->notify(new DemandeMiseAJour($this));
    }

    /** 10. Retour sous garantie : nouvelle demande gratuite, chez le même atelier, pour le même vêtement. */
    public static function ouvrirRetour(Garantie $garantie, string $description): static
    {
        if (! $garantie->estActive()) {
            throw new DomainException('Cette garantie a expiré le '.$garantie->date_fin->format('d/m/Y').'.');
        }

        $origine = $garantie->demandeOrigine();

        return static::creer($origine->vetement, [
            'atelier_id' => $origine->atelier_id,
            'garantie_id' => $garantie->id,
            'type_reparation' => $origine->type_reparation,
            'description' => $description,
        ]);
    }

    public function messageNotification(bool $pourStructure = false): string
    {
        $vetement = $this->vetement->nom;
        $atelier = $this->atelier->nom;
        $particulier = $this->particulier->name;
        $euros = fn ($montant) => number_format((float) $montant, 2, ',', ' ').' €';

        if ($pourStructure) {
            return match (true) {
                $this->statut === StatutDemande::ANNULEE && $this->devis?->statut === StatutDevis::REFUSE => "{$particulier} a refusé votre devis pour « {$vetement} » : la demande est annulée, rendez-lui le vêtement.",
                $this->statut === StatutDemande::DEPOT_REMIS && $this->devis?->statut === StatutDevis::ACCEPTE => "{$particulier} a accepté votre devis de {$euros($this->devis->main_oeuvre)} pour « {$vetement} ». Vous pouvez démarrer la réparation.",
                $this->estRetourSousGarantie() => "Retour sous garantie de {$particulier} pour « {$vetement} ».",
                default => "Nouvelle demande de réparation de {$particulier} pour « {$vetement} ».",
            };
        }

        return match ($this->statut) {
            StatutDemande::ACCEPTEE => $this->estRetourSousGarantie()
                ? "{$atelier} prend en charge votre retour sous garantie pour « {$vetement} ». Vous pouvez déposer le vêtement."
                : "{$atelier} a accepté votre demande pour « {$vetement} ». Déposez le vêtement : l’atelier établira le devis après l’avoir diagnostiqué.",
            StatutDemande::DEPOT_REMIS => match (true) {
                $this->estRetourSousGarantie() => "{$atelier} a diagnostiqué « {$vetement} » : la réparation est prise en charge sous garantie, sans frais.",
                $this->devis?->statut === StatutDevis::PROPOSE => "{$atelier} a diagnostiqué « {$vetement} » et vous propose un devis de {$euros($this->devis->main_oeuvre)}. Acceptez-le pour lancer la réparation.",
                default => "{$atelier} a bien reçu « {$vetement} ».",
            },
            StatutDemande::REFUSEE => "{$atelier} a refusé votre demande de réparation pour « {$vetement} » : {$this->motif_refus}",
            StatutDemande::PRET => $this->estRetourSousGarantie()
                ? "« {$vetement} » est réparé sous garantie, sans frais. Vous pouvez le récupérer chez {$atelier}."
                : "« {$vetement} » est réparé ! Facture de {$euros($this->cout_final)} à régler lors de la récupération chez {$atelier}.",
            StatutDemande::RECUPERE => ($garantie = $this->garantieAccordee())
                ? "Réparation de « {$vetement} » terminée, garantie jusqu'au {$garantie->date_fin->format('d/m/Y')}. Donnez votre avis sur {$atelier} !"
                : "Réparation de « {$vetement} » terminée. Donnez votre avis sur {$atelier} !",
            default => "Votre demande de réparation pour « {$vetement} » est maintenant : {$this->statut->label()}.",
        };
    }
}
