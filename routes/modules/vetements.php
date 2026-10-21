<?php

// Module 1 — Vêtements et catégories

use App\Http\Controllers\Vetements\Admin\CategorieVetementController;
use App\Http\Controllers\Vetements\Admin\MatiereController;
use App\Http\Controllers\Vetements\VetementController;
use Illuminate\Support\Facades\Route;

// Particulier : CRUD de ses vêtements
Route::middleware(['auth', 'verified', 'role:particulier'])->group(function () {
    Route::resource('vetements', VetementController::class);
});

// Admin : catégories de vêtements et catalogue des matières
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'role:admin|moderateur', 'permission:gerer-catalogue'])->group(function () {
    Route::resource('categories', CategorieVetementController::class)->except(['show', 'create'])->parameters(['categories' => 'categorie']);
    Route::resource('matieres', MatiereController::class)->except('show')->parameters(['matieres' => 'matiere']);
    Route::post('/matieres/{matiere}/basculer', [MatiereController::class, 'basculer'])->name('matieres.basculer');
});
