<?php

// Mise en ligne — la commande demo:initialiser remplit une base vide, sans jamais effacer une base déjà remplie.

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('une base vide reçoit les données de démo, une base remplie est laissée intacte', function () {
    expect(User::count())->toBe(0);

    $this->artisan('demo:initialiser')->expectsOutputToContain('Base vide')->assertSuccessful();
    $comptes = User::count();
    expect($comptes)->toBeGreaterThan(0)->and(User::firstWhere('email', 'admin@textilecycle.fr'))->not->toBeNull();

    User::factory()->create(['email' => 'temoin@example.com']);
    $this->artisan('demo:initialiser')->expectsOutputToContain('Base déjà remplie')->assertSuccessful();
    expect(User::count())->toBe($comptes + 1)->and(User::where('email', 'temoin@example.com')->exists())->toBeTrue();
});
