<?php

namespace App\Http\Controllers\Dons\Association;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $association = $request->user()->association ?? abort(403);

        return view('back.dons.dashboard', [
            'association' => $association,
            'stats' => $association->statistiques(),
            'dernieres' => $association->demandesDon()->with(['vetement', 'particulier'])->latest()->limit(8)->get(),
            'besoins' => $association->besoinsActifs()->with('categorie')->limit(4)->get(),
        ]);
    }
}
