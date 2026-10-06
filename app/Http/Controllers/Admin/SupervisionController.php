<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ListeDesDemandes;
use App\Http\Controllers\Controller;
use App\Models\Dons\DemandeDon;
use App\Models\Reparations\DemandeReparation;
use App\Models\Transformations\DemandeTransformation;
use Illuminate\Http\Request;

/** Consultation (lecture seule) de toutes les demandes : l'admin n'agit pas à la place des structures. */
class SupervisionController extends Controller
{
    use ListeDesDemandes;

    private const TYPES = [
        'reparations' => DemandeReparation::class,
        'transformations' => DemandeTransformation::class,
        'dons' => DemandeDon::class,
    ];

    public function index(Request $request, string $type)
    {
        return $this->liste($request, self::TYPES[$type]::query(), $type, 'admin');
    }

    public function show(string $id)
    {
        $type = request()->route('type');
        $demande = self::TYPES[$type]::with(['vetement.categorie', 'particulier', 'structure', 'review'])->findOrFail($id);

        if ($demande instanceof DemandeReparation) {
            $demande->load('reparation');
        }

        return view("back.{$type}.show", ['demande' => $demande, 'espace' => 'admin']);
    }
}
