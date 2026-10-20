<?php

// Module 2 — Pré-diagnostic IA par photo (Gemini simulé) et e-mail « vêtement prêt ».

use App\Models\Reparations\DemandeReparation;
use App\Models\User;
use App\Notifications\ReparationPrete;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    Storage::fake('public');
    $this->lina = User::firstWhere('email', 'lina.martin@example.com');
    $this->filRouge = User::firstWhere('email', 'contact@filrouge.fr');
    $this->vetement = $this->lina->vetements()->where('statut', 'disponible')->firstOrFail();
});

function reponseGemini(array $donnees): array
{
    return ['candidates' => [['content' => ['parts' => [['text' => json_encode($donnees)]]]]]];
}

test('la photo de l’abîmement remplit le type, la description, la gravité et un prix indicatif', function () {
    config(['services.gemini.key' => 'cle-de-test']);
    Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
        ->push(reponseGemini(['est_abime' => true, 'type_reparation' => 'fermeture', 'description' => 'La fermeture éclair est cassée, le curseur ne remonte plus.', 'gravite' => 'moyenne', 'conseil' => 'Évitez de forcer le curseur.']))
        ->push(reponseGemini(['est_abime' => false, 'type_reparation' => 'autre', 'description' => '', 'gravite' => 'légère', 'conseil' => ''])),
    ]);

    $r = $this->actingAs($this->lina)->post(route('reparations.pre-diagnostic'), [
        'photo' => UploadedFile::fake()->image('fermeture.jpg'), 'vetement_id' => $this->vetement->id,
    ])->assertOk()->json();

    expect($r['type_reparation'])->toBe('fermeture')
        ->and($r['gravite'])->toBe('moyenne')
        ->and($r['description'])->toContain('fermeture éclair')
        ->and($r['fourchette']['nb'])->toBeGreaterThan(0)          // devis de fermeture acceptés dans l'historique
        ->and($r['fourchette']['min'])->toBeLessThanOrEqual($r['fourchette']['max']);
    Http::assertSent(fn ($requete) => isset($requete['contents'][0]['parts'][0]['inlineData']));   // la photo est bien envoyée

    // Photo sans abîmement reconnu : message clair, rien n'est rempli.
    $this->actingAs($this->lina)->post(route('reparations.pre-diagnostic'), ['photo' => UploadedFile::fake()->image('chat.jpg')])
        ->assertStatus(422)->assertJsonStructure(['erreur']);
});

test('sans IA, le pré-diagnostic se signale indisponible et la photo reste obligatoire', function () {
    $this->actingAs($this->lina)->post(route('reparations.pre-diagnostic'), ['photo' => UploadedFile::fake()->image('trou.jpg')])
        ->assertStatus(503)->assertJsonStructure(['erreur']);
    $this->actingAs($this->lina)->postJson(route('reparations.pre-diagnostic'), [])->assertUnprocessable()->assertJsonValidationErrors('photo');
});

test('la photo du problème est jointe à la demande et visible par l’atelier', function () {
    $this->actingAs($this->lina)->post(route('reparations.store'), [
        'vetement_id' => $this->vetement->id, 'atelier_id' => $this->filRouge->atelier->id,
        'type_reparation' => 'trou', 'description' => 'Petit trou au coude.', 'photo_probleme' => UploadedFile::fake()->image('trou.jpg'),
    ])->assertSessionHasNoErrors();

    $demande = DemandeReparation::latest('id')->firstOrFail();
    Storage::disk('public')->assertExists($demande->photo_probleme);
    $this->actingAs($this->filRouge)->get(route('atelier.reparations.show', $demande))->assertOk()->assertSee('Photo du problème');
});

test('un e-mail prévient le particulier quand son vêtement est prêt', function () {
    Notification::fake();
    $demande = DemandeReparation::where('particulier_id', $this->lina->id)->where('statut', 'en_traitement')->firstOrFail();

    $this->actingAs($this->filRouge)->post(route('atelier.reparations.terminer', $demande), ['actions_realisees' => 'Couture reprise.'])
        ->assertSessionHas('success');

    Notification::assertSentTo($this->lina, ReparationPrete::class, function ($notification, $canaux) use ($demande) {
        $mail = $notification->toMail($this->lina);

        return $canaux === ['mail']
            && str_contains($mail->subject, 'est prêt')
            && str_contains(implode(' ', $mail->introLines), number_format((float) $demande->refresh()->facture->montant_total, 2, ',', ' '))
            && $mail->actionUrl === route('reparations.show', $demande);
    });
});
