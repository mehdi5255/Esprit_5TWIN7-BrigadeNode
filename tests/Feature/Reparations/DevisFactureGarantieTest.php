<?php

// Module 2 — Parcours devis → facture → garantie d'une réparation, joué comme un utilisateur.

use App\Models\Reparations\DemandeReparation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->lina = User::firstWhere('email', 'lina.martin@example.com');
    $this->filRouge = User::firstWhere('email', 'contact@filrouge.fr');
    // Demandes des vêtements de Lina (les vêtements générés par factory peuvent porter le même nom).
    $this->demandeDe = fn (string $vetement) => DemandeReparation::where('particulier_id', $this->lina->id)
        ->whereHas('vetement', fn ($q) => $q->where('nom', $vetement))->latest('id')->firstOrFail();
});

test('devis, facture puis garantie, jusqu’au retour sous garantie', function () {
    $demande = ($this->demandeDe)('Veste en velours');
    expect($demande->statut->value)->toBe('en_attente');

    // L'atelier accepte simplement la demande : pas de devis sans avoir vu le vêtement.
    $this->actingAs($this->filRouge)->post(route('atelier.reparations.accepter', $demande))->assertSessionHasNoErrors();
    $demande->refresh();
    expect($demande->statut->value)->toBe('acceptee')->and($demande->devis)->toBeNull();

    // Pas de devis avant le dépôt.
    $this->actingAs($this->filRouge)->post(route('atelier.reparations.devis.etablir', $demande), [
        'diagnostic' => 'Ourlet décousu.', 'main_oeuvre' => 15, 'delai_jours' => 3, 'valide_jusqu_au' => now()->addDays(10)->toDateString(),
    ])->assertSessionHas('error');
    expect($demande->refresh()->devis)->toBeNull();

    // Dépôt, puis diagnostic + devis ; le diagnostic est obligatoire.
    $this->actingAs($this->filRouge)->post(route('atelier.reparations.depot', $demande))->assertSessionHas('success');
    $this->actingAs($this->filRouge)->post(route('atelier.reparations.devis.etablir', $demande), ['main_oeuvre' => 15])
        ->assertSessionHasErrors('diagnostic');
    $this->actingAs($this->filRouge)->post(route('atelier.reparations.devis.etablir', $demande), [
        'diagnostic' => 'Ourlet décousu sur 10 cm.', 'main_oeuvre' => 15, 'delai_jours' => 3, 'valide_jusqu_au' => now()->addDays(10)->toDateString(),
    ])->assertSessionHas('success');
    $demande->refresh();
    expect($demande->statut->value)->toBe('depot_remis')
        ->and($demande->reparation->diagnostic)->toBe('Ourlet décousu sur 10 cm.')
        ->and($demande->devis->statut->value)->toBe('propose')
        ->and((float) $demande->devis->main_oeuvre)->toBe(15.0);

    // Pas de démarrage tant que le devis n'est pas accepté.
    $this->actingAs($this->filRouge)->post(route('atelier.reparations.demarrer', $demande))->assertSessionHas('error');
    expect($demande->refresh()->statut->value)->toBe('depot_remis');

    // L'atelier corrige son devis, puis le particulier l'accepte (une seule fois).
    $this->actingAs($this->filRouge)->put(route('atelier.reparations.devis', $demande), [
        'main_oeuvre' => 16, 'delai_jours' => 3, 'valide_jusqu_au' => now()->addDays(10)->toDateString(),
    ])->assertSessionHasNoErrors();
    $this->actingAs($this->lina)->post(route('reparations.devis.accepter', $demande))->assertSessionHas('success');
    $this->actingAs($this->lina)->post(route('reparations.devis.accepter', $demande))->assertSessionHas('error');
    expect($demande->refresh()->devis->statut->value)->toBe('accepte');

    // Démarrage, puis fin : un supplément sans motif est refusé.
    $this->actingAs($this->filRouge)->post(route('atelier.reparations.demarrer', $demande))->assertSessionHas('success');
    expect($demande->refresh()->statut->value)->toBe('en_traitement');
    $this->actingAs($this->filRouge)->post(route('atelier.reparations.terminer', $demande), ['actions_realisees' => 'Ourlet repris.', 'supplement' => 2])
        ->assertSessionHasErrors('motif_supplement');
    $this->actingAs($this->filRouge)->post(route('atelier.reparations.terminer', $demande), [
        'actions_realisees' => 'Ourlet repris.', 'supplement' => 2, 'motif_supplement' => 'Galon de renfort ajouté.',
    ])->assertSessionHasNoErrors();
    $demande->refresh();
    expect($demande->statut->value)->toBe('pret')
        ->and($demande->facture->statut->value)->toBe('emise')
        ->and((float) $demande->facture->montant_total)->toBe(18.0)
        ->and((float) $demande->cout_final)->toBe(18.0);

    // La facture est visible par son particulier et son atelier seulement.
    $this->actingAs($this->lina)->get(route('reparations.facture', $demande))->assertOk()->assertSee($demande->facture->numero())->assertSee('18,00 €');
    $this->actingAs($this->filRouge)->get(route('atelier.reparations.facture', $demande))->assertOk();
    $this->actingAs(User::firstWhere('email', 'noah.bernard@example.com'))->get(route('reparations.facture', $demande))->assertForbidden();

    // Paiement et récupération : facture payée, garantie de 3 mois.
    $this->actingAs($this->filRouge)->post(route('atelier.reparations.recuperation', $demande))->assertSessionHas('success');
    $demande->refresh();
    $garantie = $demande->garantieAccordee();
    expect($demande->statut->value)->toBe('recupere')
        ->and($demande->facture->statut->value)->toBe('payee')
        ->and($garantie->estActive())->toBeTrue()
        ->and($garantie->date_fin->toDateString())->toBe(now()->addMonths(3)->toDateString());

    // Retour sous garantie : nouvelle demande gratuite, chez le même atelier.
    $this->actingAs($this->lina)->post(route('reparations.garantie.retour', $garantie), ['description' => 'L’ourlet s’est de nouveau défait.'])
        ->assertSessionHasNoErrors();
    $retour = $garantie->retours()->firstOrFail();
    expect($retour->atelier_id)->toBe($demande->atelier_id)->and($retour->statut->value)->toBe('en_attente');

    // L'atelier prend en charge le retour : au diagnostic, devis à 0 € accepté d'office ; un supplément est ignoré, aucune nouvelle garantie.
    $this->actingAs($this->filRouge)->post(route('atelier.reparations.accepter', $retour))->assertSessionHasNoErrors();
    $this->actingAs($this->filRouge)->post(route('atelier.reparations.depot', $retour))->assertSessionHas('success');
    $this->actingAs($this->filRouge)->post(route('atelier.reparations.devis.etablir', $retour), ['diagnostic' => 'Même ourlet.'])->assertSessionHas('success');
    expect($retour->refresh()->devis->statut->value)->toBe('accepte')->and((float) $retour->devis->main_oeuvre)->toBe(0.0);
    $this->actingAs($this->filRouge)->post(route('atelier.reparations.demarrer', $retour));
    $this->actingAs($this->filRouge)->post(route('atelier.reparations.terminer', $retour), ['actions_realisees' => 'Ourlet refait.', 'supplement' => 5, 'motif_supplement' => 'Test']);
    $this->actingAs($this->filRouge)->post(route('atelier.reparations.recuperation', $retour));
    $retour->refresh();
    expect((float) $retour->facture->montant_total)->toBe(0.0)
        ->and($retour->garantieAccordee())->toBeNull()
        ->and($garantie->retours()->count())->toBe(1);

    // Pages de suivi et liste des factures.
    $this->actingAs($this->lina)->get(route('reparations.show', $demande))->assertOk()->assertSee('Garantie 3 mois')->assertSee($retour->reference());
    $this->actingAs($this->filRouge)->get(route('atelier.reparations.show', $retour))->assertOk()->assertSee('Retour sous garantie');
    $this->actingAs($this->filRouge)->get(route('atelier.factures.index', ['statut' => 'payee']))->assertOk()->assertSee($demande->facture->numero());
});

test('le particulier refuse le devis : la demande est annulée et le vêtement libéré', function () {
    $demande = ($this->demandeDe)('Chemise en lin');
    expect($demande->devis->statut->value)->toBe('propose');

    $this->actingAs($this->lina)->get(route('reparations.show', $demande))->assertOk()->assertSee('Accepter le devis');
    $this->actingAs($this->lina)->post(route('reparations.devis.refuser', $demande))->assertSessionHas('success');

    $demande->refresh();
    expect($demande->statut->value)->toBe('annulee')
        ->and($demande->devis->statut->value)->toBe('refuse')
        ->and($demande->vetement->statut->value)->toBe('disponible');
});

test('un devis expiré ne peut plus être accepté', function () {
    $demande = ($this->demandeDe)('Chemise en lin');
    $demande->devis->update(['valide_jusqu_au' => now()->subDay()]);

    $this->actingAs($this->lina)->post(route('reparations.devis.accepter', $demande))->assertSessionHas('error');
    expect($demande->refresh()->devis->statut->value)->toBe('propose');
});

test('la page Dépôts attend les réparations acceptées, pas celles déjà déposées', function () {
    $this->actingAs($this->filRouge)->get(route('atelier.depots.index'))->assertOk();
    $velours = ($this->demandeDe)('Veste en velours');
    $velours->accepter();
    $chemise = ($this->demandeDe)('Chemise en lin');

    $attendus = \App\Support\Depots::pour($this->filRouge)->attendus()->pluck('demande.id');
    expect($attendus)->toContain($velours->id)->not->toContain($chemise->id);
});

test('aide au chiffrage : estimation à partir des devis passés comparables', function () {
    $demande = DemandeReparation::where('atelier_id', $this->filRouge->atelier->id)->where('statut', 'depot_remis')
        ->whereDoesntHave('devis')->firstOrFail();

    $this->actingAs($this->filRouge)->get(route('atelier.reparations.show', $demande))->assertOk()->assertSee('Estimer le montant');

    $estimation = $this->actingAs($this->filRouge)->post(route('atelier.reparations.estimation', $demande), [
        'diagnostic' => 'Fermeture éclair cassée, curseur à remplacer.',
    ])->assertOk()->json();

    expect($estimation['base'])->toBe('historique')
        ->and($estimation['montant'])->toBeGreaterThan(15)->toBeLessThan(40)
        ->and($estimation['comparables'])->not->toBeEmpty()
        ->and(collect($estimation['comparables'])->pluck('atelier'))->toContain('Votre atelier');

    // Avec une clé Gemini, l'IA affine le montant et l'explique (réponse simulée : aucun appel réel en test).
    config(['services.gemini.key' => 'cle-de-test']);
    \Illuminate\Support\Facades\Http::fakeSequence('generativelanguage.googleapis.com/*')
        ->push(['candidates' => [['content' => ['parts' => [['text' => json_encode(['montant' => 24.3, 'min' => 20, 'max' => 30, 'explication' => 'Remplacement du curseur, proche de vos devis.'])]]]]]])
        ->push('quota', 429);
    $ia = $this->actingAs($this->filRouge)->post(route('atelier.reparations.estimation', $demande), ['diagnostic' => 'Fermeture éclair cassée.'])->json();
    expect($ia['base'])->toBe('gemini')->and($ia['montant'])->toEqual(24.5)
        ->and($ia['explication'])->toContain('curseur')->and($ia['comparables'])->not->toBeEmpty();
    \Illuminate\Support\Facades\Http::assertSent(fn ($requete) => str_contains($question = $requete['contents'][0]['parts'][0]['text'], 'Fermeture éclair cassée.')
        && ! str_contains($question, 'Fil Rouge'));

    // Gemini en panne : on retombe sur l'estimation statistique.
    $secours = $this->actingAs($this->filRouge)->post(route('atelier.reparations.estimation', $demande), ['diagnostic' => 'Fermeture éclair cassée.'])->json();
    expect($secours['base'])->toBe('historique')->and($secours['montant'])->toBeGreaterThan(0);

    // Un autre atelier ne peut pas estimer les demandes de Fil Rouge.
    $autre = User::where('role', 'atelier')->whereKeyNot($this->filRouge->id)->firstOrFail();
    $this->actingAs($autre)->post(route('atelier.reparations.estimation', $demande), ['diagnostic' => 'x'])->assertForbidden();
});
