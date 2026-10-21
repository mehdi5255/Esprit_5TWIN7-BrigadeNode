<?php

// Utilisateurs — Rôles et permissions (Spatie Permission), contrôlés côté serveur.

use App\Models\Transformations\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->admin = User::firstWhere('email', 'admin@textilecycle.fr');
    $this->lina = User::firstWhere('email', 'lina.martin@example.com');
    $this->filRouge = User::firstWhere('email', 'contact@filrouge.fr');
});

test('chaque compte a le rôle Spatie de son type de compte, avec ses permissions', function () {
    expect($this->admin->hasRole('admin'))->toBeTrue()
        ->and($this->admin->can('gerer-roles'))->toBeTrue()
        ->and($this->filRouge->hasRole('atelier'))->toBeTrue()
        ->and($this->filRouge->can('gerer-services'))->toBeTrue()
        ->and($this->filRouge->can('voir-rapports'))->toBeFalse()
        ->and($this->lina->getRoleNames()->all())->toBe(['particulier']);
});

test('les rôles sont vérifiés côté serveur, pas seulement dans l’interface', function () {
    $this->actingAs($this->lina)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($this->lina)->get(route('atelier.services.index'))->assertForbidden();
    $this->actingAs($this->filRouge)->get(route('vetements.index'))->assertForbidden();
    $this->actingAs($this->filRouge)->get(route('admin.rapports.index'))->assertForbidden()->assertSee('Vous n’avez pas accès à cette page.', false);
    // Requête forgée vers une action réservée : refusée aussi.
    $this->actingAs($this->lina)->post(route('admin.users.bloquer', $this->filRouge))->assertForbidden();
});

test('un modérateur n’accède qu’aux parties de l’administration permises par son rôle', function () {
    $this->actingAs($this->admin)->post(route('admin.roles.nommer'), ['email' => $this->lina->email])->assertSessionHas('success');
    $lina = $this->lina->refresh();
    expect($lina->role)->toBe('moderateur')->and($lina->hasRole('moderateur'))->toBeTrue()->and($lina->homeRoute())->toBe(route('admin.dashboard'));

    $this->actingAs($lina)->get(route('admin.dashboard'))->assertOk()->assertSee('Avis')->assertDontSee('Utilisateurs</a>', false);
    $this->actingAs($lina)->get(route('admin.reviews.index'))->assertOk();
    $this->actingAs($lina)->get(route('admin.reparations.index'))->assertOk();
    foreach (['admin.users.index', 'admin.rapports.index', 'admin.roles.index', 'admin.categories.index', 'admin.ateliers.index'] as $route) {
        $this->actingAs($lina)->get(route($route))->assertForbidden();
    }

    // Seul un particulier actif peut être nommé ; le rôle se retire ensuite.
    $this->actingAs($this->admin)->post(route('admin.roles.nommer'), ['email' => $this->filRouge->email])->assertSessionHasErrors('email');
    $this->actingAs($this->admin)->delete(route('admin.roles.retirer', $lina))->assertSessionHas('success');
    expect($lina->refresh()->hasRole('particulier'))->toBeTrue();
    $this->actingAs($lina)->get(route('admin.dashboard'))->assertForbidden();
});

test('l’admin ajuste les permissions d’un rôle : l’accès change aussitôt', function () {
    $particulier = Role::findByName('particulier');
    $demande = $this->lina->demandesReparation()->where('statut', 'recupere')->whereDoesntHave('review')->first()
        ?? $this->lina->demandesReparation()->where('statut', 'recupere')->firstOrFail();
    Review::where('reviewable_type', 'reparation')->where('reviewable_id', $demande->id)->delete();

    $this->actingAs($this->admin)->get(route('admin.roles.index'))->assertOk()->assertSee('Publier des avis')->assertSee('Modérateurs');
    $this->actingAs($this->admin)->put(route('admin.roles.update', $particulier), ['permissions' => []])->assertSessionHas('success');

    // Le particulier ne peut plus publier d'avis (vérifié par le middleware permission:).
    $this->actingAs($this->lina->refresh())->post(route('avis.store', ['reparations', $demande->id]), ['note' => 5, 'commentaire' => 'Très bien'])->assertForbidden();

    $this->actingAs($this->admin)->put(route('admin.roles.update', $particulier), ['permissions' => ['publier-avis']]);
    $this->actingAs($this->lina->refresh())->post(route('avis.store', ['reparations', $demande->id]), ['note' => 5, 'commentaire' => 'Très bien'])->assertSessionHasNoErrors();

    // L'admin garde toujours tout ; une permission inconnue est refusée ; un non-admin ne gère pas les rôles.
    $this->actingAs($this->admin)->put(route('admin.roles.update', Role::findByName('admin')), ['permissions' => []])->assertSessionHas('error');
    expect($this->admin->refresh()->can('gerer-roles'))->toBeTrue();
    $this->actingAs($this->admin)->put(route('admin.roles.update', $particulier), ['permissions' => ['tout-faire']])->assertSessionHasErrors('permissions.0');
    $this->actingAs($this->filRouge)->put(route('admin.roles.update', $particulier), ['permissions' => []])->assertForbidden();
});
