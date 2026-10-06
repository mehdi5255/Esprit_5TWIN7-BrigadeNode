<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as RegleMotDePasse;
use Illuminate\Validation\ValidationException;

/** Mot de passe oublié : envoi d'un lien par e-mail puis choix d'un nouveau mot de passe. */
class PasswordResetController extends Controller
{
    public function demande()
    {
        return view('front.commun.auth.forgot-password');
    }

    public function envoyerLien(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $statut = Password::sendResetLink($request->only('email'));

        // Même message que l'adresse existe ou non : on ne révèle pas quels e-mails sont inscrits.
        if (in_array($statut, [Password::RESET_LINK_SENT, Password::INVALID_USER], true)) {
            return back()->with('success', __('passwords.sent'));
        }

        throw ValidationException::withMessages(['email' => __($statut)]);
    }

    public function formulaire(Request $request, string $token)
    {
        return view('front.commun.auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reinitialiser(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', RegleMotDePasse::min(8)],
        ]);

        $statut = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            }
        );

        if ($statut !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($statut)]);
        }

        return redirect()->route('login')->with('success', __($statut));
    }
}
