<?php

namespace App\Http\Controllers\Ateliers\Atelier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $atelier = $request->user()->atelier ?? abort(403);

        $dernieres = collect()
            ->concat($atelier->demandesReparation()->with(['vetement', 'particulier'])->latest()->limit(6)->get())
            ->concat($atelier->demandesTransformation()->with(['vetement', 'particulier'])->latest()->limit(6)->get())
            ->sortByDesc('created_at')->take(8)->values();

        return view('back.ateliers.dashboard', [
            'atelier' => $atelier,
            'stats' => $atelier->statistiques(),
            'dernieres' => $dernieres,
            'avis' => $atelier->reviews()->publies()->with('particulier')->latest()->limit(3)->get(),
        ]);
    }
}
