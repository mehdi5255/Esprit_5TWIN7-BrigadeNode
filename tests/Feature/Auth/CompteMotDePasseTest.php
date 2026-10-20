<?php

// Utilisateurs — « Mon compte » : réinitialisation du mot de passe par e-mail et état de la vérification de l'adresse.

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(fn () => Notification::fake());

test('mot de passe actuel oublié : lien reçu depuis Mon compte, ouvert connecté, puis retour à Mon compte', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('compte.edit'))->assertOk()
        ->assertSee('Recevoir un lien de réinitialisation par e-mail')->assertSee('Adresse e-mail vérifiée');

    $this->actingAs($user)->post(route('compte.password.lien'))->assertSessionHas('success');
    // Un second envoi immédiat est refusé (un lien par minute et par compte).
    $this->actingAs($user)->post(route('compte.password.lien'))->assertSessionHas('error');
    Notification::assertSentToTimes($user, ResetPassword::class, 1);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        // Le lien s'ouvre sans se déconnecter.
        $this->actingAs($user)->get(route('password.reset', ['token' => $notification->token, 'email' => $user->email]))->assertOk();
        $this->actingAs($user)->post(route('password.store'), [
            'token' => $notification->token, 'email' => $user->email,
            'password' => 'nouveau-mdp-123', 'password_confirmation' => 'nouveau-mdp-123',
        ])->assertRedirect(route('compte.edit'))->assertSessionHas('success');

        return true;
    });
    expect(Hash::check('nouveau-mdp-123', $user->refresh()->password))->toBeTrue();
});

test('une adresse non vérifiée est signalée dans Mon compte, avec le renvoi du lien', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('compte.edit'))->assertOk()
        ->assertSee('Adresse e-mail non vérifiée')->assertSee('Renvoyer le lien de vérification');
    $this->actingAs($user)->post(route('verification.send'))->assertSessionHas('status', 'verification-link-sent');
    Notification::assertSentTo($user, VerifyEmail::class);
});
