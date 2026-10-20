<?php

namespace App\Http\Controllers;

use App\Models\Ateliers\Atelier;
use App\Models\Dons\Association;
use App\Models\Vetements\Vetement;

/** Page d'accueil : chiffres réels de la plateforme (vêtements sauvés, impact évité, partenaires). */
class AccueilController extends Controller
{
    public function __invoke()
    {
        return view('front.commun.home', [
            'impact' => Vetement::impactEconomise(Vetement::query()),
            'partenaires' => Atelier::actifs()->count() + Association::actifs()->count(),
        ]);
    }
}
