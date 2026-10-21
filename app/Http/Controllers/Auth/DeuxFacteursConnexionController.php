<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

/**
 * Deuxième étape de la connexion quand la 2FA est active : code à 6 chiffres de l'application
 * d'authentification (TOTP, vérifié par Fortify), ou un code de récupération à usage unique.
 */
class DeuxFacteursConnexionController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        return $this->utilisateurEnAttente($request)
            ? view('auth.two-factor-challenge')
            : redirect()->route('login');
    }

    public function store(Request $request, TwoFactorAuthenticationProvider $totp): RedirectResponse
    {
        $user = $this->utilisateurEnAttente($request) ?? abort(redirect()->route('login'));
        $request->validate(['code' => ['nullable', 'string'], 'recovery_code' => ['nullable', 'string']]);

        if ($code = preg_replace('/\s+/', '', (string) $request->input('code'))) {
            $valide = $totp->verify(Fortify::currentEncrypter()->decrypt($user->two_factor_secret), $code);
            $champ = 'code';
        } else {
            $recuperation = trim((string) $request->input('recovery_code'));
            $valide = $recuperation !== '' && in_array($recuperation, $user->recoveryCodes(), true);
            // Un code de récupération ne sert qu'une fois : il est remplacé.
            $valide && $user->replaceRecoveryCode($recuperation);
            $champ = 'recovery_code';
        }

        if (! $valide) {
            throw ValidationException::withMessages([$champ => $champ === 'code' ? 'Code incorrect ou expiré.' : 'Code de récupération invalide.']);
        }

        Auth::login($user, $request->session()->pull('connexion.2fa.remember', false));
        $request->session()->forget('connexion.2fa.id');
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function utilisateurEnAttente(Request $request): ?User
    {
        $id = $request->session()->get('connexion.2fa.id');

        return $id ? User::find($id) : null;
    }
}
