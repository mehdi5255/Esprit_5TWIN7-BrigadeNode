<?php

namespace App\Http\Controllers\Vetements\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vetements\CategorieVetement;
use Illuminate\Http\Request;

class CategorieVetementController extends Controller
{
    public function index()
    {
        return view('back.vetements.categories.index', [
            'categories' => CategorieVetement::withCount('vetements')->orderBy('libelle')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $categorie = CategorieVetement::create($this->validated($request));

        return redirect()->route('admin.categories.index')->with('success', "Catégorie « {$categorie->libelle} » créée.");
    }

    public function edit(CategorieVetement $categorie)
    {
        return view('back.vetements.categories.edit', ['categorie' => $categorie]);
    }

    public function update(Request $request, CategorieVetement $categorie)
    {
        $categorie->update($this->validated($request, $categorie));

        return redirect()->route('admin.categories.index')->with('success', "Catégorie « {$categorie->libelle} » mise à jour.");
    }

    public function destroy(CategorieVetement $categorie)
    {
        if ($categorie->vetements()->exists()) {
            return back()->with('error', 'Cette catégorie contient des vêtements : désactivez-la plutôt que de la supprimer.');
        }

        $categorie->delete();

        return redirect()->route('admin.categories.index')->with('success', "Catégorie « {$categorie->libelle} » supprimée.");
    }

    private function validated(Request $request, ?CategorieVetement $categorie = null): array
    {
        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:100', 'unique:categorie_vetements,libelle'.($categorie ? ",{$categorie->id}" : '')],
            'description' => ['nullable', 'string', 'max:500'],
            'icone' => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/'],
        ]);
        $data['icone'] = ($data['icone'] ?? null) ?: 'shirt';
        $data['actif'] = $request->boolean('actif');

        return $data;
    }
}
