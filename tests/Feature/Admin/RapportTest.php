<?php

// Administration — Rapport d'activité : synthèse comparée, données des graphiques, raccourcis de période.

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->admin = User::firstWhere('email', 'admin@textilecycle.fr');
});

test('le rapport présente la synthèse, les graphiques et l’axe du temps adapté à la période', function () {
    $reponse = $this->actingAs($this->admin)->get(route('admin.rapports.index', ['du' => now()->subDays(6)->toDateString(), 'au' => now()->toDateString()]))
        ->assertOk()->assertSee('Synthèse')->assertSee('Évolution des demandes')->assertSee('graphique-evolution', false)
        ->assertSee('chart.js@4.4.1', false)->assertSee('Exporter en PDF');

    $g = $reponse->viewData('graphiques');
    expect($g['unite'])->toBe('jour')->and($g['labels'])->toHaveCount(7)
        ->and(array_keys($g['evolution']))->toBe(['Réparation', 'Transformation', 'Don'])
        ->and(array_sum(array_merge(...array_values($g['evolution']))))->toBe($reponse->viewData('resume')['demandes'])
        ->and($g['types']['valeurs'])->toBe($reponse->viewData('parType')->pluck('total')->all());

    // Période précédente de même durée, juste avant.
    expect($reponse->viewData('avantAu')->toDateString())->toBe(now()->subDays(7)->toDateString());
});

test('les raccourcis de période changent la granularité des courbes', function () {
    expect($this->actingAs($this->admin)->get(route('admin.rapports.index', ['periode' => '3mois']))->assertOk()->viewData('graphiques')['unite'])->toBe('semaine');
    expect($this->actingAs($this->admin)->get(route('admin.rapports.index', ['periode' => 'annee']))->assertOk()->viewData('du')->toDateString())->toBe(now()->startOfYear()->toDateString());
    $this->actingAs($this->admin)->get(route('admin.rapports.index', ['periode' => 'siecle']))->assertSessionHasErrors('periode');
});
