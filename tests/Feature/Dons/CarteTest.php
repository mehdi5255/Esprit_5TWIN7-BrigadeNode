<?php

// Module 4 — Lieu des événements sur la carte (OpenStreetMap) : placé par l'association, vu par le particulier.

use App\Models\Dons\Evenement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->vestiaire = User::firstWhere('email', 'contact@vestiaire-solidaire.org');
    $this->lina = User::firstWhere('email', 'lina.martin@example.com');
});

test('l’association cherche une adresse et retrouve l’adresse d’un point cliqué (Nominatim simulé)', function () {
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([
            ['display_name' => 'Place Jean Jaurès, Montreuil, France', 'lat' => '48.862186', 'lon' => '2.4421691'],
        ]),
        'nominatim.openstreetmap.org/reverse*' => Http::response(['display_name' => '1 Place Jean Jaurès, Montreuil, France']),
    ]);

    $this->actingAs($this->vestiaire)->getJson(route('association.geocodage.rechercher', ['q' => 'Place Jean Jaurès Montreuil']))
        ->assertOk()->assertJson([['adresse' => 'Place Jean Jaurès, Montreuil, France', 'latitude' => 48.862186, 'longitude' => 2.4421691]]);
    $this->actingAs($this->vestiaire)->getJson(route('association.geocodage.adresse', ['lat' => 48.862186, 'lng' => 2.4421691]))
        ->assertOk()->assertJson(['adresse' => '1 Place Jean Jaurès, Montreuil, France']);

    // Application identifiée auprès de Nominatim, comme l'exigent ses règles d'usage.
    Http::assertSent(fn ($requete) => str_starts_with($requete->header('User-Agent')[0] ?? '', 'TextileCycle/'));
    // Même recherche : servie par le cache, sans nouvel appel.
    $this->actingAs($this->vestiaire)->getJson(route('association.geocodage.rechercher', ['q' => 'Place Jean Jaurès Montreuil']))->assertOk();
    Http::assertSentCount(2);

    // Réservé aux associations ; coordonnées hors limites refusées.
    $this->actingAs($this->lina)->getJson(route('association.geocodage.rechercher', ['q' => 'Montreuil']))->assertForbidden();
    $this->actingAs($this->vestiaire)->getJson(route('association.geocodage.adresse', ['lat' => 120, 'lng' => 2]))->assertUnprocessable();
});

test('le lieu placé sur la carte est enregistré et affiché au particulier avec l’itinéraire', function () {
    $donnees = [
        'type' => 'collecte', 'titre' => 'Collecte de printemps', 'lieu' => 'Place Jean Jaurès, Montreuil',
        'debut' => now()->addDays(3)->setTime(10, 0)->format('Y-m-d\TH:i'), 'fin' => now()->addDays(3)->setTime(16, 0)->format('Y-m-d\TH:i'),
    ];

    // Les deux coordonnées vont ensemble.
    $this->actingAs($this->vestiaire)->post(route('association.evenements.store'), $donnees + ['latitude' => 48.862186])->assertSessionHasErrors('longitude');

    $this->actingAs($this->vestiaire)->post(route('association.evenements.store'), $donnees + ['latitude' => 48.862186, 'longitude' => 2.4421691])
        ->assertSessionHasNoErrors();
    $evenement = Evenement::firstWhere('titre', 'Collecte de printemps');
    expect($evenement->estLocalise())->toBeTrue()->and($evenement->latitude)->toBe(48.862186);

    // Page publique : carte et itinéraire Google Maps vers la position exacte.
    $this->actingAs($this->lina)->get(route('evenements.show', $evenement))->assertOk()
        ->assertSee('data-carte-lieu', false)->assertSee('leaflet', false)
        ->assertSee('https://www.google.com/maps/dir/?api=1&amp;destination=48.862186%2C2.4421691', false);

    // Liste des événements : le rendez-vous apparaît sur la carte d'ensemble.
    $this->get(route('evenements.index'))->assertOk()->assertSee('data-carte-evenements', false)->assertSee('Collecte de printemps');

    // L'association peut retirer la position : la page n'affiche plus de carte, seulement l'itinéraire vers l'adresse.
    $this->actingAs($this->vestiaire)->put(route('association.evenements.update', $evenement), ['latitude' => null, 'longitude' => null] + $donnees)->assertSessionHasNoErrors();
    expect($evenement->refresh()->estLocalise())->toBeFalse();
    $this->get(route('evenements.show', $evenement))->assertOk()->assertDontSee('data-carte-lieu', false)->assertSee('Itinéraire vers');
});

test('le formulaire de l’association propose la carte', function () {
    $this->actingAs($this->vestiaire)->get(route('association.evenements.create'))->assertOk()
        ->assertSee('Chercher sur la carte')->assertSee('data-choix-lieu', false);
});
