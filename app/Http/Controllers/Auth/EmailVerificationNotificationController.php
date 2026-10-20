<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $request->user()->sendEmailVerificationNotification();

        // Personnalisation : on ne prétend pas avoir envoyé un e-mail qui n'est pas parti.
        if ($request->user()->echecEnvoiVerification) {
            return back()->with('error', 'L’e-mail de vérification n’a pas pu être envoyé pour le moment. Réessayez dans quelques minutes avec « Renvoyer le lien ».');
        }

        return back()->with('status', 'verification-link-sent');
    }
}
