<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\DepotController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// ── Authentification ──────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store'])->middleware('throttle:connexion');
    Route::get('/inscription', [RegisterController::class, 'create'])->name('register');
    Route::post('/inscription', [RegisterController::class, 'store'])->middleware('throttle:inscription');

    // Mot de passe oublié
    Route::get('/mot-de-passe-oublie', [PasswordResetController::class, 'demande'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [PasswordResetController::class, 'envoyerLien'])->middleware('throttle:mot-de-passe')->name('password.email');
    Route::get('/reinitialiser-mot-de-passe/{token}', [PasswordResetController::class, 'formulaire'])->name('password.reset');
    Route::post('/reinitialiser-mot-de-passe', [PasswordResetController::class, 'reinitialiser'])->name('password.update');
});
Route::post('/deconnexion', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// ── Mon compte (tous les rôles) : chacun gère ses propres données ─
Route::middleware('auth')->prefix('mon-compte')->name('compte.')->controller(CompteController::class)->group(function () {
    Route::get('/', 'edit')->name('edit');
    Route::put('/', 'update')->name('update');
    Route::put('/mot-de-passe', 'updatePassword')->name('password');
    Route::delete('/', 'destroy')->name('destroy');
});

// ── Pages publiques ───────────────────────────────────────────────
Route::get('/', HomeController::class)->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
});

// ── Dépôts (atelier, association, admin) ──────────────────────────
foreach (['atelier', 'association', 'admin'] as $role) {
    Route::get("/{$role}/depots", [DepotController::class, 'index'])->middleware(['auth', "role:{$role}"])->name("{$role}.depots.index");
}

// ── Administration ────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    // Utilisateurs
    Route::get('/utilisateurs', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/utilisateurs/{user}', [Admin\UserController::class, 'show'])->name('users.show');
    Route::post('/utilisateurs/{user}/valider', [Admin\UserController::class, 'valider'])->name('users.valider');
    Route::post('/utilisateurs/{user}/bloquer', [Admin\UserController::class, 'bloquer'])->name('users.bloquer');
    Route::post('/utilisateurs/{user}/debloquer', [Admin\UserController::class, 'debloquer'])->name('users.debloquer');
    Route::delete('/utilisateurs/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

    // Rapports
    Route::get('/rapports', [Admin\RapportController::class, 'index'])->name('rapports.index');
    Route::get('/rapports/export', [Admin\RapportController::class, 'export'])->name('rapports.export');
});

// ── Routes partagées par les modules ──────────────────────────────
// Les trois types de demandes (réparations, transformations, dons) ont les mêmes routes :
// chaque module les déclare avec ces macros dans son fichier routes/modules/{module}.php.

/** Parcours du particulier : liste, création, suivi, modification, suppression, annulation. */
Route::macro('demandesParticulier', function (string $segment, string $controller) {
    Route::middleware(['auth', 'role:particulier'])->group(function () use ($segment, $controller) {
        Route::get("/{$segment}", [$controller, 'index'])->name("{$segment}.index");
        Route::get("/{$segment}/nouvelle", [$controller, 'create'])->name("{$segment}.create");
        Route::post("/{$segment}", [$controller, 'store'])->name("{$segment}.store");
        Route::get("/{$segment}/{demande}", [$controller, 'show'])->whereNumber('demande')->name("{$segment}.show");
        Route::get("/{$segment}/{demande}/modifier", [$controller, 'edit'])->whereNumber('demande')->name("{$segment}.edit");
        Route::put("/{$segment}/{demande}", [$controller, 'update'])->whereNumber('demande')->name("{$segment}.update");
        Route::delete("/{$segment}/{demande}", [$controller, 'destroy'])->whereNumber('demande')->name("{$segment}.destroy");
        Route::post("/{$segment}/{demande}/annuler", [$controller, 'annuler'])->whereNumber('demande')->name("{$segment}.annuler");
    });
});

/** Traitement d'une demande par une structure ; $etapes = [segment d'URL => méthode du contrôleur]. */
Route::macro('demandesStructure', function (string $role, string $segment, string $controller, array $etapes) {
    Route::prefix("{$role}/{$segment}")->name("{$role}.{$segment}.")->middleware(['auth', "role:{$role}"])->controller($controller)->group(function () use ($etapes) {
        Route::get('/', 'index')->name('index');
        Route::get('/{demande}', 'show')->name('show');
        foreach ($etapes as $uri => $methode) {
            Route::post("/{demande}/{$uri}", $methode)->name($uri);
        }
    });
});

/** Supervision en lecture seule par l'admin. */
Route::macro('supervision', function (string $type) {
    Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () use ($type) {
        Route::get("/demandes/{$type}", [Admin\SupervisionController::class, 'index'])->defaults('type', $type)->name("{$type}.index");
        Route::get("/demandes/{$type}/{id}", [Admin\SupervisionController::class, 'show'])->defaults('type', $type)->whereNumber('id')->name("{$type}.show");
    });
});

/** Ateliers et associations côté admin : liste, fiche, validation, désactivation. */
Route::macro('structuresAdmin', function (string $type) {
    Route::prefix("admin/{$type}")->name("admin.{$type}.")->middleware(['auth', 'role:admin'])->controller(Admin\StructureController::class)->group(function () use ($type) {
        Route::get('/', 'index')->defaults('type', $type)->name('index');
        Route::get('/{id}', 'show')->defaults('type', $type)->whereNumber('id')->name('show');
        Route::post('/{id}/valider', 'valider')->defaults('type', $type)->whereNumber('id')->name('valider');
        Route::post('/{id}/desactiver', 'desactiver')->defaults('type', $type)->whereNumber('id')->name('desactiver');
    });
});

// ── Modules : chaque branche feature/* ajoute son fichier dans routes/modules/ ──
foreach (glob(__DIR__.'/modules/*.php') as $module) {
    require $module;
}
