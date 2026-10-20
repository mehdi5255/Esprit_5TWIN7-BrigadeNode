<?php

// Utilisateurs — Authentification à deux facteurs (Laravel Fortify) : TOTP, QR code, codes de récupération.

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

/** Le code que l'application du téléphone afficherait en ce moment. */
function codeTotp(User $user): string
{
    return (new Google2FA)->getCurrentOtp(decrypt($user->refresh()->two_factor_secret));
}

/** Active et confirme la 2FA comme le ferait l'utilisateur. */
function activer2fa($test, User $user): void
{
    $test->actingAs($user)->post(route('compte.2fa.activer'), ['mot_de_passe_2fa' => 'password']);
    $test->actingAs($user)->post(route('compte.2fa.confirmer'), ['code' => codeTotp($user)]);
}

test('activation : QR code et clé affichés, puis confirmation par un premier code', function () {
    $user = User::factory()->create();

    // Mot de passe exigé.
    $this->actingAs($user)->post(route('compte.2fa.activer'), ['mot_de_passe_2fa' => 'faux'])->assertSessionHasErrors('mot_de_passe_2fa', errorBag: 'deuxFacteurs');
    expect($user->refresh()->two_factor_secret)->toBeNull();

    $this->actingAs($user)->post(route('compte.2fa.activer'), ['mot_de_passe_2fa' => 'password'])->assertSessionHas('success');
    expect($user->refresh()->two_factor_secret)->not->toBeNull()->and($user->hasEnabledTwoFactorAuthentication())->toBeFalse();
    $this->actingAs($user)->get(route('compte.edit'))->assertOk()->assertSee('À confirmer')->assertSee('<svg', false)->assertSee(decrypt($user->two_factor_secret));

    // Mauvais code refusé, bon code accepté.
    $this->actingAs($user)->post(route('compte.2fa.confirmer'), ['code' => '000000'])->assertSessionHasErrors('code', errorBag: 'confirmTwoFactorAuthentication');
    $this->actingAs($user)->post(route('compte.2fa.confirmer'), ['code' => codeTotp($user)])->assertSessionHas('afficher_codes_2fa');
    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeTrue()->and($user->recoveryCodes())->toHaveCount(8);
});

test('connexion avec la 2FA : le mot de passe ne suffit plus, il faut le code', function () {
    $user = User::factory()->create();
    activer2fa($this, $user);
    auth()->logout();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
    $this->get(route('vetements.index'))->assertRedirect(route('login'));
    $this->get(route('two-factor.login'))->assertOk()->assertSee('Vérification en deux étapes');

    $this->post(route('two-factor.login'), ['code' => '000000'])->assertSessionHasErrors('code');
    $this->assertGuest();

    // Fortify refuse de réutiliser un code (anti-rejeu) : on simule le code suivant, 30 secondes plus tard.
    Cache::flush();
    // Une fois le code validé, retour à la page demandée au départ.
    $this->post(route('two-factor.login'), ['code' => codeTotp($user)])->assertRedirect(route('vetements.index'));
    $this->assertAuthenticatedAs($user);
});

test('un code de récupération permet de se connecter une seule fois', function () {
    $user = User::factory()->create();
    activer2fa($this, $user);
    $code = $user->refresh()->recoveryCodes()[0];
    auth()->logout();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);
    $this->post(route('two-factor.login'), ['recovery_code' => $code]);
    $this->assertAuthenticatedAs($user);
    expect($user->refresh()->recoveryCodes())->not->toContain($code)->toHaveCount(8);

    auth()->logout();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);
    $this->post(route('two-factor.login'), ['recovery_code' => $code])->assertSessionHasErrors('recovery_code');
    $this->assertGuest();
});

test('sans passer par le mot de passe, la page du code renvoie à la connexion', function () {
    $this->get(route('two-factor.login'))->assertRedirect(route('login'));
    $this->post(route('two-factor.login'), ['code' => '123456'])->assertRedirect(route('login'));
});

test('régénérer les codes, puis désactiver la 2FA', function () {
    $user = User::factory()->create();
    activer2fa($this, $user);
    $anciens = $user->refresh()->recoveryCodes();

    $this->actingAs($user)->post(route('compte.2fa.codes'), ['mot_de_passe_2fa' => 'password'])->assertSessionHas('afficher_codes_2fa');
    expect($user->refresh()->recoveryCodes())->not->toBe($anciens);
    $this->actingAs($user)->get(route('compte.edit'))->assertSee('Activée');

    $this->actingAs($user)->delete(route('compte.2fa.desactiver'), ['mot_de_passe_2fa' => 'password'])->assertSessionHas('success');
    expect($user->refresh()->two_factor_secret)->toBeNull()->and($user->hasEnabledTwoFactorAuthentication())->toBeFalse();

    auth()->logout();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
});
