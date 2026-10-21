<?php

namespace App\Http\Controllers\Vetements\Admin;

use App\Enums\TypeMatiere;
use App\Http\Controllers\Controller;
use App\Models\Vetements\Matiere;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** CRUD admin du catalogue des matières (impact par kg, abréviations des étiquettes, entretien). */
class MatiereController extends Controller
{
    public function index(Request $request)
    {
        return view('back.vetements.matieres.index', [
            'matieres' => Matiere::withCount('vetements')
                ->when($request->string('q')->trim()->value(), fn ($q, $recherche) => $q->where('nom', 'like', "%{$recherche}%"))
                ->orderBy('nom')
                ->get(),
        ]);
    }

    public function create()
    {
        return view('back.vetements.matieres.create', ['matiere' => new Matiere(['type' => TypeMatiere::NATURELLE])]);
    }

    public function store(Request $request)
    {
        $matiere = Matiere::create($this->valider($request));

        return redirect()->route('admin.matieres.index')->with('success', "Matière « {$matiere->nom} » ajoutée au catalogue.");
    }

    public function edit(Matiere $matiere)
    {
        return view('back.vetements.matieres.edit', ['matiere' => $matiere->loadCount('vetements')]);
    }

    public function update(Request $request, Matiere $matiere)
    {
        $matiere->update($this->valider($request, $matiere));

        return redirect()->route('admin.matieres.index')->with('success', "Matière « {$matiere->nom} » mise à jour.");
    }

    /** Une matière désactivée n'est plus proposée, mais les compositions existantes restent intactes. */
    public function basculer(Matiere $matiere)
    {
        $matiere->update(['actif' => ! $matiere->actif]);

        return back()->with('success', "Matière « {$matiere->nom} » ".($matiere->actif ? 'activée' : 'désactivée').'.');
    }

    public function destroy(Matiere $matiere)
    {
        if ($matiere->vetements()->exists()) {
            return back()->with('error', "« {$matiere->nom} » entre dans la composition de vêtements : désactivez-la plutôt que de la supprimer.");
        }
        $matiere->delete();

        return redirect()->route('admin.matieres.index')->with('success', "Matière « {$matiere->nom} » supprimée.");
    }

    private function valider(Request $request, ?Matiere $matiere = null): array
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:60', Rule::unique('matieres', 'nom')->ignore($matiere)],
            'type' => ['required', Rule::enum(TypeMatiere::class)],
            'abreviations' => ['nullable', 'string', 'max:255'],
            'co2_kg_par_kg' => ['required', 'numeric', 'min:0', 'max:200'],
            'eau_litres_par_kg' => ['required', 'integer', 'min:0', 'max:50000'],
            'entretien' => ['nullable', 'string', 'max:1000'],
        ], [], ['co2_kg_par_kg' => 'CO₂ par kg', 'eau_litres_par_kg' => 'eau par kg']);

        // « CO, cotton, algodón » → ['CO', 'cotton', 'algodón']
        $data['abreviations'] = collect(explode(',', (string) ($data['abreviations'] ?? '')))->map(fn ($a) => trim($a))->filter()->unique()->values()->all();
        $data['actif'] = $request->boolean('actif');

        return $data;
    }
}
