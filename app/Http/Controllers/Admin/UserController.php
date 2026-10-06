<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatutCompte;
use App\Enums\StatutStructure;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Modules;
use Illuminate\Http\Request;

/**
 * Gestion des comptes. Conformément au RGPD, l'admin ne modifie pas les données
 * personnelles : il consulte, valide, bloque/débloque ou supprime un compte.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->with(User::relationsStructure())
            ->when($request->string('q')->trim()->value(), fn ($query, $search) => $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($request->input('role'), fn ($query, $role) => $query->where('role', $role))
            ->when($request->input('statut'), fn ($query, $statut) => $query->where('statut', $statut))
            ->orderByRaw("case statut when 'en_attente' then 0 else 1 end")
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'stats' => [
                'total' => User::count(),
                'en_attente' => User::where('statut', StatutCompte::EN_ATTENTE)->count(),
                'bloques' => User::where('statut', StatutCompte::BLOQUE)->count(),
            ],
        ]);
    }

    public function show(User $user)
    {
        $user->load(User::relationsStructure())->loadCount(array_values(Modules::filtrer([
            'vetements' => 'vetements',
            'reparations' => 'demandesReparation',
            'transformations' => 'demandesTransformation',
            'dons' => 'demandesDon',
            'avis' => 'reviews',
        ])));

        return view('admin.users.show', ['user' => $user]);
    }

    /** Valide un compte atelier / association en attente, et rend sa structure visible. */
    public function valider(User $user)
    {
        abort_unless($user->estEnAttente(), 422);
        $this->changerStatut($user, StatutCompte::ACTIF, StatutStructure::ACTIF);

        return back()->with('success', "Le compte de {$user->name} a été validé.");
    }

    public function bloquer(Request $request, User $user)
    {
        abort_if($user->is($request->user()) || $user->hasRole('admin'), 403, 'Un administrateur ne peut pas être bloqué.');
        $this->changerStatut($user, StatutCompte::BLOQUE, StatutStructure::DESACTIVE);

        return back()->with('success', "Le compte de {$user->name} a été bloqué.");
    }

    public function debloquer(User $user)
    {
        abort_unless($user->estBloque(), 422);
        $this->changerStatut($user, StatutCompte::ACTIF, StatutStructure::ACTIF);

        return back()->with('success', "Le compte de {$user->name} a été débloqué.");
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->is($request->user()) || $user->hasRole('admin'), 403, 'Un administrateur ne peut pas être supprimé.');

        if ($user->aUnHistorique()) {
            return back()->with('error', "Le compte de {$user->name} a un historique de demandes : bloquez-le plutôt que de le supprimer.");
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "Le compte de {$user->name} a été supprimé.");
    }

    private function changerStatut(User $user, StatutCompte $compte, StatutStructure $structure): void
    {
        $user->forceFill(['statut' => $compte])->save();
        $user->structure()?->forceFill(['statut' => $structure])->save();
    }
}
