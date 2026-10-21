<?php

namespace App\Models\Ateliers;

use App\Enums\TypeService;
use App\Support\Gemini;
use App\Support\Texte;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * « Quel atelier pour mon besoin ? » : le particulier décrit son besoin en une phrase,
 * l'IA (Gemini) le compare aux services, spécialités, notes et villes des ateliers actifs
 * et recommande les 3 plus adaptés, avec une explication.
 * Sans IA disponible, un classement par mots-clés prend le relais.
 */
class ConseillerAteliers
{
    public const NB = 3;

    /**
     * @return array{base: string, type: string, type_libelle: string, conseil: ?string, ateliers: array}
     */
    public function recommander(string $besoin): array
    {
        $besoin = Str::limit(trim($besoin), 500, '');
        $candidats = Atelier::actifs()->avecNote()->with(['services' => fn ($q) => $q->where('actif', true)])->get()
            ->filter(fn (Atelier $a) => $a->services->isNotEmpty())->values();

        $ia = Cache::remember('conseil.ateliers.'.md5(Str::lower($besoin).'|'.$candidats->pluck('id')->join(',')), now()->addHour(),
            fn () => $this->demanderIa($besoin, $candidats)) ?: null;

        if ($ia === null) {
            return $this->parMotsCles($besoin, $candidats);
        }

        $type = TypeService::tryFrom($ia['type_service'] ?? '') ?? TypeService::REPARATION;
        // Seuls les ateliers réellement proposés à l'IA sont retenus, sans doublon.
        $ateliers = collect($ia['recommandations'] ?? [])
            ->map(fn ($r) => ['atelier' => $candidats->firstWhere('id', (int) ($r['atelier_id'] ?? 0)), 'r' => $r])
            ->filter(fn ($x) => $x['atelier'])->unique(fn ($x) => $x['atelier']->id)->take(self::NB)
            ->map(fn ($x) => $this->carte($x['atelier'], $type, Str::limit(trim((string) ($x['r']['raison'] ?? '')), 300), $x['r']['service'] ?? null))
            ->values()->all();

        return $ateliers
            ? ['base' => 'gemini', 'type' => $type->value, 'type_libelle' => $type->label(), 'conseil' => Str::limit(trim((string) ($ia['conseil'] ?? '')), 300) ?: null, 'ateliers' => $ateliers]
            : $this->parMotsCles($besoin, $candidats);
    }

    private function demanderIa(string $besoin, Collection $candidats): ?array
    {
        if (! Gemini::configure() || $candidats->isEmpty()) {
            return null;
        }

        $catalogue = $candidats->map(fn (Atelier $a) => sprintf(
            "[%d] %s — %s — %s — note %s (%d avis)\n    Services : %s",
            $a->id, $a->nom, $a->specialite ?: 'atelier textile', trim($a->code_postal.' '.$a->ville) ?: 'ville non précisée',
            $a->noteMoyenne() ?? '—', $a->nombreAvis(),
            $a->services->map(fn (ServiceAtelier $s) => "{$s->nom} ({$s->type->label()}, {$s->prixFormate()}".($s->delai_jours ? ", {$s->delai_jours} j" : '').')')->join(' ; '),
        ))->join("\n");

        $types = collect(TypeService::cases())->map(fn ($t) => $t->value)->join(' ou ');

        return Gemini::json(
            'Tu conseilles un particulier français qui cherche un atelier textile. Lis son besoin et choisis, parmi les ateliers listés uniquement, '
            .'les '.self::NB.' plus adaptés : d\'abord le service correspondant au besoin, puis la proximité géographique si une ville est citée, puis la note. '
            ."Type de service : {$types}. Pour chaque atelier : son identifiant, le nom exact du service le plus pertinent, "
            .'et une raison en une phrase, concrète (service, prix, ville ou note). Conseil : une phrase utile au particulier (ex. photographier le défaut). Réponds en français.',
            "Besoin du particulier : {$besoin}\n\nAteliers disponibles (identifiant entre crochets) :\n{$catalogue}",
            [
                'type' => 'OBJECT',
                'properties' => [
                    'type_service' => ['type' => 'STRING', 'enum' => array_column(TypeService::cases(), 'value')],
                    'recommandations' => ['type' => 'ARRAY', 'items' => [
                        'type' => 'OBJECT',
                        'properties' => ['atelier_id' => ['type' => 'INTEGER'], 'service' => ['type' => 'STRING'], 'raison' => ['type' => 'STRING']],
                        'required' => ['atelier_id', 'service', 'raison'],
                    ]],
                    'conseil' => ['type' => 'STRING'],
                ],
                'required' => ['type_service', 'recommandations', 'conseil'],
            ],
        );
    }

    /** Secours sans IA : mots du besoin retrouvés dans les services, la spécialité et la ville ; puis la note. */
    private function parMotsCles(string $besoin, Collection $candidats): array
    {
        $mots = Texte::mots($besoin);
        $transformation = $mots->intersect(Texte::mots('transformer transformation upcycling sac jupe top coussin patchwork'))->isNotEmpty();
        $type = $transformation ? TypeService::TRANSFORMATION : TypeService::REPARATION;

        $classes = $candidats->map(function (Atelier $a) use ($mots, $type) {
            $services = $a->services->where('type', $type);
            $meilleur = $services->sortByDesc(fn ($s) => Texte::couverture($mots, Texte::mots($s->nom.' '.$s->description)))->first();
            $score = Texte::couverture($mots, Texte::mots($a->services->pluck('nom')->join(' ').' '.$a->specialite))
                + Texte::couverture($mots, Texte::mots($a->ville.' '.$a->code_postal)) * 0.5
                + ($services->isNotEmpty() ? 0.3 : 0) + ($a->noteMoyenne() ?? 0) / 50;

            return ['atelier' => $a, 'score' => $score, 'service' => $meilleur?->nom];
        })->filter(fn ($x) => $x['atelier']->services->contains('type', $type))->sortByDesc('score')->take(self::NB);

        return [
            'base' => 'mots-cles',
            'type' => $type->value,
            'type_libelle' => $type->label(),
            'conseil' => null,
            'ateliers' => $classes->map(fn ($x) => $this->carte($x['atelier'], $type, null, $x['service']))->values()->all(),
        ];
    }

    private function carte(Atelier $atelier, TypeService $type, ?string $raison, ?string $nomService): array
    {
        // Plusieurs services du même nom : le moins cher (le « dès … » affiché).
        $service = $atelier->services->filter(fn ($s) => $nomService && Str::lower($s->nom) === Str::lower($nomService))->sortBy('prix')->first()
            ?? $atelier->services->where('type', $type)->sortBy('prix')->first();

        return [
            'id' => $atelier->id,
            'nom' => $atelier->nom,
            'specialite' => $atelier->specialite,
            'ville' => trim($atelier->code_postal.' '.$atelier->ville),
            'note' => $atelier->noteMoyenne(),
            'avis' => $atelier->nombreAvis(),
            'logo' => $atelier->logoUrl(),
            'raison' => $raison,
            'service' => $service ? ['nom' => $service->nom, 'prix' => $service->prixFormate(), 'delai' => $service->delai_jours] : null,
            'url' => route('ateliers.show', $atelier),
            'demander' => route($type === TypeService::TRANSFORMATION ? 'transformations.create' : 'reparations.create', ['atelier' => $atelier->id]),
        ];
    }
}
