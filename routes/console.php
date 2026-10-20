<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mise en ligne (Render + MySQL Aiven) : tables à jour, et données de démonstration seulement si la base est vide.
Artisan::command('demo:initialiser', function () {
    $this->call('migrate', ['--force' => true]);

    if (User::query()->exists()) {
        $this->info('Base déjà remplie : aucune donnée effacée ni ajoutée.');

        return;
    }

    $this->info('Base vide : ajout des données de démonstration…');
    $this->call('db:seed', ['--force' => true]);
})->purpose('Migrer la base et la remplir avec les données de démo si elle est vide');
