<?php

// Module 3 — Inspirations : CRUD de l'atelier, galerie publique, suggestion et demande préremplie.

use App\Models\Transformations\DemandeTransformation;
use App\Models\Transformations\Inspiration;
use App\Models\User;
use App\Models\Vetements\CategorieVetement;
use App\Models\Vetements\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->maya = User::firstWhere('email', 'maya@studiorefil.fr');        // Studio Refil : propose la transformation
    $this->filRouge = User::firstWhere('email', 'contact@filrouge.fr');    // réparation seulement
    $this->lina = User::firstWhere('email', 'lina.martin@example.com');
    $this->gilet = Inspiration::firstWhere('titre', 'Manteau → Gilet sans manches');
});

test('la galerie publique ne montre que les inspirations visibles', function () {
    $masquee = Inspiration::firstWhere('titre', 'Pull → Coussin');

    $this->get(route('inspirations.index'))->assertOk()->assertSee('Jean → Sac cabas')->assertSee($this->gilet->titre)->assertDontSee('Pull → Coussin');
    $this->get(route('inspirations.index', ['categorie' => $this->gilet->categorie_id]))->assertOk()->assertSee($this->gilet->titre)->assertDontSee('Jean → Sac cabas');
    $this->get(route('inspirations.show', $this->gilet))->assertOk()->assertSee('Studio Refil')->assertSee('Se connecter pour demander ce projet');
    $this->get(route('inspirations.show', $masquee))->assertNotFound();
    $this->get(route('ateliers.show', $this->gilet->atelier))->assertOk()->assertSee('Ses inspirations');
});

test('l’atelier gère ses inspirations (CRUD complet)', function () {
    $this->actingAs($this->maya)->get(route('atelier.inspirations.index'))->assertOk()->assertSee($this->gilet->titre);

    // Validation : titre, description et catégorie obligatoires.
    $this->actingAs($this->maya)->post(route('atelier.inspirations.store'), [])->assertSessionHasErrors(['titre', 'description', 'categorie_id']);

    $categorie = CategorieVetement::firstWhere('libelle', 'Robe');
    $this->actingAs($this->maya)->post(route('atelier.inspirations.store'), [
        'titre' => 'Robe → Blouse', 'categorie_id' => $categorie->id, 'description' => 'Le bas de la robe est retiré.', 'prix_indicatif' => 30, 'actif' => 1,
    ])->assertSessionHasNoErrors();
    $nouvelle = Inspiration::firstWhere('titre', 'Robe → Blouse');
    expect($nouvelle->atelier_id)->toBe($this->maya->atelier->id);

    $this->actingAs($this->maya)->get(route('atelier.inspirations.show', $nouvelle))->assertOk();
    $this->actingAs($this->maya)->get(route('atelier.inspirations.edit', $nouvelle))->assertOk()->assertSee('Robe → Blouse');
    $this->actingAs($this->maya)->put(route('atelier.inspirations.update', $nouvelle), [
        'titre' => 'Robe → Blouse fluide', 'categorie_id' => $categorie->id, 'description' => 'Le bas de la robe est retiré.', 'actif' => 1,
    ])->assertSessionHasNoErrors();
    expect($nouvelle->refresh()->titre)->toBe('Robe → Blouse fluide');

    // Masquer puis supprimer.
    $this->actingAs($this->maya)->post(route('atelier.inspirations.basculer', $nouvelle));
    expect($nouvelle->refresh()->actif)->toBeFalse();
    $this->get(route('inspirations.show', $nouvelle))->assertNotFound();
    $this->actingAs($this->maya)->delete(route('atelier.inspirations.destroy', $nouvelle));
    expect(Inspiration::find($nouvelle->id))->toBeNull();
});

test('seuls les ateliers qui font de la transformation publient, chacun ses inspirations', function () {
    $this->actingAs($this->filRouge)->get(route('atelier.inspirations.index'))->assertOk()->assertSee('réservées aux ateliers qui proposent la transformation');
    $this->actingAs($this->filRouge)->get(route('atelier.inspirations.create'))->assertForbidden();
    $this->actingAs($this->filRouge)->get(route('atelier.inspirations.edit', $this->gilet))->assertForbidden();
    $this->actingAs($this->filRouge)->delete(route('atelier.inspirations.destroy', $this->gilet))->assertForbidden();
});

test('le particulier demande un projet à partir d’une inspiration', function () {
    // Depuis la galerie : le formulaire présélectionne le seul vêtement compatible (le manteau).
    $manteau = Vetement::firstWhere('nom', 'Manteau camel');
    $this->actingAs($this->lina)->get(route('transformations.create', ['inspiration' => $this->gilet->id]))
        ->assertOk()->assertSee($this->gilet->titre)->assertSee('value="'.$manteau->id.'"', false);

    // Mauvaise catégorie : refusé avec un message clair.
    $jupe = Vetement::factory()->for($this->lina)->create(['nom' => 'Jupe plissée', 'categorie_id' => CategorieVetement::firstWhere('libelle', 'Bas')->id]);
    $this->actingAs($this->lina)->post(route('transformations.store'), ['vetement_id' => $jupe->id, 'inspiration_id' => $this->gilet->id])
        ->assertSessionHasErrors('inspiration_id');

    // Bonne catégorie : atelier et idée viennent du modèle.
    $this->actingAs($this->lina)->post(route('transformations.store'), ['vetement_id' => $manteau->id, 'inspiration_id' => $this->gilet->id, 'budget_max' => 50])
        ->assertSessionHasNoErrors();
    $demande = DemandeTransformation::where('vetement_id', $manteau->id)->latest('id')->firstOrFail();
    expect($demande->inspiration_id)->toBe($this->gilet->id)
        ->and($demande->atelier_id)->toBe($this->gilet->atelier_id)
        ->and($demande->idee)->toContain('Gilet sans manches');

    $this->actingAs($this->lina)->get(route('transformations.show', $demande))->assertOk()->assertSee('Projet inspiré de')->assertSee($this->gilet->titre);
    $this->actingAs($this->maya)->get(route('atelier.transformations.show', $demande))->assertOk()->assertSee('Projet inspiré de');
    $this->actingAs($this->maya)->get(route('atelier.inspirations.show', $this->gilet))->assertOk()->assertSee($demande->reference());

    // Supprimer l'inspiration ne supprime pas la demande : elle redevient « idée libre ».
    $this->actingAs($this->maya)->delete(route('atelier.inspirations.destroy', $this->gilet));
    expect($demande->refresh()->exists)->toBeTrue()->and($demande->inspiration_id)->toBeNull();
});

test('l’idée libre fonctionne toujours, avec atelier et idée obligatoires', function () {
    $jupe = Vetement::factory()->for($this->lina)->create(['nom' => 'Jupe plissée', 'categorie_id' => CategorieVetement::firstWhere('libelle', 'Bas')->id]);

    $this->actingAs($this->lina)->post(route('transformations.store'), ['vetement_id' => $jupe->id])
        ->assertSessionHasErrors(['atelier_id', 'idee']);
    $this->actingAs($this->lina)->post(route('transformations.store'), ['vetement_id' => $jupe->id, 'atelier_id' => $this->maya->atelier->id, 'idee' => 'En faire un short.'])
        ->assertSessionHasNoErrors();
    expect(DemandeTransformation::where('vetement_id', $jupe->id)->first()->inspiration_id)->toBeNull();
});
