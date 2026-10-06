<?php

namespace App\Http\Controllers\Auth;

use App\Enums\StatutCompte;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function create(Request $request)
    {
        return view('front.commun.auth.register', ['role' => $request->input('role', 'particulier')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['particulier', 'atelier', 'association'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'structure_nom' => ['exclude_if:role,particulier', 'required', 'string', 'max:255'],
            'structure_ville' => ['exclude_if:role,particulier', 'required', 'string', 'max:100'],
        ]);

        $estPro = $data['role'] !== 'particulier';

        // Particulier : compte actif immédiatement. Atelier / association : validation par l'admin.
        $user = DB::transaction(function () use ($data, $estPro) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
                'statut' => $estPro ? StatutCompte::EN_ATTENTE : StatutCompte::ACTIF,
            ]);

            if ($estPro) {
                $user->{$data['role']}()->create([
                    'nom' => $data['structure_nom'],
                    'ville' => $data['structure_ville'],
                    'email' => $data['email'],
                ]);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($user->homeRoute())->with('success', $estPro
            ? 'Bienvenue ! Complétez votre profil : votre compte sera visible dès sa validation par l’administration.'
            : 'Bienvenue sur TextileCycle ! Ajoutez votre premier vêtement.');
    }
}
