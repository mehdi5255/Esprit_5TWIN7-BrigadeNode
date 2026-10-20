<?php

// Utilisateurs — Authentification de base avec Laravel Breeze (tests Breeze adaptés à TextileCycle).

use App\Enums\StatutCompte;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('les pages de connexion, d’inscription et de mot de passe oublié s’affichent', function () {
    $this->get(route('login'))->assertOk()->assertSee('Connexion');
    $this->get(route('register'))->assertOk();
    $this->get(route('password.request'))->assertOk()->assertSee('Mot de passe oublié');
});

test('connexion avec les bons identifiants, puis redirection vers l’espace du rôle', function () {
    $user = User::factory()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
    $this->get(route('dashboard'))->assertRedirect($user->homeRoute());
});

test('mauvais mot de passe : message clair, et blocage après 5 tentatives', function () {
    $user = User::factory()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'faux'])->assertSessionHasErrors(['email' => 'Identifiants incorrects.']);
    $this->assertGuest();

    foreach (range(1, 4) as $i) {
        $this->post(route('login'), ['email' => $user->email, 'password' => 'faux']);
    }
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toStartWith('Trop de tentatives');
    $this->assertGuest();
});

test('un compte bloqué par l’admin ne peut pas se connecter', function () {
    $user = User::factory()->create(['statut' => StatutCompte::BLOQUE]);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors(['email' => trans('auth.blocked')]);
    $this->assertGuest();
});

test('déconnexion, et routes protégées par le middleware auth', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect('/');
    $this->assertGuest();
    $this->get(route('vetements.index'))->assertRedirect(route('login'));
    $this->get(route('compte.edit'))->assertRedirect(route('login'));
});

test('inscription d’un particulier, et d’un atelier qui attend la validation de l’admin', function () {
    $this->post(route('register'), [
        'role' => 'particulier', 'name' => 'Test User', 'email' => 'test@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();
    expect(User::firstWhere('email', 'test@example.com')->statut)->toBe(StatutCompte::ACTIF);

    auth()->logout();
    $this->post(route('register'), [
        'role' => 'atelier', 'name' => 'Couturière', 'email' => 'atelier@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
        'structure_nom' => 'Atelier du Coin', 'structure_ville' => 'Tunis',
    ])->assertSessionHasNoErrors();
    $atelier = User::firstWhere('email', 'atelier@example.com');
    expect($atelier->statut)->toBe(StatutCompte::EN_ATTENTE)->and($atelier->atelier->nom)->toBe('Atelier du Coin');

    // Validation : rôle inconnu refusé, nom et ville de la structure obligatoires pour un professionnel.
    auth()->logout();
    $this->post(route('register'), ['role' => 'admin', 'name' => 'X', 'email' => 'x@example.com', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertSessionHasErrors('role');
    $this->post(route('register'), ['role' => 'association', 'name' => 'X', 'email' => 'y@example.com', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertSessionHasErrors(['structure_nom', 'structure_ville']);
});

test('mot de passe oublié : lien envoyé par e-mail, puis nouveau mot de passe', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');
    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->get(route('password.reset', ['token' => $notification->token, 'email' => $user->email]))->assertOk()->assertSee($user->email);

        $this->post(route('password.store'), [
            'token' => $notification->token, 'email' => $user->email,
            'password' => 'nouveau-mdp-123', 'password_confirmation' => 'nouveau-mdp-123',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

        return true;
    });
    $this->post(route('login'), ['email' => $user->email, 'password' => 'nouveau-mdp-123']);
    $this->assertAuthenticatedAs($user);

    // Adresse inconnue : même réponse, rien n'est révélé.
    auth()->logout();
    $this->post(route('password.email'), ['email' => 'inconnu@example.com'])->assertSessionHasNoErrors()->assertSessionHas('status');
});

test('un lien de réinitialisation expiré ou falsifié est refusé', function () {
    $user = User::factory()->create();
    $token = app('auth.password.broker')->createToken($user);
    $nouveau = ['email' => $user->email, 'password' => 'nouveau-mdp-123', 'password_confirmation' => 'nouveau-mdp-123'];

    $this->post(route('password.store'), ['token' => 'faux'] + $nouveau)->assertSessionHasErrors('email');

    $this->travel(config('auth.passwords.users.expire') + 1)->minutes();
    $this->post(route('password.store'), ['token' => $token] + $nouveau)->assertSessionHasErrors('email');
});

test('confirmation du mot de passe avant une action sensible', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('password.confirm'))->assertOk();
    $this->actingAs($user)->post(route('password.confirm'), ['password' => 'faux'])->assertSessionHasErrors('password');
    $this->actingAs($user)->post(route('password.confirm'), ['password' => 'password'])->assertRedirect()->assertSessionHasNoErrors();
});
