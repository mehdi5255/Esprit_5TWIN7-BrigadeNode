<?php

namespace App\Http\Controllers\Vetements;

use App\Enums\EtatVetement;
use App\Enums\StatutVetement;
use App\Http\Controllers\Controller;
use App\Models\Vetements\CategorieVetement;
use App\Models\Vetements\Matiere;
use App\Models\Vetements\Vetement;
use App\Support\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'impact' => Vetement::impactEconomise($request->user()->vetements()->getQuery()),
        ]);
    }

    public function create()
    {
        return view('front.vetements.create', [
            'vetement' => new Vetement,
            'categories' => CategorieVetement::actives()->get(),
        ] + $this->donneesComposition());
    }

    public function store(Request $request)
    {
        [$data, $composition] = $this->validated($request);
        $data['photo'] = Photo::store($request->file('photo'), 'vetements');
        $data['statut'] = StatutVetement::DISPONIBLE;

        $vetement = DB::transaction(function () use ($request, $data, $composition) {
            $vetement = $request->user()->vetements()->create($data);
            $vetement->definirComposition($composition);

            return $vetement;
        });

        return redirect()->route('vetements.show', $vetement)->with('success', "« {$vetement->nom} » a été ajouté à votre dressing.");
    }

    public function show(Request $request, Vetement $vetement)
    {
        $this->autoriser($request, $vetement);
        $vetement->load(['categorie', 'matieres']);

        return view('front.vetements.show', ['vetement' => $vetement, 'historique' => $vetement->historique()]);
    }

    public function edit(Request $request, Vetement $vetement)
    {
        $this->autoriser($request, $vetement);

        return view('front.vetements.edit', [
            'vetement' => $vetement->load('matieres'),
            'categories' => CategorieVetement::actives()->get(),
        ] + $this->donneesComposition($vetement));
    }

    public function update(Request $request, Vetement $vetement)
    {
        $this->autoriser($request, $vetement);
        [$data, $composition] = $this->validated($request);

        if ($request->hasFile('photo')) {
            Photo::delete($vetement->photo);
            $data['photo'] = Photo::store($request->file('photo'), 'vetements');
        } else {
            unset($data['photo']);
        }

        DB::transaction(function () use ($vetement, $data, $composition) {
            $vetement->update($data);
            $vetement->definirComposition($composition);
        });

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

    /**
     * Valide le vêtement et sa composition.
     *
     * @return array{0: array, 1: array<int, int>} [données du vêtement, [matiere_id => pourcentage]]
     */
    private function validated(Request $request): array
    {
        // Les lignes de composition laissées vides sont ignorées.
        $request->merge(['composition' => collect($request->input('composition', []))
            ->filter(fn ($ligne) => filled($ligne['matiere_id'] ?? null) || filled($ligne['pourcentage'] ?? null))
            ->values()->all()]);

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'categorie_id' => ['required', Rule::exists('categorie_vetements', 'id')->where('actif', true)],
            'taille' => ['nullable', 'string', 'max:20'],
            'couleur' => ['nullable', 'string', 'max:50'],
            'etat' => ['required', Rule::enum(EtatVetement::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'poids_grammes' => ['nullable', 'required_with:composition', 'integer', 'min:10', 'max:20000'],
            'composition_estimee' => ['boolean'],
            // Composition : jusqu'à 6 matières différentes, dont la somme fait 100 %.
            'composition' => ['array', 'max:6', function ($attribute, $value, $fail) {
                $total = collect($value)->sum(fn ($ligne) => (int) ($ligne['pourcentage'] ?? 0));
                if ($value && $total !== 100) {
                    $fail("La composition doit faire 100 % (actuellement {$total} %).");
                }
            }],
            'composition.*.matiere_id' => ['required', 'distinct', Rule::exists('matieres', 'id')->where('actif', 1)],
            'composition.*.pourcentage' => ['required', 'integer', 'between:1,100'],
        ], [
            'composition.*.matiere_id.distinct' => 'Chaque matière ne doit apparaître qu’une fois.',
            'composition.*.matiere_id.required' => 'Choisissez la matière de chaque ligne.',
            'composition.*.pourcentage.required' => 'Indiquez le pourcentage de chaque matière.',
            'poids_grammes.required_with' => 'Indiquez le poids du vêtement pour calculer son impact.',
        ], ['poids_grammes' => 'poids', 'composition.*.pourcentage' => 'pourcentage']);

        $composition = collect($data['composition'] ?? [])->mapWithKeys(fn ($ligne) => [(int) $ligne['matiere_id'] => (int) $ligne['pourcentage']])->all();
        $data['composition_estimee'] = $composition && $request->boolean('composition_estimee');
        unset($data['composition']);

        return [$data, $composition];
    }

    /** Catalogue des matières et compositions typiques par catégorie, pour le formulaire. */
    private function donneesComposition(?Vetement $vetement = null): array
    {
        $categories = CategorieVetement::actives()->get();

        return [
            // Matières actives, plus celles déjà utilisées par le vêtement (même désactivées depuis).
            'matieres' => Matiere::actives()->get()->merge($vetement?->matieres ?? collect())->unique('id')->sortBy('nom')->values(),
            'typiques' => $categories->mapWithKeys(function (CategorieVetement $categorie) {
                [$poids, $composition] = Matiere::compositionTypique($categorie->libelle);

                return [$categorie->id => ['poids' => $poids, 'composition' => $composition]];
            }),
        ];
    }
}
