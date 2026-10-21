<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * « Mon compte » : chaque utilisateur consulte et modifie ses propres données personnelles
 * (l'admin ne peut pas le faire à sa place — RGPD), change son mot de passe ou supprime son compte.
 */
class CompteController extends Controller
{
    public function edit(Request $request)
    {
        return view('front.commun.compte.edit', [
            'user' => $request->user(),
            'layout' => $request->user()->hasRole('particulier') ? 'layouts.front' : 'layouts.back',
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $user->fill($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
        ]));

        // Nouvelle adresse : elle doit être vérifiée à son tour.
        $emailChange = $user->isDirty('email');
        if ($emailChange) {
            $user->email_verified_at = null;
        }
        $user->save();

        if ($emailChange) {
            $user->sendEmailVerificationNotification();

            return $user->echecEnvoiVerification
                ? redirect()->route('verification.notice')->with('error', 'Adresse modifiée, mais l’e-mail de vérification n’a pas pu être envoyé pour le moment. Réessayez dans quelques minutes avec « Renvoyer le lien ».')
                : redirect()->route('verification.notice')->with('success', 'Adresse modifiée : confirmez-la avec le lien que nous venons de vous envoyer.');
        }

        return back()->with('success', 'Vos informations ont été mises à jour.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Votre mot de passe a été modifié.');
    }

    /**
     * Mot de passe actuel oublié : même logique que « Mot de passe oublié » (Breeze),
     * le lien de réinitialisation (jeton valable 60 minutes) part à l'adresse du compte.
     */
    public function envoyerLienReinitialisation(Request $request)
    {
        try {
            $statut = PasswordBroker::sendResetLink(['email' => $request->user()->email]);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'L’e-mail n’a pas pu être envoyé pour le moment. Réessayez dans quelques minutes.');
        }

        return $statut === PasswordBroker::RESET_LINK_SENT
            ? back()->with('success', "Un lien de réinitialisation a été envoyé à {$request->user()->email}. Il expire dans ".config('auth.passwords.users.expire').' minutes.')
            : back()->with('error', __($statut));
    }

    /** Droit à l'effacement : possible tant que le compte n'a pas d'historique de demandes. */
    public function destroy(Request $request)
    {
        $request->validate(['password_suppression' => ['required', 'current_password']]);
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return back()->with('error', 'Un compte administrateur ne peut pas être supprimé depuis cette page.');
        }

        if ($user->aUnHistorique()) {
            return back()->with('error', 'Votre compte a un historique de demandes : contactez l’administration pour le faire anonymiser.');
        }

        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Votre compte a été supprimé.');
    }
}
