<?php

// Module 1 — Matières : catalogue admin, composition des vêtements (N-N + pourcentage), impact évité.

use App\Models\User;
use App\Models\Vetements\CategorieVetement;
use App\Models\Vetements\Matiere;
use App\Models\Vetements\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->admin = User::firstWhere('email', 'admin@textilecycle.fr');
    $this->lina = User::firstWhere('email', 'lina.martin@example.com');
    $this->coton = Matiere::firstWhere('nom', 'Coton');
    $this->polyester = Matiere::firstWhere('nom', 'Polyester');
    $this->vetement = fn (array $extra = []) => [
        'nom' => 'T-shirt marinière de test', 'categorie_id' => CategorieVetement::firstWhere('libelle', 'Haut')->id, 'etat' => 'bon',
    ] + $extra;
});

test('l’admin gère le catalogue des matières (CRUD complet)', function () {
    $this->actingAs($this->admin)->get(route('admin.matieres.index'))->assertOk()->assertSee('Coton')->assertSee('cotton');

    $this->actingAs($this->admin)->post(route('admin.matieres.store'), ['nom' => 'Coton', 'type' => 'naturelle'])
        ->assertSessionHasErrors(['nom', 'co2_kg_par_kg', 'eau_litres_par_kg']);    // nom déjà pris, facteurs manquants

    $this->actingAs($this->admin)->post(route('admin.matieres.store'), [
        'nom' => 'Chanvre', 'type' => 'naturelle', 'abreviations' => 'HA, hemp, cáñamo', 'co2_kg_par_kg' => 5, 'eau_litres_par_kg' => 400, 'actif' => 1,
    ])->assertSessionHasNoErrors();
    $chanvre = Matiere::firstWhere('nom', 'Chanvre');
    expect($chanvre->abreviations)->toBe(['HA', 'hemp', 'cáñamo'])->and($chanvre->motsCles())->toContain('canamo');

    $this->actingAs($this->admin)->put(route('admin.matieres.update', $chanvre), [
        'nom' => 'Chanvre', 'type' => 'naturelle', 'co2_kg_par_kg' => 4.5, 'eau_litres_par_kg' => 400, 'actif' => 1,
    ])->assertSessionHasNoErrors();
    expect((float) $chanvre->refresh()->co2_kg_par_kg)->toBe(4.5);

    // Une matière utilisée ne se supprime pas : on la désactive.
    $this->actingAs($this->admin)->delete(route('admin.matieres.destroy', $this->coton))->assertSessionHas('error');
    $this->actingAs($this->admin)->post(route('admin.matieres.basculer', $this->coton));
    expect($this->coton->refresh()->actif)->toBeFalse();
    $this->actingAs($this->admin)->delete(route('admin.matieres.destroy', $chanvre))->assertSessionHas('success');
    expect(Matiere::find($chanvre->id))->toBeNull();

    $this->actingAs($this->lina)->get(route('admin.matieres.index'))->assertForbidden();
});

test('la composition est validée : 100 %, matières distinctes, poids obligatoire', function () {
    $ligne = fn ($matiere, $pct) => ['matiere_id' => $matiere->id, 'pourcentage' => $pct];

    $this->actingAs($this->lina)->post(route('vetements.store'), ($this->vetement)(['poids_grammes' => 200, 'composition' => [$ligne($this->coton, 80), $ligne($this->polyester, 10)]]))
        ->assertSessionHasErrors('composition');
    $this->actingAs($this->lina)->post(route('vetements.store'), ($this->vetement)(['poids_grammes' => 200, 'composition' => [$ligne($this->coton, 50), $ligne($this->coton, 50)]]))
        ->assertSessionHasErrors('composition.0.matiere_id');
    $this->actingAs($this->lina)->post(route('vetements.store'), ($this->vetement)(['composition' => [$ligne($this->coton, 100)]]))
        ->assertSessionHasErrors('poids_grammes');

    // Valide (une ligne vide est ignorée) : composition enregistrée sur le pivot.
    $this->actingAs($this->lina)->post(route('vetements.store'), ($this->vetement)(['poids_grammes' => 200, 'composition' => [
        $ligne($this->coton, 80), $ligne($this->polyester, 20), ['matiere_id' => '', 'pourcentage' => ''],
    ]]))->assertSessionHasNoErrors();
    $tshirt = Vetement::firstWhere('nom', 'T-shirt marinière de test');
    expect($tshirt->matieres->pluck('pivot.pourcentage', 'nom')->all())->toBe(['Coton' => 80, 'Polyester' => 20])
        ->and($tshirt->composition_estimee)->toBeFalse();

    // Fiche : composition, impact (0,2 kg × (0,8 × 16 + 0,2 × 14) = 3,1 kg CO₂) et conseils d'entretien.
    $this->actingAs($this->lina)->get(route('vetements.show', $tshirt))->assertOk()
        ->assertSee('80 % Coton · 20 % Polyester')->assertSee('3,1 kg')->assertSee('Pour le faire durer');

    // Modification : la composition est remplacée, et peut être marquée « estimée ».
    $this->actingAs($this->lina)->put(route('vetements.update', $tshirt), ($this->vetement)(['poids_grammes' => 200, 'composition_estimee' => 1, 'composition' => [$ligne($this->coton, 100)]]))
        ->assertSessionHasNoErrors();
    expect($tshirt->refresh()->matieres->pluck('nom')->all())->toBe(['Coton'])->and($tshirt->composition_estimee)->toBeTrue();
    $this->actingAs($this->lina)->get(route('vetements.show', $tshirt))->assertSee('estimée');
});

test('l’impact est compté une fois le vêtement sauvé, sur le dressing, l’accueil et les rapports', function () {
    $manteau = Vetement::firstWhere('nom', 'Manteau camel');   // réparé et récupéré dans les données de démo
    expect($manteau->load('matieres')->impact())->toBe(['co2' => 25.4, 'eau' => 1476]);

    $impactLina = Vetement::impactEconomise($this->lina->vetements()->getQuery());
    expect($impactLina['vetements'])->toBe(1)->and($impactLina['co2'])->toBe(25.4);

    $this->actingAs($this->lina)->get(route('vetements.index'))->assertOk()->assertSee('Grâce à vous')->assertSee('25,4 kg');
    $this->get(route('home'))->assertOk()->assertSee('vêtements sauvés')->assertDontSee('2 840');
    $this->actingAs($this->admin)->get(route('admin.rapports.index'))->assertOk()->assertSee('Impact écologique depuis le lancement')->assertSee('Laine');

    // Formulaire : catalogue, compositions typiques et lien d'exemple pour le scan.
    $this->actingAs($this->lina)->get(route('vetements.create'))->assertOk()->assertSee('Scanner l')->assertSee('Je n\'ai plus l\'étiquette', false)->assertSee('etiquette-composition.png');
});

test('les noms chinois des matières servent à lire les étiquettes en chinois', function () {
    expect($this->coton->motsCles())->toContain('棉', 'co', 'cotton')
        ->and($this->polyester->motsCles())->toContain('聚酯纤维', '涤纶')
        ->and(Matiere::firstWhere('nom', 'Élasthanne')->motsCles())->toContain('氨纶');

    $this->actingAs($this->lina)->get(route('vetements.create'))->assertOk()->assertSee('etiquette-composition-chinois.png');
});
