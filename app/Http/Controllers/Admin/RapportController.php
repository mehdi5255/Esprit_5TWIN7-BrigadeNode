<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ateliers\Atelier;
use App\Models\Dons\Association;
use App\Models\Dons\DemandeDon;
use App\Models\Reparations\DemandeReparation;
use App\Models\Transformations\DemandeTransformation;
use App\Models\User;
use App\Models\Vetements\Vetement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Rapport d'activité sur une période : synthèse comparée, graphiques, classements ; exportable en CSV. */
class RapportController extends Controller
{
    private const TYPES = [
        'Réparation' => DemandeReparation::class,
        'Transformation' => DemandeTransformation::class,
        'Don' => DemandeDon::class,
    ];

    /** Raccourcis de période proposés en haut du rapport. */
    public const RACCOURCIS = ['7j' => '7 derniers jours', 'mois' => 'Ce mois', '3mois' => '3 derniers mois', 'annee' => 'Cette année'];

    public function index(Request $request)
    {
        [$du, $au] = $this->periode($request);
        $demandes = $this->demandes($du, $au);

        // Même durée juste avant : base de comparaison des indicateurs.
        $jours = $du->diffInDays($au) + 1;
        [$avantDu, $avantAu] = [$du->copy()->subDays($jours), $du->copy()->subSecond()];

        $parType = collect(self::TYPES)->map(fn ($classe, $libelle) => [
            'total' => $demandes->where('type', $libelle)->count(),
            'terminees' => $demandes->where('type', $libelle)->where('terminee', true)->count(),
            'en_cours' => $demandes->where('type', $libelle)->where('terminee', false)->whereNotIn('statut', ['refusee', 'annulee'])->count(),
            'refusees' => $demandes->where('type', $libelle)->where('statut', 'refusee')->count(),
            'annulees' => $demandes->where('type', $libelle)->where('statut', 'annulee')->count(),
            'chiffre' => $demandes->where('type', $libelle)->where('terminee', true)->sum('cout'),
        ]);

        // Impact écologique depuis le lancement : vêtements sauvés et kilos sauvés par matière.
        $sauves = Vetement::sauves()->with('matieres')->get();
        $matieres = $sauves->flatMap(fn (Vetement $v) => $v->matieres->map(fn ($m) => ['nom' => $m->nom, 'kg' => ($v->poids_grammes ?? 0) / 1000 * $m->pivot->pourcentage / 100]))
            ->groupBy('nom')->map(fn ($lignes) => round($lignes->sum('kg'), 1))->sortDesc()->take(6);

        $topAteliers = Atelier::withCount([
            'demandesReparation as terminees_rep' => fn ($q) => $q->where('statut', 'recupere')->whereBetween('date_recuperation', [$du, $au]),
            'demandesTransformation as terminees_tra' => fn ($q) => $q->where('statut', 'recupere')->whereBetween('date_recuperation', [$du, $au]),
        ])->get()->sortByDesc(fn ($a) => $a->terminees_rep + $a->terminees_tra)->take(5)->filter(fn ($a) => $a->terminees_rep + $a->terminees_tra > 0)->values();
        $topAssociations = Association::withCount(['demandesDon as remis' => fn ($q) => $q->where('statut', 'remis_beneficiaire')->whereBetween('date_remise', [$du, $au])])
            ->get()->sortByDesc('remis')->take(5)->filter(fn ($a) => $a->remis > 0)->values();

        return view('back.commun.rapports.index', [
            'du' => $du,
            'au' => $au,
            'raccourci' => $request->input('periode'),
            'resume' => $this->resume($du, $au, $demandes),
            'precedent' => $this->resume($avantDu, $avantAu, $this->demandes($avantDu, $avantAu)),
            'avantDu' => $avantDu,
            'avantAu' => $avantAu,
            'parType' => $parType,
            'impact' => Vetement::impactEconomise(Vetement::query()),
            'matieresSauvees' => $matieres,
            'topAteliers' => $topAteliers,
            'topAssociations' => $topAssociations,
            'graphiques' => $this->graphiques($du, $au, $demandes, $parType, $matieres, $topAteliers, $topAssociations),
        ]);
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

    /** Indicateurs clés d'une période. */
    private function resume(Carbon $du, Carbon $au, Collection $demandes): array
    {
        $terminees = $demandes->where('terminee', true);

        return [
            'demandes' => $demandes->count(),
            'terminees' => $terminees->count(),
            'taux' => $demandes->count() ? (int) round($terminees->count() / $demandes->count() * 100) : 0,
            'chiffre' => (float) $terminees->sum('cout'),
            'beneficiaires' => (int) DemandeDon::where('statut', 'remis_beneficiaire')->whereBetween('date_remise', [$du, $au])->sum('nombre_beneficiaires'),
            'inscriptions' => User::whereBetween('created_at', [$du, $au])->count(),
        ];
    }

    /**
     * Données des graphiques. L'axe du temps s'adapte à la durée : par jour jusqu'à un mois,
     * par semaine jusqu'à quatre mois, par mois au-delà.
     */
    private function graphiques(Carbon $du, Carbon $au, Collection $demandes, Collection $parType, Collection $matieres, Collection $topAteliers, Collection $topAssociations): array
    {
        $jours = $du->diffInDays($au) + 1;
        [$unite, $format] = match (true) {
            $jours <= 31 => ['day', 'd/m'],
            $jours <= 120 => ['week', '\s\e\m. d/m'],
            default => ['month', 'M Y'],
        };
        $debutDe = fn (Carbon $date) => match ($unite) {
            'day' => $date->copy()->startOfDay(),
            'week' => $date->copy()->startOfWeek(),
            'month' => $date->copy()->startOfMonth(),
        };

        // Toutes les tranches de la période, même vides, pour une courbe continue.
        $tranches = collect();
        for ($t = $debutDe($du); $t->lte($au); $t = $t->copy()->add(1, $unite)) {
            $tranches->put($t->format('Y-m-d'), $t->translatedFormat($format));
        }
        $compter = function (Collection $elements, callable $date, ?callable $valeur = null) use ($tranches, $debutDe) {
            $groupes = $elements->groupBy(fn ($e) => $debutDe($date($e))->format('Y-m-d'));

            return $tranches->keys()->map(fn ($cle) => $valeur
                ? round(($groupes->get($cle) ?? collect())->sum($valeur), 2)
                : ($groupes->get($cle) ?? collect())->count())->all();
        };

        $inscriptions = User::whereBetween('created_at', [$du, $au])->get(['created_at']);

        return [
            'unite' => ['day' => 'jour', 'week' => 'semaine', 'month' => 'mois'][$unite],
            'labels' => $tranches->values()->all(),
            'evolution' => collect(self::TYPES)->keys()->mapWithKeys(fn ($type) => [$type => $compter($demandes->where('type', $type), fn ($d) => $d['cree_le'])])->all(),
            'inscriptions' => $compter($inscriptions, fn ($u) => $u->created_at),
            'chiffre' => $compter($demandes->where('terminee', true)->whereNotNull('cout'), fn ($d) => $d['cree_le'], fn ($d) => $d['cout']),
            'types' => ['labels' => $parType->keys()->all(), 'valeurs' => $parType->pluck('total')->all()],
            'statuts' => [
                'labels' => $parType->keys()->all(),
                'Terminées' => $parType->pluck('terminees')->all(),
                'En cours' => $parType->pluck('en_cours')->all(),
                'Refusées' => $parType->pluck('refusees')->all(),
                'Annulées' => $parType->pluck('annulees')->all(),
            ],
            'matieres' => ['labels' => $matieres->keys()->all(), 'valeurs' => $matieres->values()->all()],
            'ateliers' => ['labels' => $topAteliers->pluck('nom')->all(), 'valeurs' => $topAteliers->map(fn ($a) => $a->terminees_rep + $a->terminees_tra)->all()],
            'associations' => ['labels' => $topAssociations->pluck('nom')->all(), 'valeurs' => $topAssociations->pluck('remis')->all()],
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function periode(Request $request): array
    {
        $request->validate([
            'du' => ['nullable', 'date'],
            'au' => ['nullable', 'date', 'after_or_equal:du'],
            'periode' => ['nullable', 'in:'.implode(',', array_keys(self::RACCOURCIS))],
        ]);

        return match ($request->input('periode')) {
            '7j' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'mois' => [now()->startOfMonth(), now()->endOfDay()],
            '3mois' => [now()->subMonths(3)->addDay()->startOfDay(), now()->endOfDay()],
            'annee' => [now()->startOfYear(), now()->endOfDay()],
            default => [
                $request->date('du')?->startOfDay() ?? now()->startOfMonth(),
                $request->date('au')?->endOfDay() ?? now()->endOfDay(),
            ],
        };
    }

    /** Toutes les demandes créées sur la période, aplanies pour le rapport et l'export. */
    private function demandes(Carbon $du, Carbon $au): Collection
    {
        return collect(self::TYPES)->flatMap(fn ($classe, $libelle) => $classe::with(['particulier', 'vetement', 'structure'])
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
