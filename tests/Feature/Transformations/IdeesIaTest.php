<?php

// Module 3 — Assistant IA : idées de transformation et aperçu du résultat (Gemini simulé, aucun appel réel).

use App\Models\Transformations\DemandeTransformation;
use App\Models\Transformations\Inspiration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    Storage::fake('public');
    $this->lina = User::firstWhere('email', 'lina.martin@example.com');
    // Un vêtement disponible de Lina, d'une catégorie qui a des inspirations dans la galerie.
    $this->vetement = $this->lina->vetements()->where('statut', 'disponible')
        ->whereIn('categorie_id', Inspiration::visibles()->pluck('categorie_id'))->firstOrFail();
    $this->compatible = Inspiration::visibles()->where('categorie_id', $this->vetement->categorie_id)->firstOrFail();
});

function geminiTexte(array $donnees): array
{
    return ['candidates' => [['content' => ['parts' => [['text' => json_encode($donnees)]]]]]];
}

test('sans IA, l’assistant propose les modèles de la galerie et la page fonctionne', function () {
    $this->actingAs($this->lina)->get(route('transformations.idees'))->assertOk()->assertSee('Que faire de ce vêtement ?');

    $r = $this->actingAs($this->lina)->post(route('transformations.idees.generer'), ['vetement_id' => $this->vetement->id])->assertOk()->json();
    expect($r['base'])->toBe('inspirations')
        ->and(collect($r['idees'])->pluck('inspirations.0.id'))->toContain($this->compatible->id);

    $a = $this->actingAs($this->lina)->post(route('transformations.apercu'), ['vetement_id' => $this->vetement->id, 'idee' => 'Sac cabas'])->assertOk()->json();
    expect($a['base'])->toBeIn(['inspiration', 'aucune'])->and($a['chemin'])->toBeNull();

    // Le vêtement d'un autre particulier est refusé.
    $autre = User::firstWhere('email', 'noah.bernard@example.com')->vetements()->firstOrFail();
    $this->actingAs($this->lina)->postJson(route('transformations.idees.generer'), ['vetement_id' => $autre->id])->assertUnprocessable();
});

test('Gemini propose 3 idées reliées à la galerie, puis un aperçu joint à la demande', function () {
    config(['services.gemini.key' => 'cle-de-test', 'services.gemini.image_model' => 'gemini-2.5-flash-image']);
    Http::fake([
        '*models/gemini-2.5-flash:generateContent' => Http::response(geminiTexte(['idees' => [
            ['titre' => 'Sac cabas', 'description' => 'Un sac solide pour les courses.', 'difficulte' => 'facile', 'temps_estime' => '2 h', 'matiere_reutilisee' => 'Tout le tissu', 'inspirations' => [$this->compatible->id, 999999]],
            ['titre' => 'Coussin', 'description' => 'Une housse de coussin.', 'difficulte' => 'facile', 'temps_estime' => '1 h', 'matiere_reutilisee' => 'Le dos', 'inspirations' => []],
            ['titre' => 'Veste patchwork', 'description' => 'Une pièce unique.', 'difficulte' => 'avancée', 'temps_estime' => '1 jour', 'matiere_reutilisee' => 'Les manches', 'inspirations' => []],
        ]])),
        '*models/gemini-2.5-flash-image:generateContent' => Http::response(['candidates' => [['content' => ['parts' => [
            ['inlineData' => ['mimeType' => 'image/png', 'data' => base64_encode('image-generee')]],
        ]]]]]),
        '*' => Http::response('photo', 200, ['Content-Type' => 'image/jpeg']),   // photo de démonstration du vêtement
    ]);

    $r = $this->actingAs($this->lina)->post(route('transformations.idees.generer'), ['vetement_id' => $this->vetement->id])->json();
    expect($r['base'])->toBe('gemini')->and($r['idees'])->toHaveCount(3)
        ->and($r['idees'][0]['inspirations'])->toHaveCount(1)          // l'identifiant inventé est écarté
        ->and($r['idees'][0]['inspirations'][0]['id'])->toBe($this->compatible->id);

    // Deuxième demande pour le même vêtement : réponse en cache, pas de nouvel appel à l'IA.
    $this->actingAs($this->lina)->post(route('transformations.idees.generer'), ['vetement_id' => $this->vetement->id]);
    expect(Http::recorded(fn ($requete) => str_contains($requete->url(), 'gemini-2.5-flash:'))->count())->toBe(1);

    // Aperçu généré : image enregistrée, puis jointe à la demande.
    $a = $this->actingAs($this->lina)->post(route('transformations.apercu'), ['vetement_id' => $this->vetement->id, 'idee' => 'Sac cabas : un sac solide.'])->json();
    expect($a['base'])->toBe('gemini')->and($a['chemin'])->toStartWith('transformations/apercus/'.$this->lina->id.'-');
    Storage::disk('public')->assertExists($a['chemin']);

    $this->actingAs($this->lina)->get(route('transformations.create', ['vetement' => $this->vetement->id, 'idee' => 'Sac cabas : un sac solide.', 'apercu' => $a['chemin']]))
        ->assertOk()->assertSee('Aperçu IA joint au projet')->assertSee('Sac cabas : un sac solide.');

    $atelier = $this->compatible->atelier;
    $this->actingAs($this->lina)->post(route('transformations.store'), [
        'vetement_id' => $this->vetement->id, 'atelier_id' => $atelier->id, 'idee' => 'Sac cabas : un sac solide.', 'apercu_ia' => $a['chemin'],
    ])->assertSessionHasNoErrors();
    $demande = DemandeTransformation::latest('id')->firstOrFail();
    expect($demande->apercu_ia)->toBe($a['chemin']);

    // L'atelier voit l'aperçu sur la demande.
    $this->actingAs($atelier->responsable)->get(route('atelier.transformations.show', $demande))->assertOk()->assertSee('Aperçu généré par l’IA');
});

test('sans Gemini image, Cloudflare dessine une illustration d’après la description rédigée par Gemini', function () {
    config(['services.gemini.key' => 'cle-de-test', 'services.cloudflare.account_id' => 'compte', 'services.cloudflare.token' => 'jeton']);
    Http::fake([
        '*models/gemini-2.5-flash:generateContent' => Http::response(geminiTexte(['prompt' => 'A camel wool sleeveless vest'])),
        'api.cloudflare.com/*' => Http::response(['result' => ['image' => base64_encode('illustration')], 'success' => true]),
        '*' => Http::response('photo', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $a = $this->actingAs($this->lina)->post(route('transformations.apercu'), ['vetement_id' => $this->vetement->id, 'idee' => 'Gilet sans manches'])->json();
    expect($a['base'])->toBe('illustration')->and($a['chemin'])->toEndWith('.jpg');
    Storage::disk('public')->assertExists($a['chemin']);
    Http::assertSent(fn ($requete) => str_contains($requete->url(), 'flux-1-schnell') && str_starts_with($requete['prompt'], 'A camel wool sleeveless vest'));
    Http::assertNotSent(fn ($requete) => str_contains($requete->url(), 'flash-image'));   // modèle payant non configuré : jamais appelé
});

test('un particulier ne peut pas joindre l’aperçu d’un autre', function () {
    Storage::disk('public')->put($chemin = 'transformations/apercus/999-'.Str::uuid().'.png', 'x');

    $this->actingAs($this->lina)->post(route('transformations.store'), [
        'vetement_id' => $this->vetement->id, 'atelier_id' => $this->compatible->atelier_id, 'idee' => 'Projet', 'apercu_ia' => $chemin,
    ])->assertSessionHasErrors('apercu_ia');
});
