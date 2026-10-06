<?php

// Module 1 — Vêtements et catégories

use App\Http\Controllers\Vetements\Admin\CategorieVetementController;
use App\Http\Controllers\Vetements\VetementController;
use Illuminate\Support\Facades\Route;

// Particulier : CRUD de ses vêtements
Route::middleware(['auth', 'role:particulier'])->group(function () {
    Route::resource('vetements', VetementController::class);
});

// Admin : catégories de vêtements
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('categories', CategorieVetementController::class)->except(['show', 'create'])->parameters(['categories' => 'categorie']);
});
