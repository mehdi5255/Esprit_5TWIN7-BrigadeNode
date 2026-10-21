<?php

// Module 4 — Événements : CRUD de l'association, agenda public, dépôt à une collecte, remise lors d'une distribution.

use App\Models\Dons\DemandeDon;
use App\Models\Dons\Evenement;
use App\Models\User;
use App\Models\Vetements\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->vestiaire = User::firstWhere('email', 'contact@vestiaire-solidaire.org');
    $this->enfance = User::firstWhere('email', 'hugo@enfance-partage.org');
    $this->lina = User::firstWhere('email', 'lina.martin@example.com');
    $this->hiver = Evenement::firstWhere('titre', 'Grande collecte d’hiver');
    $this->maraude = Evenement::firstWhere('titre', 'Maraude du jeudi soir');
});

test('l’agenda public montre les événements à venir et non annulés', function () {
    $this->get(route('evenements.index'))->assertOk()->assertSee('Grande collecte d’hiver')->assertSee('Maraude du jeudi soir')
        ->assertDontSee('Collecte au marché')->assertDontSee('Collecte de rentrée');
    $this->get(route('evenements.index', ['type' => 'distribution']))->assertOk()->assertSee('Maraude du jeudi soir')->assertDontSee('Grande collecte d’hiver');
    $this->get(route('evenements.show', $this->hiver))->assertOk()->assertSee('place(s) restante(s)')->assertSee('Se connecter pour proposer un don');
    $this->get(route('associations.show', $this->hiver->association))->assertOk()->assertSee('Prochains rendez-vous');
});

test('l’association gère ses événements (CRUD complet)', function () {
    $this->actingAs($this->vestiaire)->get(route('association.evenements.index'))->assertOk()->assertSee('Grande collecte d’hiver');

    // Validation : dates incohérentes ou passées.
    $this->actingAs($this->vestiaire)->post(route('association.evenements.store'), [
        'type' => 'collecte', 'titre' => 'Test', 'lieu' => 'Ici', 'debut' => now()->subDay()->format('Y-m-d\TH:i'), 'fin' => now()->subDays(2)->format('Y-m-d\TH:i'),
    ])->assertSessionHasErrors(['debut', 'fin']);

    $this->actingAs($this->vestiaire)->post(route('association.evenements.store'), [
        'type' => 'distribution', 'titre' => 'Vestiaire de printemps', 'lieu' => 'Salle des fêtes', 'places' => 12,
        'debut' => now()->addDays(15)->setTime(10, 0)->format('Y-m-d\TH:i'), 'fin' => now()->addDays(15)->setTime(13, 0)->format('Y-m-d\TH:i'),
    ])->assertSessionHasNoErrors();
    $nouveau = Evenement::firstWhere('titre', 'Vestiaire de printemps');
    expect($nouveau->places)->toBeNull();   // pas de places pour une distribution

    $this->actingAs($this->vestiaire)->get(route('association.evenements.show', $nouveau))->assertOk();
    $this->actingAs($this->vestiaire)->put(route('association.evenements.update', $nouveau), [
        'type' => 'distribution', 'titre' => 'Vestiaire de printemps (salle B)', 'lieu' => 'Salle des fêtes',
        'debut' => $nouveau->debut->format('Y-m-d\TH:i'), 'fin' => $nouveau->fin->format('Y-m-d\TH:i'),
    ])->assertSessionHasNoErrors();
    expect($nouveau->refresh()->titre)->toBe('Vestiaire de printemps (salle B)');

    // On ne descend pas sous le nombre de dons déjà rattachés (le pull de Lina est rattaché à la collecte d'hiver).
    $this->actingAs($this->vestiaire)->put(route('association.evenements.update', $this->hiver), [
        'type' => 'collecte', 'titre' => $this->hiver->titre, 'lieu' => $this->hiver->lieu, 'places' => 0,
        'debut' => $this->hiver->debut->format('Y-m-d\TH:i'), 'fin' => $this->hiver->fin->format('Y-m-d\TH:i'),
    ])->assertSessionHasErrors('places');

    // Suppression : refusée si des dons sont rattachés, acceptée sinon.
    $this->actingAs($this->vestiaire)->delete(route('association.evenements.destroy', $this->hiver))->assertSessionHas('error');
    $this->actingAs($this->vestiaire)->delete(route('association.evenements.destroy', $nouveau))->assertSessionHas('success');
    expect(Evenement::find($nouveau->id))->toBeNull();

    // Une autre association n'y a pas accès.
    $this->actingAs($this->enfance)->get(route('association.evenements.edit', $this->hiver))->assertForbidden();
});

test('le don passe par une collecte puis une distribution (N-N)', function () {
    $manteau = Vetement::firstWhere('nom', 'Manteau camel');

    // Depuis l'agenda : association et collecte présélectionnées.
    $this->actingAs($this->lina)->get(route('dons.create', ['collecte' => $this->hiver->id]))->assertOk()->assertSee('Collecte : Grande collecte d’hiver');

    // Une collecte d'une autre association est refusée.
    $bourse = Evenement::firstWhere('titre', 'Bourse aux vêtements enfants');
    $this->actingAs($this->lina)->post(route('dons.store'), ['vetement_id' => $manteau->id, 'association_id' => $this->hiver->association_id, 'collecte_id' => $bourse->id])
        ->assertSessionHasErrors('collecte_id');

    $this->actingAs($this->lina)->post(route('dons.store'), ['vetement_id' => $manteau->id, 'association_id' => $this->hiver->association_id, 'collecte_id' => $this->hiver->id])
        ->assertSessionHasNoErrors();
    $don = DemandeDon::where('vetement_id', $manteau->id)->latest('id')->firstOrFail();
    expect($don->collecte()?->id)->toBe($this->hiver->id);
    $this->actingAs($this->lina)->get(route('dons.show', $don))->assertOk()->assertSee('Dépôt prévu à la collecte');

    // L'association accepte puis marque le don « Reçu » depuis la page de la collecte.
    $this->actingAs($this->vestiaire)->post(route('association.dons.accepter', $don));
    $this->actingAs($this->vestiaire)->get(route('association.evenements.show', $this->hiver))->assertOk()->assertSee($don->reference())->assertSee('Reçu');
    $this->actingAs($this->vestiaire)->post(route('association.dons.depot', $don), ['evenement' => $this->hiver->id])
        ->assertRedirect(route('association.evenements.show', $this->hiver));
    expect($don->refresh()->depot_remis)->toBeTrue();

    // Remise : une collecte n'est pas une distribution ; la maraude, si.
    $this->actingAs($this->vestiaire)->post(route('association.dons.preparer', $don));
    $this->actingAs($this->vestiaire)->post(route('association.dons.remettre', $don), ['beneficiaire_info' => 'Maraude', 'nombre_beneficiaires' => 2, 'distribution_id' => $this->hiver->id])
        ->assertSessionHasErrors('distribution_id');
    expect($don->refresh()->statut->value)->toBe('en_traitement');
    $this->actingAs($this->vestiaire)->post(route('association.dons.remettre', $don), ['beneficiaire_info' => 'Maraude', 'nombre_beneficiaires' => 2, 'distribution_id' => $this->maraude->id])
        ->assertSessionHas('success');

    $don->refresh();
    expect($don->statut->value)->toBe('remis_beneficiaire')
        ->and($don->evenements->pluck('id')->sort()->values()->all())->toBe(collect([$this->hiver->id, $this->maraude->id])->sort()->values()->all());
    $this->actingAs($this->vestiaire)->get(route('association.evenements.show', $this->maraude))->assertOk()->assertSee($don->reference());
    $this->actingAs($this->lina)->get(route('dons.show', $don))->assertOk()->assertSee('Remis lors de la distribution');
});

test('une collecte complète refuse les nouveaux dons', function () {
    $express = Evenement::firstWhere('titre', 'Collecte express du mercredi');
    $express->update(['places' => 1]);
    DemandeDon::factory()->for(Vetement::factory())->create(['association_id' => $express->association_id])->evenements()->attach($express);

    $manteau = Vetement::firstWhere('nom', 'Manteau camel');
    $this->actingAs($this->lina)->post(route('dons.store'), ['vetement_id' => $manteau->id, 'association_id' => $express->association_id, 'collecte_id' => $express->id])
        ->assertSessionHas('error', 'Cette collecte est complète.');
    expect(DemandeDon::where('vetement_id', $manteau->id)->exists())->toBeFalse();
});

test('annuler une collecte libère les dons et prévient les particuliers', function () {
    $pull = DemandeDon::whereHas('vetement', fn ($q) => $q->where('nom', 'Pull irlandais'))->firstOrFail();
    expect($pull->collecte()?->id)->toBe($this->hiver->id);

    $this->actingAs($this->vestiaire)->post(route('association.evenements.annuler', $this->hiver))->assertSessionHas('success');

    expect($this->hiver->refresh()->annule)->toBeTrue()
        ->and($pull->refresh()->collecte())->toBeNull()
        ->and($this->lina->notifications()->where('data->message', 'like', '%est annulée%')->exists())->toBeTrue();
    $this->get(route('evenements.index'))->assertDontSee('Grande collecte d’hiver');
});
