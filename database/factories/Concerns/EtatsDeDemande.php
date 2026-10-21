<?php

namespace Database\Factories\Concerns;

use App\Enums\StatutVetement;
use App\Models\Vetements\Vetement;
use Illuminate\Database\Eloquent\Model;

/**
 * États du workflow partagés par les factories de demandes (réparation, transformation, don).
 * Chaque état remplit les dates cohérentes avec l'étape atteinte, et le statut du vêtement
 * est synchronisé après création, comme le ferait le workflow réel.
 */
trait EtatsDeDemande
{
    /** Le demandeur est toujours le propriétaire du vêtement. */
    protected function proprietaireDuVetement(): \Closure
    {
        return fn (array $attributes) => Vetement::find($attributes['vetement_id'])->user_id;
    }

    public function acceptee(): static
    {
        return $this->state(fn () => ['statut' => 'acceptee', 'date_acceptation' => fake()->dateTimeBetween('-12 days', '-1 day')]);
    }

    public function refusee(): static
    {
        return $this->state(fn () => ['statut' => 'refusee', 'motif_refus' => fake()->sentence()]);
    }

    public function annulee(): static
    {
        return $this->state(['statut' => 'annulee']);
    }

    public function depotRemis(): static
    {
        return $this->state(fn () => [
            'statut' => 'depot_remis',
            'date_acceptation' => fake()->dateTimeBetween('-25 days', '-21 days'),
            'depot_remis' => true,
            'date_depot_effectif' => fake()->dateTimeBetween('-20 days', '-10 days'),
        ]);
    }

    public function enTraitement(): static
    {
        return $this->depotRemis()->state(fn () => [
            'statut' => 'en_traitement',
            'date_debut_traitement' => fake()->dateTimeBetween('-9 days', '-5 days'),
        ]);
    }

    /** Statut du vêtement pendant la demande (EN_REPARATION, EN_TRANSFORMATION ou EN_DON). */
    abstract protected function statutVetementPendant(): StatutVetement;

    /** Statut du vêtement une fois la demande terminée avec succès. */
    protected function statutVetementFinal(): StatutVetement
    {
        return StatutVetement::DISPONIBLE;
    }

    /** À appeler dans configure() : aligne le statut du vêtement sur celui de la demande. */
    protected function synchroniserVetement(Model $demande, array $statutsFinaux): void
    {
        $statut = match (true) {
            in_array($demande->statut->value, ['refusee', 'annulee'], true) => StatutVetement::DISPONIBLE,
            in_array($demande->statut->value, $statutsFinaux, true) => $this->statutVetementFinal(),
            default => $this->statutVetementPendant(),
        };

        $demande->vetement->update(['statut' => $statut]);
    }
}
