<?php

// Module 1 — Passeport textile : QR code de la composition sur la fiche du vêtement, relu par « Scanner un QR code ».

use App\Models\User;
use App\Models\Vetements\CategorieVetement;
use App\Models\Vetements\Matiere;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->lina = User::firstWhere('email', 'lina.martin@example.com');
});

test('la fiche d’un vêtement avec composition affiche son passeport textile en QR code', function () {
    $vetement = $this->lina->vetements()->create([
        'nom' => 'Veste en jean', 'categorie_id' => CategorieVetement::firstWhere('libelle', 'Haut')->id, 'etat' => 'bon', 'statut' => 'disponible', 'poids_grammes' => 650,
    ]);
    $vetement->definirComposition([Matiere::firstWhere('nom', 'Coton')->id => 98, Matiere::firstWhere('nom', 'Élasthanne')->id => 2]);

    expect($vetement->refresh()->passeportTexte())
        ->toContain('Nom : Veste en jean')
        ->toContain('Composition : 98% Coton, 2% Élasthanne')
        ->toContain('Poids : 650 g');

    $this->actingAs($this->lina)->get(route('vetements.show', $vetement))
        ->assertOk()->assertSee('Passeport textile')->assertSee('<svg', false)->assertSee('passeport-veste-en-jean.svg');
});

test('sans composition, pas de passeport textile', function () {
    $vetement = $this->lina->vetements()->create([
        'nom' => 'Pull sans étiquette', 'categorie_id' => CategorieVetement::firstWhere('libelle', 'Haut')->id, 'etat' => 'bon', 'statut' => 'disponible',
    ]);

    expect($vetement->passeportTexte())->toBeNull();
    $this->actingAs($this->lina)->get(route('vetements.show', $vetement))->assertOk()->assertDontSee('Passeport textile');
});

test('le formulaire propose de scanner un QR code', function () {
    $this->actingAs($this->lina)->get(route('vetements.create'))
        ->assertOk()->assertSee('Scanner un QR code')->assertSee('data-scan-qr', false)->assertSee('qr-passeport-textile.png');
});
