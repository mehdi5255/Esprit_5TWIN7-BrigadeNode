<?php

// Utilisateurs — Vérification de l'adresse e-mail (MustVerifyEmail).

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    Notification::fake();
});

test('l’inscription envoie un lien de vérification, et l’espace reste fermé tant qu’il n’est pas confirmé', function () {
    $this->post(route('register'), [
        'role' => 'particulier', 'name' => 'Sami Ben Ali', 'email' => 'sami@example.com',
        'password' => 'motdepasse123', 'password_confirmation' => 'motdepasse123',
    ])->assertRedirect(route('dashboard', absolute: false));
    $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));

    $sami = User::firstWhere('email', 'sami@example.com');
    expect($sami->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($sami, VerifyEmail::class, function (VerifyEmail $notification) use ($sami) {
        $mail = $notification->toMail($sami);

        return $mail->subject === 'Confirmez votre adresse e-mail TextileCycle' && str_contains($mail->actionUrl, '/verification-email/');
    });

    // Contrôle côté serveur : toutes les pages de l'espace renvoient vers la vérification.
    $this->actingAs($sami)->get(route('vetements.index'))->assertRedirect(route('verification.notice'));
    $this->actingAs($sami)->get(route('reparations.create'))->assertRedirect(route('verification.notice'));
    $this->actingAs($sami)->get(route('verification.notice'))->assertOk()->assertSee('sami@example.com');
    // Le compte reste accessible pour corriger une adresse erronée.
    $this->actingAs($sami)->get(route('compte.edit'))->assertOk();
});

test('le lien signé confirme l’adresse ; un lien falsifié ou expiré est refusé', function () {
    $user = User::factory()->unverified()->create();
    $lien = fn ($expiration) => URL::temporarySignedRoute('verification.verify', $expiration, ['id' => $user->id, 'hash' => sha1($user->email)]);

    // Lien expiré, puis lien d'un autre utilisateur (hash faux) : refusés.
    $this->actingAs($user)->get($lien(now()->subMinute()))->assertForbidden();
    $this->actingAs($user)->get(URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('autre@example.com')]))->assertForbidden();
    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();

    $this->actingAs($user)->get($lien(now()->addHour()))->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
    $this->actingAs($user)->get(route('vetements.index'))->assertOk();
});

test('le lien peut être renvoyé, et changer d’adresse impose une nouvelle vérification', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->post(route('verification.send'))->assertSessionHas('status', 'verification-link-sent');
    Notification::assertSentToTimes($user, VerifyEmail::class, 1);

    $lina = User::firstWhere('email', 'lina.martin@example.com');
    expect($lina->hasVerifiedEmail())->toBeTrue();
    $this->actingAs($lina)->put(route('compte.update'), ['name' => $lina->name, 'email' => 'lina.nouvelle@example.com'])
        ->assertRedirect(route('verification.notice'));
    expect($lina->refresh()->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($lina, VerifyEmail::class);
});
