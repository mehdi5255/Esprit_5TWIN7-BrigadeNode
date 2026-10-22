<?php

// DevOps — /metrics : métriques Prometheus lues par Grafana Cloud, protégées par un jeton.

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed());

test('sans jeton configuré, la page des métriques n’existe pas', function () {
    config(['services.metriques.token' => null]);

    $this->get('/metrics')->assertNotFound();
});

test('un jeton absent ou faux est refusé', function () {
    config(['services.metriques.token' => 'jeton-de-test']);

    $this->get('/metrics')->assertUnauthorized();
    $this->withToken('mauvais')->get('/metrics')->assertUnauthorized();
});

test('avec le bon jeton (Bearer ou Basic), les métriques sont au format Prometheus', function () {
    config(['services.metriques.token' => 'jeton-de-test']);

    $reponse = $this->withToken('jeton-de-test')->get('/metrics')->assertOk()->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
    expect($reponse->getContent())
        ->toContain('# TYPE textilecycle_utilisateurs gauge')
        ->toContain('textilecycle_utilisateurs{role="admin"} 1')
        ->toContain('textilecycle_demandes{module="reparation",statut="en_attente"}')
        ->toContain('textilecycle_demandes{module="don",statut="en_attente"}')
        ->toContain('textilecycle_structures{type="atelier",statut="actif"}')
        ->toContain('textilecycle_co2_evite_kg')
        ->toContain('textilecycle_base_donnees_latence_secondes');

    $this->withBasicAuth('grafana', 'jeton-de-test')->get('/metrics')->assertOk();
    expect($reponse->headers->getCookies())->toBeEmpty();    // pas de session créée à chaque lecture
});
