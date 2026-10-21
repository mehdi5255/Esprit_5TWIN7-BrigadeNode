<?php

use App\Http\Controllers\AccueilController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\DepotController;
use App\Http\Controllers\DeuxFacteursController;
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

// ── Authentification : Laravel Breeze (inscription, connexion, mot de passe oublié, vérification d'e-mail) ──
require __DIR__.'/auth.php';

// Tableau de bord : redirige chaque utilisateur vers l'espace de son rôle (cible des redirections de Breeze).
Route::get('/tableau-de-bord', function () {
    session()->reflash(); // garde le message de l'étape précédente (ex. inscription)

    return redirect(auth()->user()->homeRoute());
})->middleware(['auth', 'verified'])->name('dashboard');

// ── Mon compte (tous les rôles) : chacun gère ses propres données ─
Route::middleware('auth')->prefix('mon-compte')->name('compte.')->controller(CompteController::class)->group(function () {
    Route::get('/', 'edit')->name('edit');
    Route::put('/', 'update')->name('update');
    Route::put('/mot-de-passe', 'updatePassword')->name('password');
    // Mot de passe actuel oublié : lien de réinitialisation envoyé à l'adresse du compte (comme « Mot de passe oublié »).
    Route::post('/mot-de-passe/lien', 'envoyerLienReinitialisation')->middleware('throttle:3,1')->name('password.lien');
    Route::delete('/', 'destroy')->name('destroy');
});

// ── Mon compte : authentification à deux facteurs (Laravel Fortify) ─
Route::middleware('auth')->prefix('mon-compte/deux-facteurs')->name('compte.2fa.')->controller(DeuxFacteursController::class)->group(function () {
    Route::post('/', 'activer')->name('activer');
    Route::post('/confirmer', 'confirmer')->middleware('throttle:6,1')->name('confirmer');
    Route::post('/codes', 'regenererCodes')->name('codes');
    Route::delete('/', 'desactiver')->name('desactiver');
});

// ── Pages publiques ───────────────────────────────────────────────
Route::get('/', AccueilController::class)->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
});

// ── Espaces atelier et association : partie commune ───────────────
Route::prefix('atelier')->name('atelier.')->middleware(['auth', 'verified', 'role:atelier'])->group(function () {
    Route::get('/depots', [DepotController::class, 'index'])->name('depots.index');
});

Route::prefix('association')->name('association.')->middleware(['auth', 'verified', 'role:association'])->group(function () {
    Route::get('/depots', [DepotController::class, 'index'])->name('depots.index');
});

// ── Administration : partie commune ───────────────────────────────
// Admin et modérateur entrent dans l'administration ; chaque partie exige sa permission (Spatie).
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'role:admin|moderateur'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    // Utilisateurs
    Route::middleware('permission:gerer-utilisateurs')->group(function () {
        Route::get('/utilisateurs', [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('/utilisateurs/{user}', [Admin\UserController::class, 'show'])->name('users.show');
        Route::post('/utilisateurs/{user}/valider', [Admin\UserController::class, 'valider'])->name('users.valider');
        Route::post('/utilisateurs/{user}/bloquer', [Admin\UserController::class, 'bloquer'])->name('users.bloquer');
        Route::post('/utilisateurs/{user}/debloquer', [Admin\UserController::class, 'debloquer'])->name('users.debloquer');
        Route::delete('/utilisateurs/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');
    });

    // Ateliers & associations
    Route::middleware('permission:gerer-structures')->group(function () {
        foreach (['ateliers', 'associations'] as $type) {
            Route::prefix($type)->name("{$type}.")->controller(Admin\StructureController::class)->group(function () use ($type) {
                Route::get('/', 'index')->defaults('type', $type)->name('index');
                Route::get('/{id}', 'show')->defaults('type', $type)->whereNumber('id')->name('show');
                Route::post('/{id}/valider', 'valider')->defaults('type', $type)->whereNumber('id')->name('valider');
                Route::post('/{id}/desactiver', 'desactiver')->defaults('type', $type)->whereNumber('id')->name('desactiver');
            });
        }
    });

    // Dépôts & rapports
    Route::get('/depots', [DepotController::class, 'index'])->middleware('permission:superviser-demandes')->name('depots.index');
    Route::middleware('permission:voir-rapports')->group(function () {
        Route::get('/rapports', [Admin\RapportController::class, 'index'])->name('rapports.index');
        Route::get('/rapports/export', [Admin\RapportController::class, 'export'])->name('rapports.export');
    });

    // Rôles et permissions (Spatie) : permissions de chaque rôle, et modérateurs
    Route::middleware('permission:gerer-roles')->controller(Admin\RolePermissionController::class)->group(function () {
        Route::get('/roles', 'index')->name('roles.index');
        Route::put('/roles/{role}', 'update')->name('roles.update');
        Route::post('/roles/moderateurs', 'nommer')->name('roles.nommer');
        Route::delete('/roles/moderateurs/{user}', 'retirer')->name('roles.retirer');
    });
});

// ── Modules : un fichier de routes par module (routes/modules/*.php) ─
foreach (Modules::actifs() as $module) {
    require Modules::fichier($module);
}
