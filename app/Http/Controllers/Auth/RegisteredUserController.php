<?php

namespace App\Http\Controllers\Auth;

use App\Enums\StatutCompte;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Inscription (Laravel Breeze), personnalisée pour TextileCycle :
 * choix du rôle (particulier, atelier, association) et, pour une structure, création de sa fiche.
 */
class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.register', ['role' => $request->input('role', 'particulier')]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'role' => ['required', Rule::in(['particulier', 'atelier', 'association'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'structure_nom' => ['exclude_if:role,particulier', 'required', 'string', 'max:255'],
            'structure_ville' => ['exclude_if:role,particulier', 'required', 'string', 'max:100'],
        ]);
        $estPro = $request->role !== 'particulier';

        // Particulier : compte actif immédiatement. Atelier / association : validation par l'admin.
        $user = DB::transaction(function () use ($request, $estPro) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'statut' => $estPro ? StatutCompte::EN_ATTENTE : StatutCompte::ACTIF,
            ]);

            if ($estPro) {
                $user->{$request->role}()->create([
                    'nom' => $request->structure_nom,
                    'ville' => $request->structure_ville,
                    'email' => $request->email,
                ]);
            }

            return $user;
        });

        // Envoie le lien de vérification de l'adresse e-mail (MustVerifyEmail).
        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false))->with('success', $estPro
            ? 'Bienvenue ! Confirmez votre adresse e-mail, puis complétez votre profil : votre compte sera visible dès sa validation par l’administration.'
            : 'Bienvenue sur TextileCycle ! Confirmez votre adresse e-mail pour commencer.');
    }
}
