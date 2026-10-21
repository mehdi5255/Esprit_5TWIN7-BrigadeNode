<?php

// Module 4 — Dons, besoins et associations

use App\Http\Controllers\Dons\Association\BesoinController;
use App\Http\Controllers\Dons\Association\DashboardController;
use App\Http\Controllers\Dons\Association\DonController;
use App\Http\Controllers\Dons\Association\EvenementController;
use App\Http\Controllers\Dons\Association\GeocodageController;
use App\Http\Controllers\Dons\Association\ProfilController;
use App\Http\Controllers\Dons\AssociationController;
use App\Http\Controllers\Dons\DemandeDonController;
use App\Http\Controllers\Dons\EvenementController as EvenementPublicController;
use Illuminate\Support\Facades\Route;

// Pages publiques
Route::get('/associations', [AssociationController::class, 'index'])->name('associations.index');
Route::get('/associations/{association}', [AssociationController::class, 'show'])->name('associations.show');
Route::get('/besoins', [AssociationController::class, 'besoins'])->name('besoins.index');
Route::get('/evenements', [EvenementPublicController::class, 'index'])->name('evenements.index');
Route::get('/evenements/{evenement}', [EvenementPublicController::class, 'show'])->whereNumber('evenement')->name('evenements.show');

// Particulier : ses dons
Route::middleware(['auth', 'verified', 'role:particulier'])->group(function () {
    Route::demandesParticulier('dons', DemandeDonController::class);
    // Contrôle qualité IA avant l'envoi : le vêtement est-il donnable ?
    Route::post('/dons/controle', [DemandeDonController::class, 'controle'])->middleware('throttle:10,1')->name('dons.controle');
});

// Association : tableau de bord, profil, besoins et traitement des dons
Route::prefix('association')->name('association.')->middleware(['auth', 'verified', 'role:association'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::middleware('permission:gerer-besoins')->group(function () {
        Route::resource('besoins', BesoinController::class)->except('show');
        Route::post('/besoins/{besoin}/basculer', [BesoinController::class, 'basculer'])->name('besoins.basculer');
    });

    // Événements : collectes et distributions
    Route::middleware('permission:gerer-evenements')->group(function () {
        Route::resource('evenements', EvenementController::class);
        Route::post('/evenements/{evenement}/annuler', [EvenementController::class, 'annuler'])->name('evenements.annuler');
    });
    // Carte : recherche d'adresse et adresse d'un point cliqué (OpenStreetMap)
    Route::get('/geocodage', [GeocodageController::class, 'rechercher'])->middleware('throttle:30,1')->name('geocodage.rechercher');
    Route::get('/geocodage/adresse', [GeocodageController::class, 'adresse'])->middleware('throttle:30,1')->name('geocodage.adresse');

    Route::demandesStructure('dons', DonController::class, [
        'preparer' => 'preparer',
        'remettre' => 'remettre',
    ]);
    // Contrôle qualité à la réception : orientation proposée par l'IA, décidée par l'association
    Route::post('/dons/{demande}/orientation/analyse', [DonController::class, 'analyserOrientation'])->middleware('throttle:10,1')->name('dons.orientation.analyse');
    Route::post('/dons/{demande}/orientation', [DonController::class, 'orienter'])->name('dons.orientation');
});

// Admin : supervision
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'role:admin|moderateur', 'permission:superviser-demandes'])->group(function () {
    Route::supervision('dons');
});
