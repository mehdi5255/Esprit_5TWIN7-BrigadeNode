<?php

namespace App\Support;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client minimal de l'API Gemini (Google AI Studio).
 *
 * La clé se met dans .env (GEMINI_API_KEY) : elle n'est jamais versionnée.
 * Sans clé, ou si l'appel échoue (réseau, quota…), les méthodes renvoient null et
 * l'appelant garde son propre calcul : l'IA est un plus, jamais un point de panne.
 *
 * Quota gratuit très limité (environ 20 requêtes par jour et par modèle) : quand le modèle principal
 * est épuisé (erreur 429), on passe aux modèles de secours (GEMINI_FALLBACK_MODELS), chacun ayant son quota.
 */
class Gemini
{
    private const URL = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    public static function configure(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * Pose une question et récupère une réponse JSON conforme au schéma donné.
     *
     * @param  array  $schema  schéma OpenAPI de la réponse attendue
     * @param  array<int, array{mime: string, data: string}>  $images  images jointes (contenu binaire)
     */
    public static function json(string $consigne, string $question, array $schema, array $images = []): ?array
    {
        $corps = [
            'systemInstruction' => ['parts' => [['text' => $consigne.' Écris les accents directement (é, è, à, ç), sans séquence d’échappement.']]],
            'contents' => [['role' => 'user', 'parts' => [...self::parties($images), ['text' => $question]]]],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
            ],
        ];

        // Il arrive que le modèle abîme un accent (« excellent \tat » au lieu de « excellent état ») : on redemande une fois.
        for ($essai = 1; $essai <= 2; $essai++) {
            $reponse = self::appelerAvecSecours($corps, 45);
            $donnees = json_decode($reponse['candidates'][0]['content']['parts'][0]['text'] ?? '', true) ?: null;
            if ($donnees === null || ! self::estAbime($donnees)) {
                return $donnees;
            }
            Log::notice('Gemini : texte abîmé, nouvelle tentative.');
        }

        // Toujours abîmé : une tabulation collée à une lettre remplace presque toujours un « é » (« d\tfaut » → « défaut »).
        array_walk_recursive($donnees, function (&$valeur) {
            if (is_string($valeur)) {
                $valeur = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', fn ($m) => mb_chr(hexdec($m[1])), $valeur);
                $valeur = preg_replace(['/\t(?=\p{L})/u', '/[\x00-\x09\x0B-\x1F]/'], ['é', ' '], $valeur);
            }
        });

        return $donnees;
    }

    /** Un texte contient un caractère de contrôle (tabulation…) ou un accent resté codé (\u00e9). */
    private static function estAbime(array $donnees): bool
    {
        $abime = false;
        array_walk_recursive($donnees, function ($valeur) use (&$abime) {
            $abime = $abime || (is_string($valeur) && preg_match('/[\x00-\x09\x0B-\x1F]|\\\\u[0-9a-fA-F]{4}/', $valeur) === 1);
        });

        return $abime;
    }

    /**
     * Génère une image à partir d'une consigne et d'images de départ.
     *
     * @return array{mime: string, data: string}|null l'image générée (contenu binaire)
     */
    public static function image(string $consigne, array $images = []): ?array
    {
        // Modèle d'image laissé vide dans .env : fonctionnalité désactivée (non incluse dans le niveau gratuit).
        if (blank(config('services.gemini.image_model'))) {
            return null;
        }

        $reponse = self::appeler(config('services.gemini.image_model'), [
            'contents' => [['role' => 'user', 'parts' => [...self::parties($images), ['text' => $consigne]]]],
            'generationConfig' => ['responseModalities' => ['TEXT', 'IMAGE']],
        ], 90);

        foreach ($reponse['candidates'][0]['content']['parts'] ?? [] as $partie) {
            if (isset($partie['inlineData']['data'])) {
                return ['mime' => $partie['inlineData']['mimeType'] ?? 'image/png', 'data' => base64_decode($partie['inlineData']['data'])];
            }
        }

        return null;
    }

    /**
     * Modèle principal, puis modèles de secours quand le quota du précédent est épuisé.
     * Un modèle épuisé est mémorisé une heure pour ne pas perdre de temps à le redemander.
     */
    private static function appelerAvecSecours(array $corps, int $delai): ?array
    {
        $modeles = array_filter([config('services.gemini.model'), ...explode(',', (string) config('services.gemini.fallback_models'))], fn ($m) => filled(trim($m)));

        foreach (array_unique(array_map('trim', $modeles)) as $modele) {
            if (Cache::has("gemini.epuise.{$modele}")) {
                continue;
            }
            // Réflexion désactivée (réponse plus rapide) : paramètre propre aux modèles 2.x, refusé par les suivants.
            $corpsModele = str_starts_with($modele, 'gemini-2') ? array_merge_recursive($corps, ['generationConfig' => ['thinkingConfig' => ['thinkingBudget' => 0]]]) : $corps;
            $reponse = self::appeler($modele, $corpsModele, $delai, $quotaEpuise);
            if (! $quotaEpuise) {
                return $reponse;
            }
            Cache::put("gemini.epuise.{$modele}", true, now()->addHour());
            Log::notice("Gemini : quota de {$modele} épuisé, passage au modèle suivant.");
        }

        return null;
    }

    private static function appeler(string $modele, array $corps, int $delai, ?bool &$quotaEpuise = null): ?array
    {
        $quotaEpuise = false;
        if (! self::configure()) {
            return null;
        }

        try {
            return Http::timeout($delai)
                // Serveurs Google momentanément surchargés (503) : une seconde tentative.
                ->retry(2, 1000, fn ($e) => $e instanceof RequestException && $e->response->status() === 503)
                ->withHeaders(['x-goog-api-key' => config('services.gemini.key')])
                ->post(sprintf(self::URL, $modele), $corps)
                ->throw()
                ->json();
        } catch (\Throwable $e) {
            $quotaEpuise = $e instanceof RequestException && $e->response->status() === 429;
            Log::warning("Gemini ({$modele}) indisponible : ".$e->getMessage());

            return null;
        }
    }

    private static function parties(array $images): array
    {
        return array_map(function ($image) {
            $image = self::reduire($image);

            return ['inlineData' => ['mimeType' => $image['mime'], 'data' => base64_encode($image['data'])]];
        }, $images);
    }

    /**
     * Photo lourde (téléphone) réduite à 1024 px et recompressée avant l'envoi : réponse plus rapide,
     * sans perte utile pour l'analyse. Sans l'extension GD, la photo part telle quelle.
     */
    private static function reduire(array $image, int $cote = 1024): array
    {
        if (strlen($image['data']) < 300_000 || ! function_exists('imagecreatefromstring')) {
            return $image;
        }
        $source = @imagecreatefromstring($image['data']);
        if (! $source) {
            return $image;
        }
        $echelle = min(1, $cote / max(imagesx($source), imagesy($source)));
        $reduite = imagescale($source, max(1, (int) round(imagesx($source) * $echelle)));
        ob_start();
        imagejpeg($reduite ?: $source, null, 80);

        return ['mime' => 'image/jpeg', 'data' => ob_get_clean()];
    }
}
