<?php

// La page d'accueil affiche des chiffres calculés en base : elle a besoin des tables.
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
