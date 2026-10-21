<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Géocodage par Nominatim (OpenStreetMap) : gratuit et sans clé.
 *  - rechercher() : adresse saisie → lieux possibles avec leur position GPS ;
 *  - adresse()    : position cliquée sur la carte → adresse lisible.
 * Règles d'usage de Nominatim : une application identifiée (User-Agent) et peu de requêtes ;
 * les réponses sont donc mises en cache, et l'appel passe par notre serveur.
 */
class Geocodage
{
    private const URL = 'https://nominatim.openstreetmap.org';

    /** @return array<int, array{adresse: string, latitude: float, longitude: float}> */
    public static function rechercher(string $recherche): array
    {
        $recherche = trim($recherche);
        if (mb_strlen($recherche) < 3) {
            return [];
        }

        return Cache::remember('geocodage.recherche.'.md5(mb_strtolower($recherche)), now()->addDay(), function () use ($recherche) {
            $lieux = self::appeler('/search', ['q' => $recherche, 'format' => 'jsonv2', 'limit' => 6, 'addressdetails' => 1]);

            return collect($lieux ?? [])->map(fn ($lieu) => [
                'adresse' => self::courte($lieu),
                'latitude' => round((float) $lieu['lat'], 7),
                'longitude' => round((float) $lieu['lon'], 7),
            ])->unique('adresse')->take(5)->values()->all();
        });
    }

    public static function adresse(float $latitude, float $longitude): ?string
    {
        $cle = 'geocodage.adresse.'.round($latitude, 5).','.round($longitude, 5);

        return Cache::remember($cle, now()->addDay(), function () use ($latitude, $longitude) {
            $lieu = self::appeler('/reverse', ['lat' => $latitude, 'lon' => $longitude, 'format' => 'jsonv2', 'zoom' => 18, 'addressdetails' => 1]);

            return isset($lieu['display_name']) ? self::courte($lieu) : null;
        });
    }

    /**
     * Adresse au format postal (« 12 rue des Artisans, 75011 Paris ») plutôt que la description complète
     * d'OpenStreetMap (quartier, arrondissement, département, région…).
     */
    private static function courte(array $lieu): string
    {
        $a = $lieu['address'] ?? [];
        $voie = trim(($a['house_number'] ?? '').' '.($a['road'] ?? $a['pedestrian'] ?? $a['square'] ?? ''));
        $nom = $lieu['name'] ?? '';
        $ville = $a['city'] ?? $a['town'] ?? $a['village'] ?? $a['municipality'] ?? '';
        $parties = array_values(array_unique(array_filter([$nom, $voie !== $nom ? $voie : '', trim(($a['postcode'] ?? '').' '.$ville)])));

        return count($parties) >= 2 ? implode(', ', $parties) : $lieu['display_name'];
    }

    private static function appeler(string $chemin, array $parametres): ?array
    {
        try {
            return Http::timeout(10)
                ->withHeaders(['User-Agent' => 'TextileCycle/1.0 (projet etudiant; '.config('mail.from.address').')', 'Accept-Language' => 'fr'])
                ->get(self::URL.$chemin, $parametres)
                ->throw()
                ->json();
        } catch (\Throwable $e) {
            Log::warning('Géocodage indisponible : '.$e->getMessage());

            return null;
        }
    }
}
