<?php

// Module 3 — Transformations, inspirations et avis

use App\Http\Controllers\Transformations\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Transformations\Atelier\InspirationController as AtelierInspirationController;
use App\Http\Controllers\Transformations\Atelier\TransformationController;
use App\Http\Controllers\Transformations\AvisStructureController;
use App\Http\Controllers\Transformations\DemandeTransformationController;
use App\Http\Controllers\Transformations\IdeesIaController;
use App\Http\Controllers\Transformations\InspirationController;
use App\Http\Controllers\Transformations\ReviewController;
use Illuminate\Support\Facades\Route;

// Pages publiques : galerie des inspirations d'upcycling
Route::get('/inspirations', [InspirationController::class, 'index'])->name('inspirations.index');
Route::get('/inspirations/{inspiration}', [InspirationController::class, 'show'])->whereNumber('inspiration')->name('inspirations.show');

// Particulier : ses demandes de transformation et ses avis
Route::middleware(['auth', 'verified', 'role:particulier'])->group(function () {
    Route::demandesParticulier('transformations', DemandeTransformationController::class);

    // Assistant IA : idées de transformation à partir de la photo, puis aperçu du résultat
    Route::get('/transformations/idees', [IdeesIaController::class, 'index'])->name('transformations.idees');
    Route::post('/transformations/idees', [IdeesIaController::class, 'idees'])->middleware('throttle:10,1')->name('transformations.idees.generer');
    Route::post('/transformations/apercu', [IdeesIaController::class, 'apercu'])->middleware('throttle:5,1')->name('transformations.apercu');

    Route::get('/mes-avis', [ReviewController::class, 'index'])->name('avis.index');
    Route::middleware('permission:publier-avis')->group(function () {
        Route::post('/avis/{type}/{id}', [ReviewController::class, 'store'])
            ->whereIn('type', ['reparations', 'transformations', 'dons'])->whereNumber('id')->name('avis.store');
        Route::put('/avis/{review}', [ReviewController::class, 'update'])->name('avis.update');
    });
    Route::delete('/avis/{review}', [ReviewController::class, 'destroy'])->name('avis.destroy');
});

// Atelier : traitement des demandes et réponses aux avis
Route::prefix('atelier')->name('atelier.')->middleware(['auth', 'verified', 'role:atelier'])->group(function () {
    Route::demandesStructure('transformations', TransformationController::class, [
        'demarrer' => 'demarrer',
        'terminer' => 'terminer',
        'recuperation' => 'confirmerRecuperation',
    ]);

    // Inspirations : CRUD des modèles d'upcycling publiés par l'atelier
    Route::middleware('permission:publier-inspirations')->group(function () {
        Route::resource('inspirations', AtelierInspirationController::class);
        Route::post('/inspirations/{inspiration}/basculer', [AtelierInspirationController::class, 'basculer'])->name('inspirations.basculer');
    });

    Route::get('/avis', [AvisStructureController::class, 'index'])->name('avis.index');
    Route::post('/avis/{review}/repondre', [AvisStructureController::class, 'repondre'])->middleware('permission:repondre-avis')->name('avis.repondre');
});

// Association : réponses aux avis
Route::prefix('association')->name('association.')->middleware(['auth', 'verified', 'role:association'])->group(function () {
    Route::get('/avis', [AvisStructureController::class, 'index'])->name('avis.index');
    Route::post('/avis/{review}/repondre', [AvisStructureController::class, 'repondre'])->middleware('permission:repondre-avis')->name('avis.repondre');
});

// Admin : supervision et modération des avis
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'role:admin|moderateur'])->group(function () {
    Route::middleware('permission:superviser-demandes')->group(fn () => Route::supervision('transformations'));

    Route::middleware('permission:moderer-avis')->group(function () {
        Route::get('/avis', [AdminReviewController::class, 'index'])->name('reviews.index');
        Route::post('/avis/{review}/basculer', [AdminReviewController::class, 'basculer'])->name('reviews.basculer');
        Route::delete('/avis/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');
    });
});
