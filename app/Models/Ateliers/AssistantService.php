<?php

namespace App\Models\Ateliers;

use App\Enums\StatutStructure;
use App\Enums\TypeService;
use App\Support\Gemini;
use App\Support\Texte;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Assistant de création de service : à partir du seul nom (« Remplacement de fermeture éclair »),
 * propose une description, une durée, un délai et un prix conseillé.
 * Le prix s'appuie sur le marché : les services similaires des autres ateliers actifs de la plateforme.
 * L'atelier valide ou corrige : rien n'est enregistré sans lui.
 */
class AssistantService
{
    /** Ressemblance minimale entre deux noms de service pour les comparer. */
    public const SEUIL = 0.3;

    /** Au-delà de cet écart avec la moyenne, le prix est signalé (en %). */
    public const ECART_SIGNALE = 20;

    /**
     * @return array{base: string, description: ?string, duree: ?int, delai_jours: ?int, prix: ?float, explication: ?string, marche: ?array}
     */
    public function suggerer(string $nom, TypeService $type, Atelier $atelier): array
    {
        $similaires = $this->similaires($nom, $type, $atelier);
        $marche = $this->marche($similaires);
        $ia = $this->demanderIa($nom, $type, $atelier, $similaires, $marche);

        if ($ia === null) {
            // Secours sans IA : les valeurs médianes des services similaires.
            $mediane = fn (string $champ) => ($valeur = $similaires->pluck($champ)->filter()->median()) !== null ? (int) round($valeur) : null;

            return [
                'base' => $marche ? 'marche' : 'aucune',
                'description' => null,
                'duree' => $mediane('duree'),
                'delai_jours' => $mediane('delai_jours'),
                'prix' => $marche['moyenne'] ?? null,
                'explication' => null,
                'marche' => $marche,
            ];
        }

        return [
            'base' => 'gemini',
            'description' => Str::limit(trim((string) $ia['description']), 1000),
            'duree' => $this->borne($ia['duree_minutes'] ?? null, 1, 10000),
            'delai_jours' => $this->borne($ia['delai_jours'] ?? null, 0, 365),
            'prix' => is_numeric($ia['prix'] ?? null) && $ia['prix'] >= 0 && $ia['prix'] <= 9999 ? round($ia['prix'] * 2) / 2 : null,
            'explication' => Str::limit(trim((string) ($ia['explication'] ?? '')), 300) ?: null,
            'marche' => $marche,
        ];
    }

    /** Services actifs et tarifés des autres ateliers actifs, du même type et au nom proche. */
    public function similaires(string $nom, TypeService $type, ?Atelier $atelier = null): Collection
    {
        $mots = Texte::mots($nom);

        return ServiceAtelier::query()
            ->where('type', $type)->where('actif', true)->whereNotNull('prix')
            ->when($atelier, fn ($q) => $q->where('atelier_id', '!=', $atelier->id))
            ->whereHas('atelier', fn ($q) => $q->where('statut', StatutStructure::ACTIF))
            ->get()
            ->map(fn (ServiceAtelier $s) => $s->setAttribute('ressemblance', Texte::ressemblance($mots, Texte::mots($s->nom))))
            ->filter(fn ($s) => $s->ressemblance >= self::SEUIL)
            ->sortByDesc('ressemblance')->values();
    }

    /** Fourchette de prix du marché, ou null s'il n'y a aucun service comparable. */
    public function marche(Collection $similaires): ?array
    {
        $prix = $similaires->pluck('prix')->map(fn ($p) => (float) $p)->sort()->values();
        if ($prix->isEmpty()) {
            return null;
        }

        return [
            'nombre' => $prix->count(),
            'ateliers' => $similaires->pluck('atelier_id')->unique()->count(),
            'min' => $prix->first(),
            'max' => $prix->last(),
            'moyenne' => round($prix->avg() * 2) / 2,
            'mediane' => round($prix->median() * 2) / 2,
        ];
    }

    /** Écart d'un prix avec la moyenne du marché, pour l'indicateur de positionnement. */
    public static function positionnement(?float $prix, ?array $marche): ?array
    {
        if ($prix === null || ! $marche || $marche['moyenne'] <= 0) {
            return null;
        }
        $ecart = (int) round(($prix - $marche['moyenne']) / $marche['moyenne'] * 100);

        return ['ecart' => $ecart, 'niveau' => match (true) {
            $ecart > self::ECART_SIGNALE => 'plus-cher',
            $ecart < -self::ECART_SIGNALE => 'moins-cher',
            default => 'dans-la-moyenne',
        }];
    }

    private function demanderIa(string $nom, TypeService $type, Atelier $atelier, Collection $similaires, ?array $marche): ?array
    {
        if (! Gemini::configure()) {
            return null;
        }

        $exemples = $similaires->take(8)->map(fn ($s) => "- {$s->nom} : {$s->prix} €".($s->duree ? ", {$s->duree} min" : '').($s->delai_jours !== null ? ", délai {$s->delai_jours} j" : ''))->join("\n");

        return Gemini::json(
            'Tu aides un atelier de couture français à créer la fiche d\'un service. À partir de son nom, rédige une description claire pour les clients '
            .'(2 phrases maximum : ce qui est fait, et pour quels vêtements), estime la durée de travail en minutes et le délai habituel en jours, '
            .'et propose un prix en euros. Pour le prix, appuie-toi d\'abord sur les services similaires des autres ateliers de la plateforme, '
            .'puis sur la difficulté du travail. Explication : une phrase sur le prix proposé. Réponds en français.',
            "Service : {$nom}\nType : {$type->label()}\nAtelier : {$atelier->nom}".($atelier->specialite ? " ({$atelier->specialite})" : '')
            ."\n\nServices similaires des autres ateliers :\n".($exemples ?: '- aucun')
            .($marche ? "\nFourchette du marché : {$marche['min']} à {$marche['max']} €, moyenne {$marche['moyenne']} €." : ''),
            [
                'type' => 'OBJECT',
                'properties' => [
                    'description' => ['type' => 'STRING'],
                    'duree_minutes' => ['type' => 'INTEGER'],
                    'delai_jours' => ['type' => 'INTEGER'],
                    'prix' => ['type' => 'NUMBER'],
                    'explication' => ['type' => 'STRING'],
                ],
                'required' => ['description', 'duree_minutes', 'delai_jours', 'prix', 'explication'],
            ],
        );
    }

    private function borne(mixed $valeur, int $min, int $max): ?int
    {
        return is_numeric($valeur) && $valeur >= $min && $valeur <= $max ? (int) round((float) $valeur) : null;
    }
}
