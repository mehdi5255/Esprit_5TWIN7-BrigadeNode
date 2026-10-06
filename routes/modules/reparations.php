<?php

// Module 2 — Réparations

use App\Http\Controllers\Reparations\Atelier\ReparationController;
use App\Http\Controllers\Reparations\DemandeReparationController;
use Illuminate\Support\Facades\Route;

// Particulier : ses demandes de réparation
Route::middleware(['auth', 'role:particulier'])->group(function () {
    Route::demandesParticulier('reparations', DemandeReparationController::class);
});

// Atelier : traitement des demandes
Route::prefix('atelier')->name('atelier.')->middleware(['auth', 'role:atelier'])->group(function () {
    Route::demandesStructure('reparations', ReparationController::class, [
        'demarrer' => 'demarrer',
        'terminer' => 'terminer',
        'recuperation' => 'confirmerRecuperation',
    ]);
});

// Admin : supervision
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::supervision('reparations');
});
