<?php

// Module 3 — Transformations et avis

use App\Http\Controllers\Transformations\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Transformations\Atelier\TransformationController;
use App\Http\Controllers\Transformations\AvisStructureController;
use App\Http\Controllers\Transformations\DemandeTransformationController;
use App\Http\Controllers\Transformations\ReviewController;
use Illuminate\Support\Facades\Route;

// Particulier : ses demandes de transformation et ses avis
Route::middleware(['auth', 'role:particulier'])->group(function () {
    Route::demandesParticulier('transformations', DemandeTransformationController::class);

    Route::get('/mes-avis', [ReviewController::class, 'index'])->name('avis.index');
    Route::post('/avis/{type}/{id}', [ReviewController::class, 'store'])
        ->whereIn('type', ['reparations', 'transformations', 'dons'])->whereNumber('id')->name('avis.store');
    Route::put('/avis/{review}', [ReviewController::class, 'update'])->name('avis.update');
    Route::delete('/avis/{review}', [ReviewController::class, 'destroy'])->name('avis.destroy');
});

// Atelier : traitement des demandes et réponses aux avis
Route::prefix('atelier')->name('atelier.')->middleware(['auth', 'role:atelier'])->group(function () {
    Route::demandesStructure('transformations', TransformationController::class, [
        'demarrer' => 'demarrer',
        'terminer' => 'terminer',
        'recuperation' => 'confirmerRecuperation',
    ]);

    Route::get('/avis', [AvisStructureController::class, 'index'])->name('avis.index');
    Route::post('/avis/{review}/repondre', [AvisStructureController::class, 'repondre'])->name('avis.repondre');
});

// Association : réponses aux avis
Route::prefix('association')->name('association.')->middleware(['auth', 'role:association'])->group(function () {
    Route::get('/avis', [AvisStructureController::class, 'index'])->name('avis.index');
    Route::post('/avis/{review}/repondre', [AvisStructureController::class, 'repondre'])->name('avis.repondre');
});

// Admin : supervision et modération des avis
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::supervision('transformations');

    Route::get('/avis', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::post('/avis/{review}/basculer', [AdminReviewController::class, 'basculer'])->name('reviews.basculer');
    Route::delete('/avis/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');
});
