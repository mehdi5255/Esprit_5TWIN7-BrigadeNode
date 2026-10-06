<?php

namespace App\Http\Controllers\Transformations\Admin;

use App\Enums\StatutReview;
use App\Http\Controllers\Controller;
use App\Models\Transformations\Review;
use Illuminate\Http\Request;

/** Modération des avis. */
class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = Review::with(['particulier', 'reviewable'])
            ->when($request->input('statut'), fn ($q, $statut) => $q->where('statut', $statut))
            ->when($request->input('note'), fn ($q, $note) => $q->where('note', '<=', $note))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $publies = Review::publies();

        return view('back.transformations.reviews.index', [
            'reviews' => $reviews,
            'stats' => [
                'moyenne' => ($m = (clone $publies)->avg('note')) ? round($m, 1) : null,
                'publies' => (clone $publies)->count(),
                'masques' => Review::where('statut', StatutReview::MASQUE)->count(),
                'reponses' => Review::whereNotNull('reponse')->count(),
            ],
        ]);
    }

    /** Masquer un avis inapproprié, ou le republier. */
    public function basculer(Review $review)
    {
        $review->forceFill(['statut' => $review->statut === StatutReview::PUBLIE ? StatutReview::MASQUE : StatutReview::PUBLIE])->save();

        return back()->with('success', $review->statut === StatutReview::MASQUE ? 'Avis masqué.' : 'Avis republié.');
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return back()->with('success', 'Avis supprimé.');
    }
}
