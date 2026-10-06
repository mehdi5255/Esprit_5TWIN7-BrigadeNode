<?php

namespace App\Http\Controllers\Transformations;

use App\Http\Controllers\Controller;
use App\Models\Dons\DemandeDon;
use App\Models\Reparations\DemandeReparation;
use App\Models\Transformations\DemandeTransformation;
use App\Models\Transformations\Review;
use Illuminate\Http\Request;

/** Le particulier note l'atelier / l'association après une demande terminée. */
class ReviewController extends Controller
{
    private const TYPES = [
        'reparations' => DemandeReparation::class,
        'transformations' => DemandeTransformation::class,
        'dons' => DemandeDon::class,
    ];

    /** Avis laissés par le particulier connecté. */
    public function index(Request $request)
    {
        return view('front.transformations.avis.index', [
            'reviews' => $request->user()->reviews()->with(['reviewable', 'demande.vetement'])->latest()->paginate(10),
        ]);
    }

    public function store(Request $request, string $type, string $id)
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        $demande = self::TYPES[$type]::findOrFail($id);
        abort_unless($demande->particulier_id === $request->user()->id, 403);

        if (! $demande->peutEtreNotee()) {
            return back()->with('error', 'Vous ne pouvez pas (ou plus) laisser d’avis sur cette demande.');
        }

        $data = $request->validate([
            'note' => ['required', 'integer', 'between:1,5'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ]);

        $review = $demande->review()->make($data);
        $review->particulier()->associate($request->user());
        $review->reviewable()->associate($demande->structureModel());
        $review->save();

        return back()->with('success', 'Merci pour votre avis !');
    }

    /** L'auteur modifie son avis (la réponse éventuelle de la structure est conservée). */
    public function update(Request $request, Review $review)
    {
        abort_unless($review->particulier_id === $request->user()->id, 403);

        $review->update($request->validate([
            'note' => ['required', 'integer', 'between:1,5'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ]));

        return back()->with('success', 'Votre avis a été modifié.');
    }

    public function destroy(Request $request, Review $review)
    {
        abort_unless($review->particulier_id === $request->user()->id, 403);
        $review->delete();

        return back()->with('success', 'Votre avis a été supprimé.');
    }
}
