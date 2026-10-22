<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        // Modèles de secours, séparés par des virgules, utilisés quand le quota du précédent est épuisé.
        'fallback_models' => env('GEMINI_FALLBACK_MODELS', 'gemini-3.5-flash-lite'),
        // Génération d'image : non incluse dans le niveau gratuit (facturation Google à activer) ; vide = désactivée.
        'image_model' => env('GEMINI_IMAGE_MODEL'),
    ],

    // Supervision : jeton exigé pour lire /metrics (Grafana Cloud). Vide = page désactivée.
    'metriques' => [
        'token' => env('METRICS_TOKEN'),
    ],

    'cloudflare' => [
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'token' => env('CLOUDFLARE_API_TOKEN'),
        'image_model' => env('CLOUDFLARE_IMAGE_MODEL', '@cf/black-forest-labs/flux-1-schnell'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
