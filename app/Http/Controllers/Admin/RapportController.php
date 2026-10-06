<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\Atelier;
use App\Models\DemandeDon;
use App\Models\DemandeReparation;
use App\Models\DemandeTransformation;
use App\Models\User;
use App\Support\Modules;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Rapport d'activité sur une période, consultable et exportable en CSV. */
class RapportController extends Controller
{
    /** module => [libellé, modèle] */
    private const TYPES = [
        'reparations' => ['Réparation', DemandeReparation::class],
        'transformations' => ['Transformation', DemandeTransformation::class],
        'dons' => ['Don', DemandeDon::class],
    ];

    public function index(Request $request)
    {
        [$du, $au] = $this->periode($request);
        $demandes = $this->demandes($du, $au);

        $parType = collect($this->types())->map(fn ($classe, $libelle) => [
            'total' => $demandes->where('type', $libelle)->count(),
            'terminees' => $demandes->where('type', $libelle)->where('terminee', true)->count(),
            'refusees' => $demandes->where('type', $libelle)->whereIn('statut', ['refusee'])->count(),
            'annulees' => $demandes->where('type', $libelle)->whereIn('statut', ['annulee'])->count(),
        ]);

        // Demandes terminées par atelier, pour les modules présents.
        $termineesAtelier = Modules::filtrer([
            'reparations' => ['demandesReparation as terminees_rep' => fn ($q) => $q->where('statut', 'recupere')->whereBetween('date_recuperation', [$du, $au])],
            'transformations' => ['demandesTransformation as terminees_tra' => fn ($q) => $q->where('statut', 'recupere')->whereBetween('date_recuperation', [$du, $au])],
        ]);

        return view('admin.rapports.index', [
            'du' => $du,
            'au' => $au,
            'parType' => $parType,
            'resume' => [
                'demandes' => $demandes->count(),
                'terminees' => $demandes->where('terminee', true)->count(),
                'chiffre' => $demandes->where('terminee', true)->sum('cout'),
                'beneficiaires' => Modules::actif('dons') ? (int) DemandeDon::where('statut', 'remis_beneficiaire')->whereBetween('date_remise', [$du, $au])->sum('nombre_beneficiaires') : 0,
                'inscriptions' => User::whereBetween('created_at', [$du, $au])->count(),
            ],
            'topAteliers' => Modules::actif('ateliers') && $termineesAtelier
                ? Atelier::withCount(array_merge(...array_values($termineesAtelier)))
                    ->get()->sortByDesc(fn ($a) => $a->terminees_rep + $a->terminees_tra)->take(5)->filter(fn ($a) => $a->terminees_rep + $a->terminees_tra > 0)
                : collect(),
            'topAssociations' => Modules::actif('associations') && Modules::actif('dons')
                ? Association::withCount(['demandesDon as remis' => fn ($q) => $q->where('statut', 'remis_beneficiaire')->whereBetween('date_remise', [$du, $au])])
                    ->get()->sortByDesc('remis')->take(5)->filter(fn ($a) => $a->remis > 0)
                : collect(),
        ]);
    }

    /** Types de demandes des modules présents : libellé => modèle. */
    private function types(): array
    {
        return collect(Modules::filtrer(self::TYPES))->mapWithKeys(fn ($type) => [$type[0] => $type[1]])->all();
    }

    public function export(Request $request)
    {
        [$du, $au] = $this->periode($request);
        $demandes = $this->demandes($du, $au);
        $fichier = 'rapport-textilecycle-'.$du->format('Y-m-d').'-'.$au->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($demandes) {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\xEF\xBB\xBF"); // BOM pour Excel
            fputcsv($sortie, ['Type', 'Référence', 'Date', 'Particulier', 'Structure', 'Vêtement', 'Statut', 'Coût final (€)'], ';');
            foreach ($demandes as $d) {
                fputcsv($sortie, [$d['type'], $d['reference'], $d['date'], $d['particulier'], $d['structure'], $d['vetement'], $d['statut_label'], $d['cout'] !== null ? number_format($d['cout'], 2, ',', '') : ''], ';');
            }
            fclose($sortie);
        }, $fichier, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function periode(Request $request): array
    {
        $request->validate(['du' => ['nullable', 'date'], 'au' => ['nullable', 'date', 'after_or_equal:du']]);

        return [
            $request->date('du')?->startOfDay() ?? now()->startOfMonth(),
            $request->date('au')?->endOfDay() ?? now()->endOfDay(),
        ];
    }

    /** Toutes les demandes créées sur la période, aplanies pour le rapport et l'export. */
    private function demandes(Carbon $du, Carbon $au): Collection
    {
        return collect($this->types())->flatMap(fn ($classe, $libelle) => $classe::with(['particulier', 'vetement', 'structure'])
            ->whereBetween('created_at', [$du, $au])->get()
            ->map(fn ($d) => [
                'type' => $libelle,
                'reference' => $d->reference(),
                'cree_le' => $d->created_at,
                'date' => $d->created_at->format('d/m/Y'),
                'particulier' => $d->particulier->name,
                'structure' => $d->structureModel()->nom,
                'vetement' => $d->vetement->nom,
                'statut' => $d->statut->value,
                'statut_label' => $d->statut->label(),
                'terminee' => $d->statut === $d->statutFinal(),
                'cout' => isset($d->cout_final) ? (float) $d->cout_final : null,
            ]))->sortBy('cree_le')->values();
    }
}
