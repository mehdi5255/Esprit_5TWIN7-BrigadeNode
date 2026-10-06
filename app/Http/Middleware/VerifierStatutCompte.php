<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Déconnecte immédiatement un utilisateur bloqué par l'admin. */
class VerifierStatutCompte
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->estBloque()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Votre compte a été bloqué. Contactez l’administration de TextileCycle.');
        }

        return $next($request);
    }
}
