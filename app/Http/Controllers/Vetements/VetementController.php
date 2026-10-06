<?php

namespace App\Http\Controllers\Vetements;

use App\Enums\EtatVetement;
use App\Enums\StatutVetement;
use App\Http\Controllers\Controller;
use App\Models\Vetements\CategorieVetement;
use App\Models\Vetements\Vetement;
use App\Support\Photo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VetementController extends Controller
{
    public function index(Request $request)
    {
        $vetements = $request->user()->vetements()
            ->with('categorie')
            ->when($request->string('q')->trim()->value(), fn ($q, $search) => $q->where('nom', 'like', "%{$search}%"))
            ->when($request->input('categorie'), fn ($q, $categorie) => $q->where('categorie_id', $categorie))
            ->when($request->input('etat'), fn ($q, $etat) => $q->where('etat', $etat))
            ->when($request->input('statut'), fn ($q, $statut) => $q->where('statut', $statut))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('front.vetements.index', [
            'vetements' => $vetements,
            'categories' => CategorieVetement::actives()->get(),
        ]);
    }

    public function create()
    {
        return view('front.vetements.create', [
            'vetement' => new Vetement(),
            'categories' => CategorieVetement::actives()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['photo'] = Photo::store($request->file('photo'), 'vetements');
        $data['statut'] = StatutVetement::DISPONIBLE;

        $vetement = $request->user()->vetements()->create($data);

        return redirect()->route('vetements.show', $vetement)->with('success', "« {$vetement->nom} » a été ajouté à votre dressing.");
    }

    public function show(Request $request, Vetement $vetement)
    {
        $this->autoriser($request, $vetement);
        $vetement->load('categorie');

        return view('front.vetements.show', ['vetement' => $vetement, 'historique' => $vetement->historique()]);
    }

    public function edit(Request $request, Vetement $vetement)
    {
        $this->autoriser($request, $vetement);

        return view('front.vetements.edit', [
            'vetement' => $vetement,
            'categories' => CategorieVetement::actives()->get(),
        ]);
    }

    public function update(Request $request, Vetement $vetement)
    {
        $this->autoriser($request, $vetement);
        $data = $this->validated($request);

        if ($request->hasFile('photo')) {
            Photo::delete($vetement->photo);
            $data['photo'] = Photo::store($request->file('photo'), 'vetements');
        } else {
            unset($data['photo']);
        }

        $vetement->update($data);

        return redirect()->route('vetements.show', $vetement)->with('success', 'Le vêtement a été mis à jour.');
    }

    public function destroy(Request $request, Vetement $vetement)
    {
        $this->autoriser($request, $vetement);

        if (! in_array($vetement->statut, [StatutVetement::DISPONIBLE, StatutVetement::TERMINE], true)) {
            return back()->with('error', 'Ce vêtement fait l’objet d’une demande en cours et ne peut pas être supprimé.');
        }

        $vetement->delete();

        return redirect()->route('vetements.index')->with('success', "« {$vetement->nom} » a été supprimé.");
    }

    private function autoriser(Request $request, Vetement $vetement): void
    {
        abort_unless($vetement->user_id === $request->user()->id, 403);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'categorie_id' => ['required', Rule::exists('categorie_vetements', 'id')->where('actif', true)],
            'matiere' => ['nullable', 'string', 'max:100'],
            'taille' => ['nullable', 'string', 'max:20'],
            'couleur' => ['nullable', 'string', 'max:50'],
            'etat' => ['required', Rule::enum(EtatVetement::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);
    }
}
