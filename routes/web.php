<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\DepotController;
use App\Http\Controllers\NotificationController;
use App\Support\Modules;
use Illuminate\Support\Facades\Route;

// ── Macros partagées par les modules de demandes (2, 3, 4) ───────

// Côté particulier : liste, création, consultation, modification, suppression, annulation.
Route::macro('demandesParticulier', function (string $segment, string $controller) {
    Route::get("/{$segment}", [$controller, 'index'])->name("{$segment}.index");
    Route::get("/{$segment}/nouvelle", [$controller, 'create'])->name("{$segment}.create");
    Route::post("/{$segment}", [$controller, 'store'])->name("{$segment}.store");
    Route::get("/{$segment}/{demande}", [$controller, 'show'])->whereNumber('demande')->name("{$segment}.show");
    Route::get("/{$segment}/{demande}/modifier", [$controller, 'edit'])->whereNumber('demande')->name("{$segment}.edit");
    Route::put("/{$segment}/{demande}", [$controller, 'update'])->whereNumber('demande')->name("{$segment}.update");
    Route::delete("/{$segment}/{demande}", [$controller, 'destroy'])->whereNumber('demande')->name("{$segment}.destroy");
    Route::post("/{$segment}/{demande}/annuler", [$controller, 'annuler'])->whereNumber('demande')->name("{$segment}.annuler");
});

// Côté atelier / association : étapes communes, puis les étapes propres au module ([nom de route => méthode]).
Route::macro('demandesStructure', function (string $segment, string $controller, array $etapes) {
    Route::controller($controller)->prefix($segment)->name("{$segment}.")->group(function () use ($etapes) {
        Route::get('/', 'index')->name('index');
        Route::get('/{demande}', 'show')->name('show');
        Route::post('/{demande}/accepter', 'accepter')->name('accepter');
        Route::post('/{demande}/refuser', 'refuser')->name('refuser');
        Route::post('/{demande}/depot', 'confirmerDepot')->name('depot');
        foreach ($etapes as $nom => $methode) {
            Route::post("/{demande}/{$nom}", $methode)->name($nom);
        }
    });
});

// Côté admin : supervision des demandes (lecture seule).
Route::macro('supervision', function (string $type) {
    Route::get("/demandes/{$type}", [Admin\SupervisionController::class, 'index'])->defaults('type', $type)->name("{$type}.index");
    Route::get("/demandes/{$type}/{id}", [Admin\SupervisionController::class, 'show'])->defaults('type', $type)->whereNumber('id')->name("{$type}.show");
});

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
Route::view('/', 'front.commun.home')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
});

// ── Espaces atelier et association : partie commune ───────────────
Route::prefix('atelier')->name('atelier.')->middleware(['auth', 'role:atelier'])->group(function () {
    Route::get('/depots', [DepotController::class, 'index'])->name('depots.index');
});

Route::prefix('association')->name('association.')->middleware(['auth', 'role:association'])->group(function () {
    Route::get('/depots', [DepotController::class, 'index'])->name('depots.index');
});

// ── Administration : partie commune ───────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    // Utilisateurs
    Route::get('/utilisateurs', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/utilisateurs/{user}', [Admin\UserController::class, 'show'])->name('users.show');
    Route::post('/utilisateurs/{user}/valider', [Admin\UserController::class, 'valider'])->name('users.valider');
    Route::post('/utilisateurs/{user}/bloquer', [Admin\UserController::class, 'bloquer'])->name('users.bloquer');
    Route::post('/utilisateurs/{user}/debloquer', [Admin\UserController::class, 'debloquer'])->name('users.debloquer');
    Route::delete('/utilisateurs/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

    // Ateliers & associations
    foreach (['ateliers', 'associations'] as $type) {
        Route::prefix($type)->name("{$type}.")->controller(Admin\StructureController::class)->group(function () use ($type) {
            Route::get('/', 'index')->defaults('type', $type)->name('index');
            Route::get('/{id}', 'show')->defaults('type', $type)->whereNumber('id')->name('show');
            Route::post('/{id}/valider', 'valider')->defaults('type', $type)->whereNumber('id')->name('valider');
            Route::post('/{id}/desactiver', 'desactiver')->defaults('type', $type)->whereNumber('id')->name('desactiver');
        });
    }

    // Dépôts & rapports
    Route::get('/depots', [DepotController::class, 'index'])->name('depots.index');
    Route::get('/rapports', [Admin\RapportController::class, 'index'])->name('rapports.index');
    Route::get('/rapports/export', [Admin\RapportController::class, 'export'])->name('rapports.export');
});

// ── Modules : un fichier de routes par module (routes/modules/*.php) ─
foreach (Modules::actifs() as $module) {
    require Modules::fichier($module);
}
