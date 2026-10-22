<?php

namespace App\Http\Controllers;

use App\Enums\StatutDemande;
use App\Enums\StatutDon;
use App\Enums\StatutStructure;
use App\Models\Ateliers\Atelier;
use App\Models\Dons\Association;
use App\Models\Dons\DemandeDon;
use App\Models\Dons\Evenement;
use App\Models\Reparations\DemandeReparation;
use App\Models\Transformations\DemandeTransformation;
use App\Models\User;
use App\Models\Vetements\Vetement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Supervision (DevOps) : métriques de l'application au format Prometheus, lues chaque minute par
 * Grafana Cloud (intégration « Metrics Endpoint »). Protégée par le jeton METRICS_TOKEN, envoyé en
 * « Bearer » ou comme mot de passe « Basic » ; sans jeton configuré, la page n'existe pas (404).
 */
class MetriquesController extends Controller
{
    /** @var list<string> Lignes de la réponse, au format texte de Prometheus. */
    private array $lignes = [];

    public function __invoke(Request $request): Response
    {
        $jeton = (string) config('services.metriques.token');
        abort_if($jeton === '', 404);

        $recu = (string) ($request->bearerToken() ?? $request->getPassword());
        if (! hash_equals($jeton, $recu)) {
            return response('Jeton de supervision invalide.', 401, ['WWW-Authenticate' => 'Bearer']);
        }

        $debut = microtime(true);
        DB::select('select 1');
        $this->metrique('textilecycle_base_donnees_latence_secondes', 'Temps de réponse de la base de données.', [[[], round(microtime(true) - $debut, 4)]]);

        $this->metrique('textilecycle_info', 'Informations sur l’application déployée.', [[[
            'env' => app()->environment(), 'laravel' => app()->version(), 'php' => PHP_VERSION,
        ], 1]]);

        $this->metrique('textilecycle_utilisateurs', 'Comptes par rôle.',
            $this->parValeur(User::class, 'role', array_keys(User::ROLES), 'role'));

        // Module 1 — Vêtements
        $impact = Vetement::impactEconomise(Vetement::query());
        $this->metrique('textilecycle_vetements', 'Vêtements enregistrés dans les dressings.', [[[], Vetement::count()]]);
        $this->metrique('textilecycle_vetements_sauves', 'Vêtements réparés, transformés ou donnés.', [[[], $impact['vetements']]]);
        $this->metrique('textilecycle_co2_evite_kg', 'CO₂ de fabrication évité (estimation, kg).', [[[], $impact['co2']]]);
        $this->metrique('textilecycle_eau_evitee_litres', 'Eau de fabrication évitée (estimation, litres).', [[[], $impact['eau']]]);

        // Modules 2, 3, 4 — Demandes par statut
        $statutsDemande = array_map(fn ($s) => $s->value, StatutDemande::cases());
        $this->metrique('textilecycle_demandes', 'Demandes par module et par statut.', [
            ...$this->parValeur(DemandeReparation::class, 'statut', $statutsDemande, 'statut', ['module' => 'reparation']),
            ...$this->parValeur(DemandeTransformation::class, 'statut', $statutsDemande, 'statut', ['module' => 'transformation']),
            ...$this->parValeur(DemandeDon::class, 'statut', array_map(fn ($s) => $s->value, StatutDon::cases()), 'statut', ['module' => 'don']),
        ]);
        $this->metrique('textilecycle_reparations_chiffre_euros', 'Montant des réparations récupérées (euros).',
            [[[], round((float) DemandeReparation::where('statut', StatutDemande::RECUPERE)->sum('cout_final'), 2)]]);

        // Module 5 — Ateliers, et structures solidaires
        $statutsStructure = array_map(fn ($s) => $s->value, StatutStructure::cases());
        $this->metrique('textilecycle_structures', 'Ateliers et associations par statut.', [
            ...$this->parValeur(Atelier::class, 'statut', $statutsStructure, 'statut', ['type' => 'atelier']),
            ...$this->parValeur(Association::class, 'statut', $statutsStructure, 'statut', ['type' => 'association']),
        ]);
        $this->metrique('textilecycle_evenements', 'Événements solidaires enregistrés.', [[[], Evenement::count()]]);

        return response(implode("\n", $this->lignes)."\n", 200, ['Content-Type' => 'text/plain; version=0.0.4; charset=utf-8']);
    }

    /**
     * Nombre de lignes du modèle pour chaque valeur d'une colonne (valeurs absentes = 0).
     *
     * @param  class-string<Model>  $modele
     * @param  list<string>  $valeurs
     * @return list<array{0: array<string, string>, 1: int}>
     */
    private function parValeur(string $modele, string $colonne, array $valeurs, string $label, array $labels = []): array
    {
        $comptes = $modele::query()->toBase()->selectRaw("{$colonne} as valeur, count(*) as n")->groupBy($colonne)->pluck('n', 'valeur');

        return array_map(fn ($valeur) => [$labels + [$label => $valeur], (int) ($comptes[$valeur] ?? 0)], $valeurs);
    }

    /** @param  list<array{0: array<string, string>, 1: int|float}>  $series */
    private function metrique(string $nom, string $aide, array $series): void
    {
        $this->lignes[] = "# HELP {$nom} {$aide}";
        $this->lignes[] = "# TYPE {$nom} gauge";
        foreach ($series as [$labels, $valeur]) {
            $texte = collect($labels)->map(fn ($v, $k) => $k.'="'.addcslashes((string) $v, "\\\"\n").'"')->join(',');
            $this->lignes[] = $nom.($texte ? "{{$texte}}" : '').' '.$valeur;
        }
    }
}
