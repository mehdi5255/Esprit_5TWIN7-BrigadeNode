<?php

// Authentification — Laravel Breeze (routes publiées par « php artisan breeze:install »),
// adresses traduites en français et limitation des envois ajoutée.

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\DeuxFacteursConnexionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('inscription', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('inscription', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:inscription');

    Route::get('connexion', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('connexion', [AuthenticatedSessionController::class, 'store']);

    Route::get('mot-de-passe-oublie', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('mot-de-passe-oublie', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:mot-de-passe')
        ->name('password.email');

    // Deuxième étape de la connexion quand la 2FA (Fortify) est active
    Route::get('verification-deux-etapes', [DeuxFacteursConnexionController::class, 'create'])
        ->name('two-factor.login');

    Route::post('verification-deux-etapes', [DeuxFacteursConnexionController::class, 'store'])
        ->middleware('throttle:deux-facteurs');
});

// Lien reçu par e-mail : ouvert déconnecté (« Mot de passe oublié ») ou connecté (« Mon compte »).
Route::get('reinitialiser-mot-de-passe/{token}', [NewPasswordController::class, 'create'])
    ->name('password.reset');

Route::post('reinitialiser-mot-de-passe', [NewPasswordController::class, 'store'])
    ->name('password.store');

Route::middleware('auth')->group(function () {
    Route::get('verification-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verification-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('verification-email/renvoyer', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirmer-mot-de-passe', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirmer-mot-de-passe', [ConfirmablePasswordController::class, 'store']);

    Route::post('deconnexion', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
