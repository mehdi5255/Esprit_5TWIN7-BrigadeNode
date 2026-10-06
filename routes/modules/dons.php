<?php

// Module 4 — Dons, besoins et associations

use App\Http\Controllers\Dons\Association\BesoinController;
use App\Http\Controllers\Dons\Association\DashboardController;
use App\Http\Controllers\Dons\Association\DonController;
use App\Http\Controllers\Dons\Association\ProfilController;
use App\Http\Controllers\Dons\AssociationController;
use App\Http\Controllers\Dons\DemandeDonController;
use Illuminate\Support\Facades\Route;

// Pages publiques
Route::get('/associations', [AssociationController::class, 'index'])->name('associations.index');
Route::get('/associations/{association}', [AssociationController::class, 'show'])->name('associations.show');
Route::get('/besoins', [AssociationController::class, 'besoins'])->name('besoins.index');

// Particulier : ses dons
Route::middleware(['auth', 'role:particulier'])->group(function () {
    Route::demandesParticulier('dons', DemandeDonController::class);
});

// Association : tableau de bord, profil, besoins et traitement des dons
Route::prefix('association')->name('association.')->middleware(['auth', 'role:association'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::resource('besoins', BesoinController::class)->except('show');
    Route::post('/besoins/{besoin}/basculer', [BesoinController::class, 'basculer'])->name('besoins.basculer');

    Route::demandesStructure('dons', DonController::class, [
        'preparer' => 'preparer',
        'remettre' => 'remettre',
    ]);
});

// Admin : supervision
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::supervision('dons');
});
