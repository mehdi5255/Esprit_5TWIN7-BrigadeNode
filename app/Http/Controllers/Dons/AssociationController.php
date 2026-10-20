<?php

namespace App\Http\Controllers\Dons;

use App\Http\Controllers\Controller;
use App\Models\Dons\Association;
use App\Models\Dons\BesoinAssociation;
use Illuminate\Http\Request;

class AssociationController extends Controller
{
    public function index(Request $request)
    {
        $associations = Association::actifs()
            ->avecNote()
            ->with('besoinsActifs')
            ->when($request->string('q')->trim()->value(), fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('nom', 'like', "%{$search}%")
                ->orWhere('domaine_action', 'like', "%{$search}%")
                ->orWhere('ville', 'like', "%{$search}%")
                ->orWhere('code_postal', 'like', "{$search}%")))
            ->paginate(12)
            ->withQueryString();

        return view('front.dons.associations.index', ['associations' => $associations]);
    }

    public function show(Association $association)
    {
        abort_unless($association->estActif(), 404);

        return view('front.dons.associations.show', [
            'association' => Association::avecNote()->with('besoinsActifs.categorie')->find($association->id),
            'reviews' => $association->reviews()->publies()->with('particulier')->latest()->paginate(10),
            'evenements' => $association->evenements()->aVenir()->orderBy('debut')->take(4)->get(),
        ]);
    }

    /** Tous les besoins actifs des associations validées. */
    public function besoins(Request $request)
    {
        $besoins = BesoinAssociation::where('actif', true)
            ->whereHas('association', fn ($q) => $q->actifs())
            ->with(['association', 'categorie'])
            ->when($request->input('categorie'), fn ($q, $categorie) => $q->where('categorie_id', $categorie))
            ->when($request->input('priorite'), fn ($q, $priorite) => $q->where('priorite', $priorite))
            ->orderByRaw("case priorite when 'urgente' then 1 when 'haute' then 2 when 'moyenne' then 3 else 4 end")
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('front.dons.besoins.index', [
            'besoins' => $besoins,
            'categories' => \App\Models\Vetements\CategorieVetement::actives()->get(),
        ]);
    }
}
