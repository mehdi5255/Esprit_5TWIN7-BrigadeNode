<?php

namespace App\Http\Controllers\Transformations;

use App\Http\Controllers\Controller;
use App\Models\Ateliers\Atelier;
use App\Models\Transformations\Inspiration;
use App\Models\Vetements\CategorieVetement;
use Illuminate\Http\Request;

/** Galerie publique des inspirations d'upcycling. */
class InspirationController extends Controller
{
    public function index(Request $request)
    {
        $inspirations = Inspiration::visibles()
            ->with(['atelier', 'categorie'])
            ->withCount('demandes')
            ->when($request->integer('categorie'), fn ($q, $categorie) => $q->where('categorie_id', $categorie))
            ->when($request->integer('atelier'), fn ($q, $atelier) => $q->where('atelier_id', $atelier))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('front.transformations.inspirations.index', [
            'inspirations' => $inspirations,
            'categories' => CategorieVetement::actives()->whereHas('inspirations', fn ($q) => $q->visibles())->get(),
            'ateliers' => Atelier::actifs()->whereHas('inspirations', fn ($q) => $q->visibles())->get(),
        ]);
    }

    public function show(Inspiration $inspiration)
    {
        abort_unless($inspiration->estVisible(), 404);
        $inspiration->load(['atelier', 'categorie'])->loadCount('demandes');

        return view('front.transformations.inspirations.show', [
            'inspiration' => $inspiration,
            'autres' => $inspiration->atelier->inspirations()->visibles()->whereKeyNot($inspiration->id)->with('categorie')->latest()->take(3)->get(),
        ]);
    }
}
