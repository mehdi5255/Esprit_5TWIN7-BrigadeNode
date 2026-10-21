<?php

namespace App\Http\Controllers\Dons\Association;

use App\Enums\TypeEvenement;
use App\Http\Controllers\Concerns\ExecuteDesTransitions;
use App\Http\Controllers\Controller;
use App\Models\Dons\Association;
use App\Models\Dons\Evenement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** CRUD des collectes et distributions de l'association. */
class EvenementController extends Controller
{
    use ExecuteDesTransitions;

    public function index(Request $request)
    {
        $evenements = $this->association($request)->evenements()
            ->withCount('dons')
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->orderByDesc('debut')
            ->get();

        return view('back.dons.evenements.index', [
            'aVenir' => $evenements->filter(fn (Evenement $e) => ! $e->annule && ! $e->estPasse())->sortBy('debut'),
            'passes' => $evenements->filter(fn (Evenement $e) => $e->annule || $e->estPasse()),
        ]);
    }

    public function create()
    {
        return view('back.dons.evenements.create', ['evenement' => new Evenement(['type' => TypeEvenement::COLLECTE])]);
    }

    public function store(Request $request)
    {
        $evenement = $this->association($request)->evenements()->create($this->valider($request));

        return redirect()->route('association.evenements.show', $evenement)->with('success', "{$evenement->type->label()} « {$evenement->titre} » publiée.");
    }

    public function show(Request $request, Evenement $evenement)
    {
        $this->autoriser($request, $evenement);
        $evenement->load(['dons' => fn ($q) => $q->with(['particulier', 'vetement'])->latest('demande_dons.created_at')]);

        return view('back.dons.evenements.show', ['evenement' => $evenement]);
    }

    public function edit(Request $request, Evenement $evenement)
    {
        $this->autoriser($request, $evenement);

        return view('back.dons.evenements.edit', ['evenement' => $evenement]);
    }

    public function update(Request $request, Evenement $evenement)
    {
        $this->autoriser($request, $evenement);
        $evenement->update($this->valider($request, $evenement));

        return redirect()->route('association.evenements.show', $evenement)->with('success', 'Événement mis à jour.');
    }

    /** Annuler plutôt que supprimer : les dons déjà rattachés sont libérés et les particuliers prévenus. */
    public function annuler(Request $request, Evenement $evenement)
    {
        $this->autoriser($request, $evenement);

        return $this->executer(fn () => $evenement->annuler(), 'Événement annulé. Les particuliers concernés ont été prévenus.', route('association.evenements.show', $evenement));
    }

    public function destroy(Request $request, Evenement $evenement)
    {
        $this->autoriser($request, $evenement);

        if ($evenement->dons()->exists()) {
            return back()->with('error', 'Des dons sont rattachés à cet événement : annulez-le plutôt que de le supprimer.');
        }
        $evenement->delete();

        return redirect()->route('association.evenements.index')->with('success', "Événement « {$evenement->titre} » supprimé.");
    }

    private function association(Request $request): Association
    {
        return $request->user()->association ?? abort(403);
    }

    private function autoriser(Request $request, Evenement $evenement): void
    {
        abort_unless($evenement->association_id === $request->user()->association?->id, 403);
    }

    private function valider(Request $request, ?Evenement $evenement = null): array
    {
        $dejaInscrits = $evenement?->donsActifs()->count() ?? 0;

        $data = $request->validate([
            'type' => ['required', Rule::enum(TypeEvenement::class)],
            'titre' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'lieu' => ['required', 'string', 'max:255'],
            // Position choisie sur la carte (facultative, mais les deux ensemble).
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            // Un nouvel événement est forcément à venir.
            'debut' => ['required', 'date', $evenement ? 'date' : 'after:now'],
            'fin' => ['required', 'date', 'after:debut'],
            // On ne peut pas descendre sous le nombre de dons déjà rattachés.
            'places' => ['nullable', 'integer', 'min:'.max(1, $dejaInscrits), 'max:1000'],
        ], ['places.min' => $dejaInscrits > 1 ? "{$dejaInscrits} dons sont déjà rattachés : prévoyez au moins {$dejaInscrits} places." : 'Prévoyez au moins une place.'],
            ['debut' => 'date de début', 'fin' => 'date de fin']);

        // Les places ne concernent que les collectes.
        if ($data['type'] === TypeEvenement::DISTRIBUTION->value) {
            $data['places'] = null;
        }

        return $data;
    }
}
