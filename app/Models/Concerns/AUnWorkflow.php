<?php

namespace App\Models\Concerns;

use App\Enums\StatutVetement;
use App\Models\Transformations\Review;
use App\Models\User;
use App\Models\Vetements\Vetement;
use App\Notifications\DemandeMiseAJour;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;

/**
 * Workflow commun aux demandes (réparation, transformation, don) :
 * EN_ATTENTE → ACCEPTEE | REFUSEE → DEPOT_REMIS → EN_TRAITEMENT → …
 *
 * Chaque transition vérifie le statut courant, met à jour la demande et
 * synchronise le statut du vêtement dans une même transaction.
 *
 * La classe utilisatrice définit :
 *  - const PREFIXE (ex. 'REP') et const LIBELLE (ex. 'Réparation')
 *  - structure(): BelongsTo  (atelier ou association)
 *  - statutVetementPendant(): StatutVetement
 *  - messageNotification(): string
 */
trait AUnWorkflow
{
    public function vetement(): BelongsTo
    {
        return $this->belongsTo(Vetement::class);
    }

    public function particulier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'particulier_id');
    }

    public function review(): MorphOne
    {
        return $this->morphOne(Review::class, 'demande');
    }

    public function reference(): string
    {
        return '#'.static::PREFIXE.'-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /** Structure (Atelier ou Association) qui traite la demande. */
    public function structureModel(): Model
    {
        return $this->structure()->getResults();
    }

    /** Particulier crée la demande → EN_ATTENTE, vêtement bloqué + notification à la structure. */
    public static function creer(Vetement $vetement, array $data): static
    {
        if (! $vetement->estDisponible()) {
            throw new DomainException("Ce vêtement n'est pas disponible pour une nouvelle demande.");
        }

        $demande = DB::transaction(function () use ($vetement, $data) {
            $demande = static::create($data + ['vetement_id' => $vetement->id, 'particulier_id' => $vetement->user_id]);
            $vetement->update(['statut' => (new static)->statutVetementPendant()]);

            return $demande;
        });

        $demande->structureModel()->responsable?->notify(new DemandeMiseAJour($demande, pourStructure: true));

        return $demande;
    }

    public function accepter(): void
    {
        $e = $this->statut::class;
        $this->transition([$e::EN_ATTENTE], $e::ACCEPTEE, ['date_acceptation' => now()], notifier: true);
    }

    public function refuser(string $motif): void
    {
        $e = $this->statut::class;
        $this->transition([$e::EN_ATTENTE], $e::REFUSEE, ['motif_refus' => $motif], StatutVetement::DISPONIBLE, notifier: true);
    }

    public function confirmerDepot(): void
    {
        $e = $this->statut::class;
        $this->transition([$e::ACCEPTEE], $e::DEPOT_REMIS, ['date_depot_effectif' => now(), 'depot_remis' => true]);
    }

    /** Le particulier peut annuler tant que la demande n'a pas été acceptée. */
    public function annuler(): void
    {
        $e = $this->statut::class;
        $this->transition([$e::EN_ATTENTE], $e::ANNULEE, vetement: StatutVetement::DISPONIBLE);
    }

    public function peutEtreAnnulee(): bool
    {
        return $this->statut === ($this->statut::class)::EN_ATTENTE;
    }

    /** Le particulier modifie sa demande tant que la structure ne l'a pas traitée. */
    public function peutEtreModifiee(): bool
    {
        return $this->peutEtreAnnulee();
    }

    /** Suppression possible si la demande n'a jamais été engagée (en attente, annulée ou refusée). */
    public function peutEtreSupprimee(): bool
    {
        $e = $this->statut::class;

        return in_array($this->statut, [$e::EN_ATTENTE, $e::ANNULEE, $e::REFUSEE], true);
    }

    public function modifier(array $data): void
    {
        if (! $this->peutEtreModifiee()) {
            throw new DomainException('Cette demande a déjà été prise en charge : elle ne peut plus être modifiée.');
        }

        $this->update($data);
    }

    /** Supprime la demande ; si elle était en attente, le vêtement redevient disponible. */
    public function supprimer(): void
    {
        if (! $this->peutEtreSupprimee()) {
            throw new DomainException('Une demande en cours ou terminée ne peut pas être supprimée.');
        }

        DB::transaction(function () {
            if ($this->statut === ($this->statut::class)::EN_ATTENTE) {
                $this->vetement->update(['statut' => StatutVetement::DISPONIBLE]);
            }
            $this->delete();
        });
    }

    /** Un avis est possible une fois le parcours terminé avec succès, une seule fois. */
    public function peutEtreNotee(): bool
    {
        return $this->statut === $this->statutFinal() && ! $this->review()->exists();
    }

    protected function transition(array $depuis, $vers, array $champs = [], ?StatutVetement $vetement = null, bool $notifier = false): void
    {
        if (! in_array($this->statut, $depuis, true)) {
            throw new DomainException("Action impossible : la demande est « {$this->statut->label()} ».");
        }

        DB::transaction(function () use ($vers, $champs, $vetement) {
            $this->forceFill($champs + ['statut' => $vers])->save();

            if ($vetement) {
                $this->vetement->update(['statut' => $vetement]);
            }
        });

        if ($notifier) {
            $this->particulier->notify(new DemandeMiseAJour($this));
        }
    }
}
