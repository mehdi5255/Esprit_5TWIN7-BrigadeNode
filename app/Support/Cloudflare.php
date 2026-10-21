<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Génération d'image par Cloudflare Workers AI (modèle FLUX, quota gratuit quotidien).
 *
 * Identifiants dans .env (CLOUDFLARE_ACCOUNT_ID, CLOUDFLARE_API_TOKEN), jamais versionnés.
 * Le modèle part d'un texte seulement : il ne voit pas la photo d'origine.
 * Sans identifiants ou en cas d'échec, image() renvoie null.
 */
class Cloudflare
{
    private const URL = 'https://api.cloudflare.com/client/v4/accounts/%s/ai/run/%s';

    public static function configure(): bool
    {
        return filled(config('services.cloudflare.account_id')) && filled(config('services.cloudflare.token'));
    }

    /** @return array{mime: string, data: string}|null  l'image générée (contenu binaire) */
    public static function image(string $consigne): ?array
    {
        if (! self::configure()) {
            return null;
        }

        try {
            $image = Http::timeout(60)
                ->withToken(config('services.cloudflare.token'))
                ->post(sprintf(self::URL, config('services.cloudflare.account_id'), config('services.cloudflare.image_model')), [
                    'prompt' => mb_substr($consigne, 0, 2000),
                    'steps' => 6,
                ])
                ->throw()
                ->json('result.image');

            return $image ? ['mime' => 'image/jpeg', 'data' => base64_decode($image)] : null;
        } catch (\Throwable $e) {
            Log::warning('Cloudflare Workers AI indisponible : '.$e->getMessage());

            return null;
        }
    }
}
