<?php

namespace Database\Factories;

use App\Enums\StatutVetement;
use App\Enums\TypeReparation;
use App\Enums\TypeService;
use App\Models\Ateliers\Atelier;
use App\Models\Reparations\DemandeReparation;
use App\Models\Reparations\Devis;
use App\Models\Reparations\Facture;
use App\Models\Reparations\Garantie;
use App\Models\Reparations\Reparation;
use App\Models\Vetements\Vetement;
use Database\Factories\Concerns\EtatsDeDemande;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DemandeReparation>
 */
class DemandeReparationFactory extends Factory
{
    protected $model = DemandeReparation::class;

    use EtatsDeDemande;

    public function definition(): array
    {
        return [
            'vetement_id' => Vetement::factory(),
            'particulier_id' => $this->proprietaireDuVetement(),
            // Un atelier actif qui propose la réparation, ou un nouvel atelier.
            'atelier_id' => fn () => Atelier::actifs()->proposant(TypeService::REPARATION)->reorder()->inRandomOrder()->value('id') ?? Atelier::factory(),
            'type_reparation' => fake()->randomElement(TypeReparation::cases()),
            'description' => fake()->randomElement(['Couture décousue sur le côté.', 'Fermeture éclair bloquée.', 'Petit trou au coude.', 'Bouton manquant.', 'Ourlet à reprendre.']),
            'budget_max' => fake()->optional(0.7)->randomElement([10, 15, 20, 30, 40]),
            'date_souhaitee' => fake()->optional()->dateTimeBetween('+3 days', '+1 month'),
            'statut' => 'en_attente',
        ];
    }

    public function prete(): static
    {
        return $this->enTraitement()->state(fn (array $attributes) => [
            'statut' => 'pret',
            'date_fin_traitement' => fake()->dateTimeBetween('-4 days', '-2 days'),
            'cout_final' => $attributes['budget_max'] ?? fake()->randomElement([10, 15, 20, 25]),
        ]);
    }

    public function recuperee(): static
    {
        return $this->prete()->state(fn () => [
            'statut' => 'recupere',
            'date_recuperation' => fake()->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (DemandeReparation $demande) {
            $this->synchroniserVetement($demande, ['recupere']);
            $statut = $demande->statut->value;

            // Une réparation démarrée a sa fiche technique.
            if (in_array($statut, ['en_traitement', 'pret', 'recupere'], true)) {
                Reparation::factory()->for($demande, 'demande')->create(
                    $statut === 'en_traitement' ? ['actions_realisees' => null, 'duree_reelle' => null] : []
                );
            }

            // Le devis s'établit au dépôt, après diagnostic ; dès le traitement, il est forcément accepté.
            if (in_array($statut, ['en_traitement', 'pret', 'recupere'], true)) {
                $montant = $demande->cout_final ?? $demande->budget_max ?? fake()->randomElement([12, 15, 18, 22]);
                $devis = Devis::factory()->for($demande, 'demande')
                    ->state(['main_oeuvre' => $montant, 'valide_jusqu_au' => ($demande->date_acceptation ?? now())->copy()->addDays(Devis::VALIDITE_JOURS)])
                    ->accepte()
                    ->create();
            }

            // Terminée : facture émise au montant du devis ; récupérée : facture payée + garantie.
            if (in_array($statut, ['pret', 'recupere'], true)) {
                $facture = Facture::factory()->for($demande, 'demande')
                    ->when($statut === 'recupere', fn ($f) => $f->payee())
                    ->create([
                        'montant_devis' => $devis->main_oeuvre,
                        'supplement' => 0,
                        'motif_supplement' => null,
                        'montant_total' => $demande->cout_final ?? $devis->main_oeuvre,
                        'date_emission' => $demande->date_fin_traitement ?? now(),
                    ]);

                if ($statut === 'recupere') {
                    $debut = $demande->date_recuperation ?? now();
                    Garantie::factory()->for($facture)->create([
                        'date_debut' => $debut,
                        'date_fin' => $debut->copy()->addMonths(Garantie::DUREE_MOIS),
                    ]);
                }
            }
        });
    }

    protected function statutVetementPendant(): StatutVetement
    {
        return StatutVetement::EN_REPARATION;
    }
}
