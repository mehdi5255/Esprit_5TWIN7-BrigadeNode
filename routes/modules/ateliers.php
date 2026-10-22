<?php

// Module 5 — Ateliers et services

use App\Http\Controllers\Ateliers\Atelier\DashboardController;
use App\Http\Controllers\Ateliers\Atelier\ProfilController;
use App\Http\Controllers\Ateliers\Atelier\ServiceController;
use App\Http\Controllers\Ateliers\AtelierController;
use Illuminate\Support\Facades\Route;

// Pages publiques
Route::get('/ateliers', [AtelierController::class, 'index'])->name('ateliers.index');
Route::get('/ateliers/{atelier}', [AtelierController::class, 'show'])->name('ateliers.show');
// « Quel atelier pour mon besoin ? » : recommandation par l'IA (JSON)
Route::post('/ateliers/conseil', [AtelierController::class, 'conseil'])->middleware('throttle:6,1')->name('ateliers.conseil');

// Atelier : tableau de bord, profil et services
Route::prefix('atelier')->name('atelier.')->middleware(['auth', 'verified', 'role:atelier'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::middleware('permission:gerer-services')->group(function () {
        // Assistant IA : description, durée, délai et prix conseillé d'après le marché (JSON)
        Route::post('/services/assistant', [ServiceController::class, 'assistant'])->middleware('throttle:10,1')->name('services.assistant');
        Route::resource('services', ServiceController::class);
        Route::post('/services/{service}/basculer', [ServiceController::class, 'basculer'])->name('services.basculer');
    });
});
