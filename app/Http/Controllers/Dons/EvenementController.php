<?php

namespace App\Http\Controllers\Dons;

use App\Enums\TypeEvenement;
use App\Http\Controllers\Controller;
use App\Models\Dons\Association;
use App\Models\Dons\Evenement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Agenda public des collectes et distributions à venir. */
class EvenementController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['type' => ['nullable', Rule::enum(TypeEvenement::class)]]);
        $filtres = fn ($q) => $q->visibles()
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->integer('association'), fn ($q, $association) => $q->where('association_id', $association));

        return view('front.dons.evenements.index', [
            'evenements' => Evenement::query()->tap($filtres)
                ->with('association')
                ->withCount('dons')
                ->orderBy('debut')
                ->paginate(12)
                ->withQueryString(),
            // Carte : tous les rendez-vous filtrés placés sur la carte, pas seulement ceux de la page.
            'points' => Evenement::query()->tap($filtres)->with('association')->whereNotNull('latitude')->whereNotNull('longitude')->orderBy('debut')->get()
                ->map(fn (Evenement $e) => [
                    'titre' => $e->titre, 'type' => $e->type->label(), 'horaire' => $e->horaire(), 'lieu' => $e->lieu,
                    'association' => $e->association->nom, 'lat' => $e->latitude, 'lng' => $e->longitude, 'url' => route('evenements.show', $e),
                ])->values(),
            'associations' => Association::actifs()->whereHas('evenements', fn ($q) => $q->visibles())->get(),
        ]);
    }

    public function show(Evenement $evenement)
    {
        abort_unless($evenement->association->estActif(), 404);

        return view('front.dons.evenements.show', ['evenement' => $evenement->loadCount('dons')]);
    }
}
