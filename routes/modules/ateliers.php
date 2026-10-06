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

// Atelier : tableau de bord, profil et services
Route::prefix('atelier')->name('atelier.')->middleware(['auth', 'role:atelier'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::resource('services', ServiceController::class);
    Route::post('/services/{service}/basculer', [ServiceController::class, 'basculer'])->name('services.basculer');
});
