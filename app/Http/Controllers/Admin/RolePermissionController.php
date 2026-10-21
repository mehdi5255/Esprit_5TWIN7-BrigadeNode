<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatutCompte;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\RolesPermissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rôles et permissions (Spatie Permission) : l'admin ajuste les permissions de chaque rôle
 * et nomme des modérateurs parmi les particuliers.
 */
class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->withCount('users')->get()
            ->sortBy(fn (Role $role) => array_search($role->name, array_keys(RolesPermissions::ROLES)))->values();

        return view('back.commun.roles.index', [
            'roles' => $roles,
            'permissions' => collect(RolesPermissions::PERMISSIONS)->groupBy(fn ($p) => $p[1], preserveKeys: true),
            'moderateurs' => User::where('role', 'moderateur')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        // L'administrateur garde toutes les permissions : on ne peut pas se retirer l'accès à cette page.
        if ($role->name === 'admin') {
            return back()->with('error', 'Le rôle Administrateur a toujours toutes les permissions.');
        }

        $permissions = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => [Rule::in(array_keys(RolesPermissions::PERMISSIONS))],
        ])['permissions'] ?? [];

        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Permissions du rôle « '.(User::ROLES[$role->name] ?? $role->name).' » enregistrées.');
    }

    /** Un particulier actif devient modérateur : il accède à l'administration selon les permissions du rôle. */
    public function nommer(Request $request)
    {
        $email = $request->validate(['email' => ['required', 'email']], [], ['email' => 'e-mail'])['email'];
        $user = User::firstWhere('email', $email);

        if (! $user || $user->role !== 'particulier' || $user->statut !== StatutCompte::ACTIF) {
            return back()->withInput()->withErrors(['email' => 'Seul un compte particulier actif peut devenir modérateur.']);
        }

        $user->update(['role' => 'moderateur']);

        return back()->with('success', "{$user->name} est maintenant modérateur.");
    }

    public function retirer(User $user)
    {
        abort_unless($user->role === 'moderateur', 404);
        $user->update(['role' => 'particulier']);

        return back()->with('success', "{$user->name} n’est plus modérateur : son compte redevient particulier.");
    }
}
