<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatutCompte;
use App\Enums\StatutStructure;
use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\Atelier;
use App\Models\DemandeDon;
use App\Models\DemandeReparation;
use App\Models\DemandeTransformation;
use App\Models\Review;
use App\Models\User;
use App\Models\Vetement;
use App\Support\Modules;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        // Types de demandes des modules présents : module => [modèle, statut final].
        $types = collect(Modules::filtrer([
            'reparations' => [DemandeReparation::class, 'recupere'],
            'transformations' => [DemandeTransformation::class, 'recupere'],
            'dons' => [DemandeDon::class, 'remis_beneficiaire'],
        ]));

        // Demandes créées par mois, sur les 6 derniers mois.
        $mois = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $activite = $mois->map(fn (Carbon $debut) => [
            'label' => ucfirst($debut->translatedFormat('M')),
            'total' => $types->sum(fn ($type) => $type[0]::whereBetween('created_at', [$debut, $debut->copy()->endOfMonth()])->count()),
        ]);

        // Les trois libellés sont toujours présents (0 si le module n'est pas encore fusionné).
        $repartition = collect(['reparations' => 'Réparations', 'transformations' => 'Transformations', 'dons' => 'Dons'])
            ->mapWithKeys(fn ($libelle, $module) => [$libelle => isset($types[$module]) ? $types[$module][0]::count() : 0]);

        $recentes = $types
            ->flatMap(fn ($type) => $type[0]::with(['particulier', 'vetement', 'structure'])->latest()->limit(5)->get())
            ->sortByDesc('created_at')->take(8)->values();

        return view('admin.dashboard', [
            'stats' => [
                'utilisateurs' => User::count(),
                'comptes_en_attente' => User::where('statut', StatutCompte::EN_ATTENTE)->count(),
                'vetements' => Modules::actif('vetements') ? Vetement::count() : 0,
                'demandes_actives' => $types->sum(fn ($type) => $type[0]::whereNotIn('statut', ['refusee', $type[1], 'annulee'])->count()),
                'partenaires' => (Modules::actif('ateliers') ? Atelier::where('statut', StatutStructure::ACTIF)->count() : 0)
                    + (Modules::actif('associations') ? Association::where('statut', StatutStructure::ACTIF)->count() : 0),
                'vetements_sauves' => $types->sum(fn ($type) => $type[0]::where('statut', $type[1])->count()),
                'note_moyenne' => Modules::actif('avis') && ($note = Review::publies()->avg('note')) ? round($note, 1) : null,
            ],
            'enAttente' => User::where('statut', StatutCompte::EN_ATTENTE)->with(User::relationsStructure())->latest()->get(),
            'activite' => $activite,
            'repartition' => $repartition,
            'recentes' => $recentes,
        ]);
    }
}
