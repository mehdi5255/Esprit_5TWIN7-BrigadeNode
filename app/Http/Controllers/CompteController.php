<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        return view('compte.edit', [
            'user' => $request->user(),
            'layout' => $request->user()->hasRole('particulier') ? 'layouts.frontoffice' : 'layouts.backoffice',
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $user->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
        ]));

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
