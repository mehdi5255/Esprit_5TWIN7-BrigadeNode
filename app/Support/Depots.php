<?php

namespace App\Support;

use App\Models\Dons\DemandeDon;
use App\Models\Reparations\DemandeReparation;
use App\Models\Transformations\DemandeTransformation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Suivi des dépôts physiques de vêtements, toutes demandes confondues.
 *
 * Un dépôt est « attendu » quand la demande est acceptée mais que le particulier n'a pas
 * encore apporté le vêtement ; il est « reçu » une fois confirmé par l'atelier ou l'association.
 * L'admin voit tout (lecture seule) ; un atelier ou une association ne voit que ses demandes.
 */
class Depots
{
    /** Au-delà de ce délai après l'acceptation, un dépôt attendu est signalé en retard. */
    public const DELAI_JOURS = 7;

    /** segment d'URL => [modèle, relation vers la structure, libellé] */
    public const TYPES = [
        'reparations' => [DemandeReparation::class, 'atelier', 'Réparation'],
        'transformations' => [DemandeTransformation::class, 'atelier', 'Transformation'],
        'dons' => [DemandeDon::class, 'association', 'Don'],
    ];

    /**
     * @param  array<string, array>  $types  types de demandes suivis
     * @param  \Closure|null  $portee  restreint les demandes à une structure (null = tout, pour l'admin)
     */
    private function __construct(private array $types, private ?\Closure $portee)
    {
    }

    /** Dépôts visibles par l'utilisateur, éventuellement filtrés sur un type (reparations, transformations, dons). */
    public static function pour(User $user, ?string $type = null): self
    {
        [$types, $portee] = match ($user->role) {
            'admin', 'moderateur' => [self::TYPES, null],
            'atelier' => [array_intersect_key(self::TYPES, array_flip(['reparations', 'transformations'])),
                fn (Builder $q) => $q->where('atelier_id', $user->atelier?->id)],
            'association' => [array_intersect_key(self::TYPES, array_flip(['dons'])),
                fn (Builder $q) => $q->where('association_id', $user->association?->id)],
            default => [[], null],
        };

        if ($type && isset($types[$type])) {
            $types = [$type => $types[$type]];
        }

        return new self($types, $portee);
    }

    /** Types proposés dans le filtre (segment => libellé). */
    public function types(): array
    {
        return array_map(fn ($t) => $t[2], $this->types);
    }

    /** Demandes acceptées dont le vêtement n'a pas encore été déposé, les plus anciennes d'abord. */
    public function attendus(): Collection
    {
        return $this->lignes(fn (Builder $q) => self::attendu($q))
            ->sortBy(fn ($ligne) => $ligne['demande']->date_acceptation)
            ->values();
    }

    /** Dépôt attendu : demande acceptée, vêtement pas encore apporté (le devis d'une réparation se fait après le dépôt). */
    private static function attendu(Builder $q): Builder
    {
        return $q->where('statut', 'acceptee');
    }

    /** Dépôts confirmés sur les N derniers jours, les plus récents d'abord. */
    public function recus(int $jours = 30): Collection
    {
        return $this->lignes(fn (Builder $q) => $q->where('depot_remis', true)->where('date_depot_effectif', '>=', now()->subDays($jours)))
            ->sortByDesc(fn ($ligne) => $ligne['demande']->date_depot_effectif)
            ->values();
    }

    public function nombreAttendus(): int
    {
        return $this->compter(fn (Builder $q) => self::attendu($q));
    }

    public function nombreEnRetard(): int
    {
        // En retard = plus de DELAI_JOURS jours entiers depuis l'acceptation (même règle que l'affichage).
        return $this->compter(fn (Builder $q) => self::attendu($q)->where('date_acceptation', '<=', now()->subDays(self::DELAI_JOURS + 1)));
    }

    public function statistiques(): array
    {
        $recus = $this->recus();
        $delais = $recus->pluck('delai')->filter(fn ($d) => $d !== null);

        return [
            'attendus' => $this->nombreAttendus(),
            'en_retard' => $this->nombreEnRetard(),
            'recus' => $recus->count(),
            'delai_moyen' => $delais->isNotEmpty() ? round($delais->avg(), 1) : null,
        ];
    }

    /** Charge les demandes de chaque type et les met à plat pour l'affichage. */
    private function lignes(\Closure $filtre): Collection
    {
        return collect($this->types)->flatMap(function (array $type, string $segment) use ($filtre) {
            [$modele, $structure, $libelle] = $type;

            return $modele::query()
                ->with(['vetement', 'particulier', $structure])
                ->when($this->portee !== null, $this->portee)
                ->where($filtre)
                ->get()
                ->map(fn ($demande) => [
                    'demande' => $demande,
                    'segment' => $segment,
                    'type' => $libelle,
                    'structure' => $demande->{$structure},
                    // Jours écoulés depuis l'acceptation (attendus) ou entre acceptation et dépôt (reçus).
                    'jours' => $demande->date_acceptation ? (int) $demande->date_acceptation->diffInDays(now()) : null,
                    'delai' => $demande->date_acceptation && $demande->date_depot_effectif
                        ? round($demande->date_acceptation->diffInHours($demande->date_depot_effectif) / 24, 1) : null,
                    'en_retard' => $demande->statut->value === 'acceptee' && $demande->date_acceptation?->lte(now()->subDays(self::DELAI_JOURS + 1)),
                ]);
        });
    }

    private function compter(\Closure $filtre): int
    {
        return collect($this->types)->sum(fn (array $type) => $type[0]::query()->when($this->portee !== null, $this->portee)->where($filtre)->count());
    }
}
