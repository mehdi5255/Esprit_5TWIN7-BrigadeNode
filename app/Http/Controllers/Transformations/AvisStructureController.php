<?php

namespace App\Http\Controllers\Transformations;

use App\Http\Controllers\Controller;
use App\Models\Transformations\Review;
use Illuminate\Http\Request;

/** Avis reçus par l'atelier ou l'association connecté(e), avec possibilité de répondre. */
class AvisStructureController extends Controller
{
    public function index(Request $request)
    {
        $structure = $this->structure($request);

        return view('back.transformations.avis.index', [
            'structure' => $structure,
            'reviews' => $structure->reviews()->publies()->with(['particulier', 'demande'])->latest()->paginate(15),
            'espace' => $request->user()->role,
        ]);
    }

    public function repondre(Request $request, Review $review)
    {
        $structure = $this->structure($request);
        abort_unless($review->reviewable_type === $structure->getMorphClass() && $review->reviewable_id === $structure->id, 403);

        $data = $request->validateWithBag('reponse_'.$review->id, ['reponse' => ['required', 'string', 'max:1000']]);
        $review->forceFill(['reponse' => $data['reponse'], 'date_reponse' => now()])->save();

        return back()->with('success', 'Votre réponse a été publiée.');
    }

    private function structure(Request $request)
    {
        return $request->user()->atelier ?? $request->user()->association ?? abort(403);
    }
}
