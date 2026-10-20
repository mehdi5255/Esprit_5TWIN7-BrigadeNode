<?php

// Module 5 — IA des ateliers : « Quel atelier pour mon besoin ? » et assistant de création de service (Gemini simulé).

use App\Enums\TypeService;
use App\Models\Ateliers\AssistantService;
use App\Models\Ateliers\Atelier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->filRouge = User::firstWhere('email', 'contact@filrouge.fr');
    $this->refil = Atelier::firstWhere('nom', 'Studio Refil');
});

function reponseIa(array $donnees): array
{
    return ['candidates' => [['content' => ['parts' => [['text' => json_encode($donnees)]]]]]];
}

test('sans IA, les ateliers sont classés par mots-clés, avec un lien de demande vers l’atelier choisi', function () {
    $this->get(route('ateliers.index'))->assertOk()->assertSee('Quel atelier pour mon besoin ?');

    $r = $this->post(route('ateliers.conseil'), ['besoin' => 'La fermeture éclair de mon blouson est cassée'])->assertOk()->json();

    expect($r['base'])->toBe('mots-cles')->and($r['type'])->toBe('reparation')
        ->and($r['ateliers'])->not->toBeEmpty()->and(count($r['ateliers']))->toBeLessThanOrEqual(3)
        ->and($r['ateliers'][0]['demander'])->toBe(route('reparations.create', ['atelier' => $r['ateliers'][0]['id']]));

    // Besoin trop court refusé.
    $this->postJson(route('ateliers.conseil'), ['besoin' => 'jean'])->assertUnprocessable()->assertJsonValidationErrors('besoin');
});

test('Gemini recommande des ateliers réels : un identifiant inventé est écarté, le lien suit le type de service', function () {
    config(['services.gemini.key' => 'cle-de-test']);
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(reponseIa([
        'type_service' => 'transformation',
        'recommandations' => [
            ['atelier_id' => $this->refil->id, 'service' => 'Jean en sac cabas', 'raison' => 'Spécialiste de l’upcycling, transforme les jeans en sacs.'],
            ['atelier_id' => 999999, 'service' => 'Inventé', 'raison' => 'N’existe pas.'],
        ],
        'conseil' => 'Apportez le jean propre.',
    ]))]);

    $r = $this->post(route('ateliers.conseil'), ['besoin' => 'Je voudrais transformer mon vieux jean en sac'])->json();

    expect($r['base'])->toBe('gemini')->and($r['ateliers'])->toHaveCount(1)
        ->and($r['ateliers'][0]['nom'])->toBe('Studio Refil')
        ->and($r['ateliers'][0]['service']['nom'])->toBe('Jean en sac cabas')
        ->and($r['ateliers'][0]['demander'])->toBe(route('transformations.create', ['atelier' => $this->refil->id]))
        ->and($r['conseil'])->toBe('Apportez le jean propre.');
    // Le catalogue envoyé à l'IA contient les vrais services et villes des ateliers.
    Http::assertSent(fn ($requete) => str_contains($requete['contents'][0]['parts'][0]['text'], 'Jean en sac cabas'));
});

test('l’assistant de service calcule le marché à partir des autres ateliers, sans compter les siens', function () {
    $assistant = new AssistantService;
    $atelier = $this->filRouge->atelier;
    // Deux services comparables chez un autre atelier actif.
    $this->refil->services()->createMany([
        ['type' => 'reparation', 'nom' => 'Remplacement de fermeture', 'prix' => 18, 'duree' => 40, 'delai_jours' => 3],
        ['type' => 'reparation', 'nom' => 'Remplacement fermeture éclair veste', 'prix' => 24, 'duree' => 50, 'delai_jours' => 5],
    ]);
    $similaires = $assistant->similaires('Remplacement de fermeture éclair', TypeService::REPARATION, $atelier);

    expect($similaires)->not->toBeEmpty()
        ->and($similaires->pluck('atelier_id'))->not->toContain($atelier->id)
        ->and($similaires->every(fn ($s) => $s->atelier->estActif()))->toBeTrue();

    $marche = $assistant->marche($similaires);
    expect($marche['min'])->toBeLessThanOrEqual($marche['moyenne'])->and($marche['moyenne'])->toBeLessThanOrEqual($marche['max']);

    // Indicateur de positionnement : au-delà de 20 % d'écart avec la moyenne.
    expect(AssistantService::positionnement($marche['moyenne'] * 1.5, $marche)['niveau'])->toBe('plus-cher')
        ->and(AssistantService::positionnement($marche['moyenne'] * 0.5, $marche)['niveau'])->toBe('moins-cher')
        ->and(AssistantService::positionnement($marche['moyenne'], $marche)['niveau'])->toBe('dans-la-moyenne');

    // Sans IA : valeurs du marché.
    $r = $this->actingAs($this->filRouge)->post(route('atelier.services.assistant'), ['nom' => 'Remplacement de fermeture éclair', 'type' => 'reparation'])->assertOk()->json();
    expect($r['base'])->toBe('marche')->and($r['prix'])->toEqual($marche['moyenne'])->and($r['marche']['nombre'])->toBe($marche['nombre']);
});

test('avec Gemini, la fiche du service est préremplie ; réservé aux ateliers', function () {
    config(['services.gemini.key' => 'cle-de-test']);
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(reponseIa([
        'description' => 'Remplacement complet de la fermeture éclair, pour vestes, jeans et sacs.',
        'duree_minutes' => 45, 'delai_jours' => 4, 'prix' => 19.3, 'explication' => 'Dans la moyenne des ateliers de la plateforme.',
    ]))]);

    $this->actingAs($this->filRouge)->get(route('atelier.services.create'))->assertOk()->assertSee('Remplir avec l’IA');
    $r = $this->actingAs($this->filRouge)->post(route('atelier.services.assistant'), ['nom' => 'Remplacement de fermeture éclair', 'type' => 'reparation'])->json();

    expect($r['base'])->toBe('gemini')->and($r['duree'])->toBe(45)->and($r['delai_jours'])->toBe(4)
        ->and($r['prix'])->toEqual(19.5)->and($r['description'])->toContain('fermeture éclair')->and($r['marche'])->not->toBeNull();
    Http::assertSent(fn ($requete) => str_contains($requete['contents'][0]['parts'][0]['text'], 'Fourchette du marché'));

    $this->actingAs(User::firstWhere('email', 'lina.martin@example.com'))->post(route('atelier.services.assistant'), ['nom' => 'Ourlet', 'type' => 'reparation'])->assertForbidden();
});
