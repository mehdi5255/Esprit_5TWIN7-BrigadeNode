<?php

namespace App\Http\Controllers;

use App\Enums\StatutDemande;
use App\Enums\StatutDon;
use App\Models\Association;
use App\Models\Atelier;
use App\Models\BesoinAssociation;
use App\Models\DemandeDon;
use App\Models\DemandeReparation;
use App\Models\DemandeTransformation;
use App\Support\Modules;

class HomeController extends Controller
{
    public function __invoke()
    {
        $semaine = now()->subDays(7);

        // Un vêtement est « sauvé » quand son parcours est terminé avec succès : [requête, colonne de date de fin].
        $termines = collect(Modules::filtrer([
            'reparations' => fn () => [DemandeReparation::where('statut', StatutDemande::RECUPERE), 'date_recuperation'],
            'transformations' => fn () => [DemandeTransformation::where('statut', StatutDemande::RECUPERE), 'date_recuperation'],
            'dons' => fn () => [DemandeDon::where('statut', StatutDon::REMIS_BENEFICIAIRE), 'date_remise'],
        ]))->map(fn ($requete) => $requete());

        $stats = [
            'vetements_sauves' => $termines->sum(fn ($t) => (clone $t[0])->count()),
            'partenaires' => (Modules::actif('ateliers') ? Atelier::actifs()->count() : 0)
                + (Modules::actif('associations') ? Association::actifs()->count() : 0),
            'cette_semaine' => $termines->sum(fn ($t) => (clone $t[0])->where($t[1], '>=', $semaine)->count()),
            'beneficiaires' => Modules::actif('dons') ? (int) DemandeDon::where('statut', StatutDon::REMIS_BENEFICIAIRE)->sum('nombre_beneficiaires') : 0,
        ];

        return view('frontoffice.home', [
            'stats' => $stats,
            'ateliers' => Modules::actif('ateliers') && Modules::actif('avis')
                ? Atelier::actifs()->avecNote()
                    ->with(['services' => fn ($q) => $q->where('actif', true)])
                    ->reorder()->orderByDesc('note_moyenne')->orderByDesc('nombre_avis')
                    ->limit(3)->get()
                : collect(),
            'besoins' => Modules::actif('dons')
                ? BesoinAssociation::where('actif', true)
                    ->whereHas('association', fn ($q) => $q->actifs())
                    ->with(['association', 'categorie'])
                    ->orderByRaw("case priorite when 'urgente' then 1 when 'haute' then 2 when 'moyenne' then 3 else 4 end")
                    ->latest()
                    ->limit(3)->get()
                : collect(),
        ]);
    }
}
