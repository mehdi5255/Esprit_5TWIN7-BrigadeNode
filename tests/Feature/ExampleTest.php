<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

// La page d'accueil affiche des chiffres calculés en base : elle a besoin des tables.
uses(RefreshDatabase::class);

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
