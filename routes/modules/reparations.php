<?php

// Module 2 — Réparations (devis, facture, garantie)

use App\Http\Controllers\Reparations\Atelier\FactureController;
use App\Http\Controllers\Reparations\Atelier\ReparationController;
use App\Http\Controllers\Reparations\DemandeReparationController;
use Illuminate\Support\Facades\Route;

// Particulier : ses demandes de réparation, ses devis, factures et garanties
Route::middleware(['auth', 'verified', 'role:particulier'])->group(function () {
    Route::demandesParticulier('reparations', DemandeReparationController::class);
    // Pré-diagnostic IA : la photo de l'abîmement remplit le formulaire de demande
    Route::post('/reparations/pre-diagnostic', [DemandeReparationController::class, 'preDiagnostic'])->middleware('throttle:10,1')->name('reparations.pre-diagnostic');

    Route::controller(DemandeReparationController::class)->prefix('reparations')->name('reparations.')->group(function () {
        Route::post('/{demande}/devis/accepter', 'accepterDevis')->whereNumber('demande')->name('devis.accepter');
        Route::post('/{demande}/devis/refuser', 'refuserDevis')->whereNumber('demande')->name('devis.refuser');
        Route::get('/{demande}/facture', 'facture')->whereNumber('demande')->name('facture');
        Route::post('/garanties/{garantie}/retour', 'ouvrirRetour')->whereNumber('garantie')->name('garantie.retour');
    });
});

// Atelier : traitement des demandes, devis et factures
Route::prefix('atelier')->name('atelier.')->middleware(['auth', 'verified', 'role:atelier'])->group(function () {
    Route::demandesStructure('reparations', ReparationController::class, [
        'demarrer' => 'demarrer',
        'terminer' => 'terminer',
        'recuperation' => 'confirmerRecuperation',
    ]);

    // Après le dépôt : diagnostic + devis (avec aide au chiffrage), puis modification tant que le particulier n'a pas répondu.
    Route::post('/reparations/{demande}/estimation', [ReparationController::class, 'estimer'])->name('reparations.estimation');
    Route::post('/reparations/{demande}/devis', [ReparationController::class, 'etablirDevis'])->name('reparations.devis.etablir');
    Route::put('/reparations/{demande}/devis', [ReparationController::class, 'modifierDevis'])->name('reparations.devis');
    Route::get('/reparations/{demande}/facture', [ReparationController::class, 'facture'])->name('reparations.facture');
    Route::get('/factures', [FactureController::class, 'index'])->name('factures.index');
});

// Admin : supervision
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'role:admin|moderateur', 'permission:superviser-demandes'])->group(function () {
    Route::supervision('reparations');
});
