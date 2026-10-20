<?php

namespace App\Http\Controllers\Dons\Association;

use App\Enums\PrioriteBesoin;
use App\Enums\StatutDon;
use App\Http\Controllers\Controller;
use App\Models\Dons\BesoinAssociation;
use App\Models\Vetements\CategorieVetement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BesoinController extends Controller
{
    public function index(Request $request)
    {
        $association = $request->user()->association ?? abort(403);

        return view('back.dons.besoins.index', [
            'besoins' => $association->besoins()->with('categorie')
                ->withCount(['dons as dons_recus' => fn ($q) => $q->whereIn('statut', [StatutDon::DEPOT_REMIS, StatutDon::EN_TRAITEMENT, StatutDon::REMIS_BENEFICIAIRE])])
                ->orderByDesc('actif')->latest()->get(),
        ]);
    }

    public function create()
    {
        return view('back.dons.besoins.create', ['besoin' => new BesoinAssociation, 'categories' => CategorieVetement::actives()->get()]);
    }

    public function store(Request $request)
    {
        $association = $request->user()->association ?? abort(403);
        $besoin = $association->besoins()->create($this->validated($request));

        return redirect()->route('association.besoins.index')->with('success', "Besoin « {$besoin->titre} » publié.");
    }

    public function edit(Request $request, BesoinAssociation $besoin)
    {
        $this->autoriser($request, $besoin);

        return view('back.dons.besoins.edit', ['besoin' => $besoin, 'categories' => CategorieVetement::actives()->get()]);
    }

    public function update(Request $request, BesoinAssociation $besoin)
    {
        $this->autoriser($request, $besoin);
        $besoin->update($this->validated($request));

        return redirect()->route('association.besoins.index')->with('success', "Besoin « {$besoin->titre} » mis à jour.");
    }

    /** Désactiver un besoin satisfait, ou le réactiver. */
    public function basculer(Request $request, BesoinAssociation $besoin)
    {
        $this->autoriser($request, $besoin);
        $besoin->update(['actif' => ! $besoin->actif]);

        return back()->with('success', "Besoin « {$besoin->titre} » ".($besoin->actif ? 'réactivé' : 'désactivé').'.');
    }

    public function destroy(Request $request, BesoinAssociation $besoin)
    {
        $this->autoriser($request, $besoin);
        $besoin->delete();

        return redirect()->route('association.besoins.index')->with('success', "Besoin « {$besoin->titre} » supprimé.");
    }

    private function autoriser(Request $request, BesoinAssociation $besoin): void
    {
        abort_unless($besoin->association_id === $request->user()->association?->id, 403);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'categorie_id' => ['nullable', Rule::exists('categorie_vetements', 'id')],
            'description' => ['nullable', 'string', 'max:1000'],
            'quantite' => ['required', 'integer', 'min:1', 'max:10000'],
            'priorite' => ['required', Rule::enum(PrioriteBesoin::class)],
        ]);
        $data['actif'] = $request->boolean('actif');

        return $data;
    }
}
