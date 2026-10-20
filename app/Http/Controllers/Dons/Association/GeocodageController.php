<?php

namespace App\Http\Controllers\Dons\Association;

use App\Http\Controllers\Controller;
use App\Support\Geocodage;
use Illuminate\Http\Request;

/** Recherche d'adresse sur la carte pour placer le lieu d'un événement (JSON). */
class GeocodageController extends Controller
{
    public function rechercher(Request $request)
    {
        $recherche = $request->validate(['q' => ['required', 'string', 'min:3', 'max:255']])['q'];

        return response()->json(Geocodage::rechercher($recherche));
    }

    public function adresse(Request $request)
    {
        $position = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        return response()->json(['adresse' => Geocodage::adresse((float) $position['lat'], (float) $position['lng'])]);
    }
}
