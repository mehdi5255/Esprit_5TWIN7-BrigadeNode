<?php

namespace App\Http\Controllers\Transformations\Atelier;

use App\Enums\TypeService;
use App\Http\Controllers\Controller;
use App\Models\Ateliers\Atelier;
use App\Models\Transformations\Inspiration;
use App\Models\Vetements\CategorieVetement;
use App\Support\Photo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** CRUD des inspirations d'un atelier : réservé aux ateliers qui proposent la transformation. */
class InspirationController extends Controller
{
    public function index(Request $request)
    {
        $atelier = $this->atelier($request);

        return view('back.transformations.inspirations.index', [
            'inspirations' => $atelier->inspirations()->with('categorie')->withCount('demandes')->latest()->get(),
            'peutPublier' => $atelier->propose(TypeService::TRANSFORMATION),
        ]);
    }

    public function create(Request $request)
    {
        $this->exigerTransformation($request);

        return view('back.transformations.inspirations.create', ['inspiration' => new Inspiration, 'categories' => CategorieVetement::actives()->get()]);
    }

    public function store(Request $request)
    {
        $atelier = $this->exigerTransformation($request);
        $data = $this->valider($request);
        $data['photo'] = Photo::store($request->file('photo'), 'inspirations');
        $inspiration = $atelier->inspirations()->create($data);

        return redirect()->route('atelier.inspirations.show', $inspiration)->with('success', "Inspiration « {$inspiration->titre} » publiée.");
    }

    public function show(Request $request, Inspiration $inspiration)
    {
        $this->autoriser($request, $inspiration);
        $inspiration->load(['categorie', 'demandes' => fn ($q) => $q->with(['particulier', 'vetement'])->latest()]);

        return view('back.transformations.inspirations.show', ['inspiration' => $inspiration]);
    }

    public function edit(Request $request, Inspiration $inspiration)
    {
        $this->autoriser($request, $inspiration);

        return view('back.transformations.inspirations.edit', ['inspiration' => $inspiration, 'categories' => CategorieVetement::actives()->get()]);
    }

    public function update(Request $request, Inspiration $inspiration)
    {
        $this->autoriser($request, $inspiration);
        $data = $this->valider($request);
        unset($data['photo']);

        // Nouvelle photo : on remplace l'ancienne et on l'efface du disque.
        if ($request->hasFile('photo')) {
            $ancienne = $inspiration->photo;
            $data['photo'] = Photo::store($request->file('photo'), 'inspirations');
            Photo::delete($ancienne);
        }
        $inspiration->update($data);

        return redirect()->route('atelier.inspirations.show', $inspiration)->with('success', 'Inspiration mise à jour.');
    }

    /** Masquer / republier dans la galerie sans supprimer. */
    public function basculer(Request $request, Inspiration $inspiration)
    {
        $this->autoriser($request, $inspiration);
        $inspiration->update(['actif' => ! $inspiration->actif]);

        return back()->with('success', "Inspiration « {$inspiration->titre} » ".($inspiration->actif ? 'publiée' : 'masquée').'.');
    }

    /** Les demandes déjà faites à partir de ce modèle sont conservées (inspiration_id passe à null). */
    public function destroy(Request $request, Inspiration $inspiration)
    {
        $this->autoriser($request, $inspiration);
        $inspiration->delete();

        return redirect()->route('atelier.inspirations.index')->with('success', "Inspiration « {$inspiration->titre} » supprimée.");
    }

    private function atelier(Request $request): Atelier
    {
        return $request->user()->atelier ?? abort(403);
    }

    private function exigerTransformation(Request $request): Atelier
    {
        $atelier = $this->atelier($request);
        abort_unless($atelier->propose(TypeService::TRANSFORMATION), 403, 'Activez un service de transformation pour publier des inspirations.');

        return $atelier;
    }

    private function autoriser(Request $request, Inspiration $inspiration): void
    {
        abort_unless($inspiration->atelier_id === $request->user()->atelier?->id, 403);
    }

    private function valider(Request $request): array
    {
        $data = $request->validate([
            'titre' => ['required', 'string', 'max:120'],
            'categorie_id' => ['required', Rule::exists('categorie_vetements', 'id')->where('actif', true)],
            'description' => ['required', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'prix_indicatif' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'delai_jours' => ['nullable', 'integer', 'min:1', 'max:365'],
        ], [], ['categorie_id' => 'catégorie de vêtement', 'prix_indicatif' => 'prix indicatif']);
        $data['actif'] = $request->boolean('actif');

        return $data;
    }
}
