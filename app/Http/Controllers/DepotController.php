<?php

namespace App\Http\Controllers;

use App\Support\Depots;
use Illuminate\Http\Request;

/**
 * Page « Dépôts » : vêtements attendus et reçus.
 * Admin : supervision de toutes les demandes (lecture seule).
 * Atelier / association : ses propres demandes, avec confirmation du dépôt.
 */
class DepotController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $depots = Depots::pour($user, $request->input('type'));

        return view('back.commun.depots.index', [
            'espace' => $user->role,
            'types' => Depots::pour($user)->types(),
            'attendus' => $depots->attendus(),
            'recus' => $depots->recus(),
            'stats' => $depots->statistiques(),
        ]);
    }
}
