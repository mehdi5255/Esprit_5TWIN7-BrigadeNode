<?php

// Utilisateurs — un serveur d'e-mails injoignable ne doit jamais faire échouer l'inscription (pas d'erreur 500).

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // SMTP vers un port fermé : chaque envoi échoue immédiatement.
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 2]);
});

test('inscription : le compte est créé et l’échec d’envoi est signalé, sans erreur 500', function () {
    $this->post(route('register'), [
        'role' => 'particulier', 'name' => 'Test', 'email' => 'test@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false))->assertSessionHas('error');

    $user = User::firstWhere('email', 'test@example.com');
    expect($user)->not->toBeNull();

    // « Renvoyer le lien » : message d'erreur honnête, pas de faux « lien envoyé ».
    $this->actingAs($user)->post(route('verification.send'))->assertSessionHas('error')->assertSessionMissing('status');
});

test('mot de passe oublié : message clair si l’e-mail ne part pas', function () {
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasErrors('email');
    $this->actingAs($user)->post(route('compte.password.lien'))->assertSessionHas('error');
});
