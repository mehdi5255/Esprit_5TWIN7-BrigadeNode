<?php

// Module 4 — Contrôle qualité IA des dons : avant l'envoi (donateur) et à la réception (association). Gemini simulé.

use App\Models\Dons\DemandeDon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->lina = User::firstWhere('email', 'lina.martin@example.com');
    $this->vestiaire = User::firstWhere('email', 'contact@vestiaire-solidaire.org');
    $this->vetement = $this->lina->vetements()->where('statut', 'disponible')->firstOrFail();
    config(['services.gemini.key' => 'cle-de-test']);
});

function avisGemini(array $donnees): array
{
    return ['candidates' => [['content' => ['parts' => [['text' => json_encode($donnees)]]]]]];
}

test('le donateur fait vérifier son vêtement : à réparer, avec un lien vers le module réparation', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(avisGemini([
        'est_vetement' => true, 'verdict' => 'a_reparer', 'etat_observe' => 'Pull propre mais troué au coude.',
        'defauts' => ['Trou de 2 cm au coude', 'Bord effiloch\u00e9'], 'conseil' => 'Faites-le repriser : il fera un très beau don.',
    ]))]);

    $r = $this->actingAs($this->lina)->post(route('dons.controle'), [
        'vetement_id' => $this->vetement->id, 'photo' => UploadedFile::fake()->image('pull.jpg'),
    ])->assertOk()->json();

    expect($r['verdict'])->toBe('a_reparer')
        ->and($r['defauts'])->toBe(['Trou de 2 cm au coude', 'Bord effiloché'])   // accent codé par l'IA, remis en clair
        ->and($r['liens']['reparation'])->toBe(route('reparations.create', ['vetement' => $this->vetement->id]))
        ->and($r['liens']['transformation'])->toBe(route('transformations.idees', ['vetement' => $this->vetement->id]));

    // L'avis est gardé avec le don, à titre indicatif pour l'association ; une valeur inventée est refusée.
    $association = $this->vestiaire->association;
    $this->actingAs($this->lina)->post(route('dons.store'), ['vetement_id' => $this->vetement->id, 'association_id' => $association->id, 'controle_ia' => 'parfait'])
        ->assertSessionHasErrors('controle_ia');
    $this->actingAs($this->lina)->post(route('dons.store'), ['vetement_id' => $this->vetement->id, 'association_id' => $association->id, 'controle_ia' => 'donnable'])
        ->assertSessionHasNoErrors();
    $don = DemandeDon::latest('id')->firstOrFail();
    expect($don->controle_ia)->toBe('donnable');
    $this->actingAs($this->vestiaire)->get(route('association.dons.show', $don))->assertOk()->assertSee('Avis IA du donateur')->assertSee('Donnable');
});

test('sans IA ou sans vêtement reconnu, le donateur reçoit un message clair', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(avisGemini([
        'est_vetement' => false, 'verdict' => 'donnable', 'etat_observe' => '', 'defauts' => [], 'conseil' => '',
    ]))]);
    $this->actingAs($this->lina)->post(route('dons.controle'), ['vetement_id' => $this->vetement->id, 'photo' => UploadedFile::fake()->image('chat.jpg')])
        ->assertStatus(422)->assertJsonStructure(['erreur']);

    config(['services.gemini.key' => null]);
    $this->actingAs($this->lina)->post(route('dons.controle'), ['vetement_id' => $this->vetement->id])->assertStatus(503);
});

test('à la réception, l’IA propose une orientation que l’association valide', function () {
    $don = DemandeDon::where('statut', 'depot_remis')->whereHas('association.besoinsActifs')->firstOrFail();
    $association = $don->association;
    $responsable = $association->responsable;
    $besoin = $association->besoinsActifs()->firstOrFail();

    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(avisGemini([
        'est_vetement' => true, 'orientation' => 'redistribution', 'justification' => 'Propre et chaud, prêt à être porté.',
        'etat_observe' => 'Très bon état.', 'defauts' => [], 'besoin_id' => $besoin->id,
    ]))]);

    $this->actingAs($responsable)->get(route('association.dons.show', $don))->assertOk()->assertSee('Contrôle qualité et orientation');
    $r = $this->actingAs($responsable)->post(route('association.dons.orientation.analyse', $don), ['photo' => UploadedFile::fake()->image('recu.jpg')])
        ->assertOk()->json();
    expect($r['orientation'])->toBe('redistribution')->and($r['besoin']['id'])->toBe($besoin->id);

    // L'association valide (ou corrige) : l'orientation est enregistrée et visible du donateur.
    $this->actingAs($responsable)->post(route('association.dons.orientation', $don), ['orientation' => 'recyclage', 'orientation_motif' => 'Doublure déchirée.'])
        ->assertSessionHas('success');
    expect($don->refresh()->orientation->value)->toBe('recyclage');
    $this->actingAs($don->particulier)->get(route('dons.show', $don))->assertOk()->assertSee('Recyclage des fibres');

    // Une autre association ne peut ni analyser ni orienter ce don.
    $autre = User::where('role', 'association')->whereKeyNot($responsable->id)->whereHas('association')->firstOrFail();
    $this->actingAs($autre)->post(route('association.dons.orientation.analyse', $don))->assertForbidden();
    $this->actingAs($autre)->post(route('association.dons.orientation', $don), ['orientation' => 'boutique'])->assertForbidden();
});

test('un don pas encore reçu ne peut pas être orienté', function () {
    $don = DemandeDon::where('association_id', $this->vestiaire->association->id)->where('statut', 'en_attente')->firstOrFail();

    $this->actingAs($this->vestiaire)->post(route('association.dons.orientation', $don), ['orientation' => 'boutique'])->assertSessionHas('error');
    expect($don->refresh()->orientation)->toBeNull();
});

test('un vêtement d’une autre catégorie ne peut pas répondre au besoin, même si l’IA le juge donnable', function () {
    $chaussures = $this->vestiaire->association->besoins()->firstWhere('titre', 'Chaussures de marche');
    $pull = $this->lina->vetements()->where('statut', 'disponible')->where('categorie_id', '!=', $chaussures->categorie_id)->firstOrFail();

    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(avisGemini([
        'est_vetement' => true, 'verdict' => 'donnable', 'etat_observe' => 'Très bon état.', 'defauts' => [],
        'conseil' => 'Merci !', 'correspond_au_besoin' => true, 'remarque_besoin' => 'Convient.',
    ]))]);

    // L'IA reçoit le besoin, mais la règle de catégorie l'emporte.
    $r = $this->actingAs($this->lina)->post(route('dons.controle'), ['vetement_id' => $pull->id, 'besoin_id' => $chaussures->id, 'photo' => UploadedFile::fake()->image('pull.jpg')])
        ->assertOk()->json();
    expect($r['besoin']['correspond'])->toBeFalse()->and($r['besoin']['remarque'])->toContain('Chaussures');
    Http::assertSent(fn ($requete) => str_contains($requete['contents'][0]['parts'][1]['text'], 'Chaussures de marche'));

    // Et l'envoi du don est refusé avec ce besoin, accepté en don libre.
    $this->actingAs($this->lina)->post(route('dons.store'), ['vetement_id' => $pull->id, 'association_id' => $chaussures->association_id, 'besoin_id' => $chaussures->id])
        ->assertSessionHasErrors('besoin_id');
    $this->actingAs($this->lina)->post(route('dons.store'), ['vetement_id' => $pull->id, 'association_id' => $chaussures->association_id])
        ->assertSessionHasNoErrors();
});

test('une réponse de l’IA aux accents abîmés est redemandée', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
        ->push(avisGemini(['est_vetement' => true, 'verdict' => 'donnable', 'etat_observe' => "Excellent \tat, sans d\tfaut.", 'defauts' => [], 'conseil' => 'Merci !', 'correspond_au_besoin' => true, 'remarque_besoin' => '']))
        ->push(avisGemini(['est_vetement' => true, 'verdict' => 'donnable', 'etat_observe' => 'Excellent état, sans défaut.', 'defauts' => [], 'conseil' => 'Merci !', 'correspond_au_besoin' => true, 'remarque_besoin' => ''])),
    ]);

    $r = $this->actingAs($this->lina)->post(route('dons.controle'), ['vetement_id' => $this->vetement->id, 'photo' => UploadedFile::fake()->image('pull.jpg')])->json();
    expect($r['etat_observe'])->toBe('Excellent état, sans défaut.');
    Http::assertSentCount(2);
});

test('quota du modèle principal épuisé : bascule sur le modèle de secours, mémorisée ensuite', function () {
    config(['services.gemini.model' => 'gemini-2.5-flash', 'services.gemini.fallback_models' => 'gemini-3.5-flash-lite']);
    Http::fake([
        '*models/gemini-2.5-flash:generateContent' => Http::response(['error' => ['message' => 'Quota exceeded']], 429),
        '*models/gemini-3.5-flash-lite:generateContent' => Http::response(avisGemini([
            'est_vetement' => true, 'verdict' => 'donnable', 'etat_observe' => 'Très bon état.', 'defauts' => [], 'conseil' => 'Merci !', 'correspond_au_besoin' => true, 'remarque_besoin' => '',
        ])),
    ]);

    foreach ([1, 2] as $fois) {
        $r = $this->actingAs($this->lina)->post(route('dons.controle'), ['vetement_id' => $this->vetement->id, 'photo' => UploadedFile::fake()->image('pull.jpg')])->assertOk()->json();
        expect($r['verdict'])->toBe('donnable');
    }

    // Le modèle épuisé n'est demandé qu'une fois ; le paramètre réservé aux modèles 2.x n'est pas envoyé au modèle de secours.
    expect(Http::recorded(fn ($requete) => str_contains($requete->url(), 'gemini-2.5-flash:'))->count())->toBe(1);
    Http::assertSent(fn ($requete) => str_contains($requete->url(), 'gemini-3.5-flash-lite') && ! isset($requete['generationConfig']['thinkingConfig']));
});
