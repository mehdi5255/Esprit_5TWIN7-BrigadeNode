<?php

namespace App\Models\Reparations;

use App\Enums\StatutDevis;
use App\Enums\TypeReparation;
use App\Enums\TypeService;
use App\Support\Gemini;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Aide au chiffrage d'un devis : estimation par les plus proches voisins (k-NN).
 *
 * On compare la demande aux devis déjà acceptés par des particuliers (prix « validés ») :
 *  - même type de réparation (couture, fermeture…) ;
 *  - ressemblance des textes (diagnostic + description), mesurée par les mots en commun (indice de Jaccard) ;
 *  - devis du même atelier, un peu favorisés (ses propres tarifs).
 * Le montant proposé est la moyenne des 5 devis les plus proches, pondérée par leur ressemblance.
 * Sans historique comparable, on se rabat sur les tarifs des services de réparation de l'atelier.
 * Si une clé Gemini est configurée, l'IA affine ce calcul et l'explique (voir avecGemini()).
 * L'atelier garde toujours la main : c'est une suggestion, qu'il peut modifier.
 */
class EstimateurDevis
{
    public const VOISINS = 5;

    /** Score minimal pour qu'un devis passé soit retenu (même type, ou textes vraiment proches). */
    public const SEUIL = 0.3;

    private const MOTS_VIDES = ['les', 'des', 'une', 'sur', 'avec', 'pour', 'dans', 'sans', 'est', 'pas', 'par', 'aux', 'tres', 'peu', 'plus', 'tout', 'cote', 'bien', 'fait', 'etre', 'qui', 'que', 'son', 'ses', 'mon', 'mes'];

    /**
     * @return array{montant: ?float, min: ?float, max: ?float, confiance: string, base: string, comparables: array}
     */
    public function estimer(DemandeReparation $demande, string $diagnostic): array
    {
        $voisins = $this->voisins($diagnostic.' '.$demande->description, $demande->type_reparation, $demande->atelier_id, $demande->id);
        $calcul = $voisins->isEmpty() ? $this->depuisTarifs($demande) : $this->depuisVoisins($voisins);

        return $this->avecGemini($demande, $diagnostic, $voisins, $calcul);
    }

    /**
     * Fourchette indicative pour le particulier, avant toute demande (pré-diagnostic par photo) :
     * mêmes devis comparables, sans détail des devis ni appel à l'IA.
     *
     * @return array{montant: float, min: float, max: float, nb: int}|null
     */
    public function fourchette(TypeReparation $type, string $description, ?int $atelierId = null): ?array
    {
        $voisins = $this->voisins($description, $type, $atelierId);
        if ($voisins->isEmpty()) {
            return null;
        }
        $calcul = $this->depuisVoisins($voisins);

        return ['montant' => $calcul['montant'], 'min' => $calcul['min'], 'max' => $calcul['max'], 'nb' => $voisins->count()];
    }

    /** Les devis acceptés les plus proches : même type, textes ressemblants, même atelier (un peu favorisé). */
    private function voisins(string $texte, TypeReparation $type, ?int $atelierId, ?int $exclure = null): Collection
    {
        $mots = self::mots($texte);

        return Devis::query()
            ->where('statut', StatutDevis::ACCEPTE)
            ->where('main_oeuvre', '>', 0)
            ->whereHas('demande', fn ($q) => $q->when($exclure, fn ($q) => $q->whereKeyNot($exclure))->whereNull('garantie_id'))
            ->with(['demande.reparation', 'demande.atelier'])
            ->get()
            ->map(function (Devis $devis) use ($type, $atelierId, $mots) {
                $autre = $devis->demande;
                $similarite = self::jaccard($mots, self::mots(($autre->reparation?->diagnostic ?? '').' '.$autre->description));
                $memeType = $autre->type_reparation === $type;
                $memeAtelier = $atelierId !== null && $autre->atelier_id === $atelierId;

                return [
                    'devis' => $devis,
                    'score' => round(0.6 * $similarite + ($memeType ? 0.3 : 0) + ($memeAtelier ? 0.1 : 0), 3),
                    'similarite' => $similarite,
                    'meme_atelier' => $memeAtelier,
                ];
            })
            ->filter(fn ($v) => $v['score'] >= self::SEUIL)
            ->sortByDesc('score')
            ->take(self::VOISINS)
            ->values();
    }

    /** Moyenne des devis voisins, pondérée par leur ressemblance. */
    private function depuisVoisins(Collection $voisins): array
    {
        $montants = $voisins->map(fn ($v) => (float) $v['devis']->main_oeuvre);
        $montant = $voisins->sum(fn ($v) => $v['score'] * $v['devis']->main_oeuvre) / $voisins->sum('score');

        return [
            'montant' => self::arrondir($montant),
            'min' => $montants->min(),
            'max' => $montants->max(),
            'confiance' => match (true) {
                $voisins->count() >= 3 && $voisins->avg('score') >= 0.5 => 'élevée',
                $voisins->count() >= 2 => 'moyenne',
                default => 'faible',
            },
            'base' => 'historique',
            'comparables' => $voisins->map(fn ($v) => [
                'reference' => $v['devis']->demande->reference(),
                'montant' => (float) $v['devis']->main_oeuvre,
                // Les autres ateliers restent anonymes : seul le montant est partagé.
                'atelier' => $v['meme_atelier'] ? 'Votre atelier' : 'Un autre atelier',
                'type' => $v['devis']->demande->type_reparation->label(),
                'ressemblance' => (int) round($v['score'] * 100),
                'diagnostic' => $v['meme_atelier'] ? Str::limit((string) $v['devis']->demande->reparation?->diagnostic, 80) : null,
            ])->all(),
        ];
    }

    /**
     * Affinage par Gemini : l'IA lit le diagnostic, les devis comparables et la grille tarifaire,
     * puis propose un montant justifié. Sans clé ou en cas d'échec, le calcul k-NN est renvoyé tel quel.
     * Aucun nom (particulier, atelier) n'est envoyé : seulement des textes de diagnostic et des montants.
     */
    private function avecGemini(DemandeReparation $demande, string $diagnostic, Collection $voisins, array $calcul): array
    {
        $calcul['explication'] = null;
        if (! Gemini::configure()) {
            return $calcul;
        }

        $comparables = $voisins->map(fn ($v) => sprintf('- %s, %s, ressemblance %d %% : « %s » → %.2f €',
            $v['meme_atelier'] ? 'cet atelier' : 'un autre atelier',
            $v['devis']->demande->type_reparation->label(),
            round($v['score'] * 100),
            trim(($v['devis']->demande->reparation?->diagnostic ?? '') ?: $v['devis']->demande->description),
            $v['devis']->main_oeuvre,
        ))->join("\n");
        $tarifs = $this->servicesReparation($demande)->map(fn ($s) => "- {$s->nom} : ".number_format((float) $s->prix, 2, '.', '').' €')->join("\n");

        $question = implode("\n", [
            'Type de réparation : '.$demande->type_reparation->label(),
            'Description du client : '.$demande->description,
            'Diagnostic de l\'atelier : '.$diagnostic,
            'Budget maximum du client : '.($demande->budget_max ? $demande->budget_max.' €' : 'non précisé'),
            "Devis passés acceptés les plus proches (main-d'œuvre) :\n".($comparables ?: '- aucun'),
            "Grille tarifaire de l'atelier :\n".($tarifs ?: '- non renseignée'),
            'Estimation statistique (moyenne pondérée des devis proches) : '.($calcul['montant'] !== null ? $calcul['montant'].' €' : 'aucune'),
        ]);

        $ia = Gemini::json(
            "Tu aides un atelier de couture français à chiffrer la main-d'œuvre d'une réparation de vêtement. "
            .'Appuie-toi d\'abord sur les devis passés de cet atelier, puis sur ceux des autres ateliers et sur sa grille tarifaire ; '
            .'ajuste selon la gravité décrite dans le diagnostic. Ne tiens pas compte du budget du client pour fixer le prix. '
            .'Montants en euros. Explication en français, 2 phrases maximum, sans citer de numéro de demande.',
            $question,
            [
                'type' => 'OBJECT',
                'properties' => [
                    'montant' => ['type' => 'NUMBER'],
                    'min' => ['type' => 'NUMBER'],
                    'max' => ['type' => 'NUMBER'],
                    'explication' => ['type' => 'STRING'],
                ],
                'required' => ['montant', 'min', 'max', 'explication'],
            ],
        );

        // Réponse invalide ou farfelue : on garde le calcul statistique.
        $montant = (float) ($ia['montant'] ?? 0);
        if ($montant <= 0 || $montant > 1000) {
            return $calcul;
        }

        return [
            'montant' => self::arrondir($montant),
            'min' => self::arrondir(min((float) $ia['min'], $montant)),
            'max' => self::arrondir(max((float) $ia['max'], $montant)),
            'confiance' => $calcul['base'] === 'historique' ? $calcul['confiance'] : 'faible',
            'base' => 'gemini',
            'explication' => Str::limit(trim((string) $ia['explication']), 400),
            'comparables' => $calcul['comparables'],
            'statistique' => $calcul['montant'],
        ];
    }

    private function servicesReparation(DemandeReparation $demande): Collection
    {
        return $demande->atelier->services()->where('type', TypeService::REPARATION)->where('actif', true)->whereNotNull('prix')->get();
    }

    /** Sans devis comparable : moyenne des tarifs des services de réparation actifs de l'atelier. */
    private function depuisTarifs(DemandeReparation $demande): array
    {
        $prix = $this->servicesReparation($demande)->pluck('prix')->map(fn ($p) => (float) $p);

        return [
            'montant' => $prix->isEmpty() ? null : self::arrondir($prix->avg()),
            'min' => $prix->min(),
            'max' => $prix->max(),
            'confiance' => 'faible',
            'base' => $prix->isEmpty() ? 'aucune' : 'tarifs',
            'comparables' => [],
        ];
    }

    /** Mots significatifs d'un texte, normalisés : « Coutures décousues » → [coutur, decousue]. */
    public static function mots(string $texte): Collection
    {
        return collect(preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii($texte))))
            ->filter(fn ($mot) => strlen($mot) >= 3 && ! in_array($mot, self::MOTS_VIDES, true) && ! is_numeric($mot))
            ->map(fn ($mot) => rtrim($mot, 'sx'))   // pluriels : « boutons » = « bouton »
            ->unique()->values();
    }

    /** Indice de Jaccard : mots communs / mots distincts des deux textes (0 à 1). */
    public static function jaccard(Collection $a, Collection $b): float
    {
        $union = $a->merge($b)->unique()->count();

        return $union === 0 ? 0.0 : round($a->intersect($b)->count() / $union, 3);
    }

    /** Arrondi au demi-euro. */
    private static function arrondir(float $montant): float
    {
        return round($montant * 2) / 2;
    }
}
