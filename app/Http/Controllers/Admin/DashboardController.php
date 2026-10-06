<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatutCompte;
use App\Enums\StatutStructure;
use App\Http\Controllers\Controller;
use App\Models\Ateliers\Atelier;
use App\Models\Dons\Association;
use App\Models\Dons\DemandeDon;
use App\Models\Reparations\DemandeReparation;
use App\Models\Transformations\DemandeTransformation;
use App\Models\Transformations\Review;
use App\Models\User;
use App\Models\Vetements\Vetement;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $types = ['Réparations' => DemandeReparation::class, 'Transformations' => DemandeTransformation::class, 'Dons' => DemandeDon::class];

        // Demandes créées par mois, sur les 6 derniers mois.
        $mois = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $activite = $mois->map(fn (Carbon $debut) => [
            'label' => ucfirst($debut->translatedFormat('M')),
            'total' => collect($types)->sum(fn ($classe) => $classe::whereBetween('created_at', [$debut, $debut->copy()->endOfMonth()])->count()),
        ]);

        $repartition = collect($types)->map(fn ($classe) => $classe::count());

        $recentes = collect($types)
            ->flatMap(fn ($classe) => $classe::with(['particulier', 'vetement', 'structure'])->latest()->limit(5)->get())
            ->sortByDesc('created_at')->take(8)->values();

        return view('back.commun.dashboard', [
            'stats' => [
                'utilisateurs' => User::count(),
                'comptes_en_attente' => User::where('statut', StatutCompte::EN_ATTENTE)->count(),
                'vetements' => Vetement::count(),
                'demandes_actives' => DemandeReparation::whereNotIn('statut', ['refusee', 'recupere', 'annulee'])->count()
                    + DemandeTransformation::whereNotIn('statut', ['refusee', 'recupere', 'annulee'])->count()
                    + DemandeDon::whereNotIn('statut', ['refusee', 'remis_beneficiaire', 'annulee'])->count(),
                'partenaires' => Atelier::where('statut', StatutStructure::ACTIF)->count() + Association::where('statut', StatutStructure::ACTIF)->count(),
                'vetements_sauves' => DemandeReparation::where('statut', 'recupere')->count() + DemandeTransformation::where('statut', 'recupere')->count() + DemandeDon::where('statut', 'remis_beneficiaire')->count(),
                'note_moyenne' => ($note = Review::publies()->avg('note')) ? round($note, 1) : null,
            ],
            'enAttente' => User::where('statut', StatutCompte::EN_ATTENTE)->with(['atelier', 'association'])->latest()->get(),
            'activite' => $activite,
            'repartition' => $repartition,
            'recentes' => $recentes,
        ]);
    }
}
