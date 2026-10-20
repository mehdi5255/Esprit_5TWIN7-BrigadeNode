<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

/**
 * Authentification à deux facteurs (Laravel Fortify), gérée par l'utilisateur dans « Mon compte » :
 * activation (secret TOTP + QR code), confirmation par un premier code, codes de récupération, désactivation.
 * Chaque action sensible redemande le mot de passe.
 */
class DeuxFacteursController extends Controller
{
    /** 1. Génère le secret et les codes de récupération ; le QR code s'affiche, à scanner puis confirmer. */
    public function activer(Request $request, EnableTwoFactorAuthentication $activer)
    {
        $this->verifierMotDePasse($request);
        $activer($request->user());

        return back()->with('success', 'Scannez le QR code avec votre application d’authentification, puis saisissez le code affiché.');
    }

    /** 2. Un premier code valide prouve que l'application est bien configurée : la 2FA devient active. */
    public function confirmer(Request $request, ConfirmTwoFactorAuthentication $confirmer)
    {
        $code = $request->validate(['code' => ['required', 'string']], [], ['code' => 'code'])['code'];
        $confirmer($request->user(), preg_replace('/\s+/', '', $code));

        return back()->with('success', 'Double authentification activée. Conservez vos codes de récupération en lieu sûr.')
            ->with('afficher_codes_2fa', true);
    }

    public function regenererCodes(Request $request, GenerateNewRecoveryCodes $regenerer)
    {
        $this->verifierMotDePasse($request);
        $regenerer($request->user());

        return back()->with('success', 'Nouveaux codes de récupération générés : les anciens ne fonctionnent plus.')
            ->with('afficher_codes_2fa', true);
    }

    /** Désactive (ou annule une activation non confirmée). */
    public function desactiver(Request $request, DisableTwoFactorAuthentication $desactiver)
    {
        $this->verifierMotDePasse($request);
        $desactiver($request->user());

        return back()->with('success', 'Double authentification désactivée.');
    }

    private function verifierMotDePasse(Request $request): void
    {
        $request->validateWithBag('deuxFacteurs', ['mot_de_passe_2fa' => ['required', 'current_password']], [], ['mot_de_passe_2fa' => 'mot de passe']);
    }
}
