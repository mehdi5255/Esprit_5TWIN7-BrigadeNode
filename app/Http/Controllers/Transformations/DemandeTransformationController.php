<?php

namespace App\Http\Controllers\Transformations;

use App\Enums\StatutVetement;
use App\Enums\TypeService;
use App\Http\Controllers\Concerns\ExecuteDesTransitions;
use App\Http\Controllers\Controller;
use App\Models\Ateliers\Atelier;
use App\Models\Transformations\ConseillerUpcycling;
use App\Models\Transformations\DemandeTransformation;
use App\Models\Transformations\Inspiration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DemandeTransformationController extends Controller
{
    use ExecuteDesTransitions;

    public function index(Request $request)
    {
        $demandes = $request->user()->demandesTransformation()
            ->with(['vetement', 'atelier'])
            ->when($request->input('statut'), fn ($q, $statut) => $q->where('statut', $statut))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('front.transformations.index', ['demandes' => $demandes]);
    }

    public function create(Request $request)
    {
        $vetements = $request->user()->vetements()->where('statut', StatutVetement::DISPONIBLE)->with('categorie')->orderBy('nom')->get();
        // Inspiration choisie depuis la galerie (« Je veux ce projet »).
        $inspirationChoisie = $request->integer('inspiration') ? Inspiration::visibles()->with(['atelier', 'categorie'])->find($request->integer('inspiration')) : null;
        $compatibles = $inspirationChoisie ? $vetements->where('categorie_id', $inspirationChoisie->categorie_id) : collect();

        return view('front.transformations.create', [
            'vetements' => $vetements,
            'ateliers' => Atelier::actifs()->proposant(TypeService::TRANSFORMATION)->get(),
            // Suggestions : les inspirations des catégories de vêtements que le particulier peut transformer.
            'inspirations' => Inspiration::visibles()->with(['atelier', 'categorie'])
                ->where(fn ($q) => $q->whereIn('categorie_id', $vetements->pluck('categorie_id'))->orWhere('id', $inspirationChoisie?->id))
                ->latest()->get(),
            'inspirationChoisie' => $inspirationChoisie,
            // Un seul vêtement compatible avec l'inspiration choisie : on le présélectionne.
            'selection' => $request->integer('vetement') ?: ($compatibles->count() === 1 ? $compatibles->first()->id : null),
            'atelierChoisi' => $request->integer('atelier'),
            // Projet choisi dans l'assistant IA : idée préremplie et aperçu généré joint à la demande.
            'ideeIa' => $request->string('idee')->limit(2000)->value() ?: null,
            'apercu' => ConseillerUpcycling::apercuAppartientA($request->input('apercu'), $request->user()->id) ? $request->input('apercu') : null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vetement_id' => ['required', Rule::exists('vetements', 'id')->where('user_id', $request->user()->id)],
            'inspiration_id' => ['nullable', 'integer', function ($attribute, $value, $fail) {
                if (! Inspiration::visibles()->whereKey($value)->exists()) {
                    $fail('Cette inspiration n’est plus disponible.');
                }
            }],
            // Avec une inspiration, l'atelier et l'idée viennent du modèle.
            'atelier_id' => ['required_without:inspiration_id', 'nullable', function ($attribute, $value, $fail) {
                if (! Atelier::actifs()->proposant(TypeService::TRANSFORMATION)->whereKey($value)->exists()) {
                    $fail('Cet atelier ne propose pas de transformation.');
                }
            }],
            'idee' => ['required_without:inspiration_id', 'nullable', 'string', 'max:2000'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'date_souhaitee' => ['nullable', 'date', 'after_or_equal:today'],
            'apercu_ia' => ['nullable', 'string', function ($attribute, $value, $fail) use ($request) {
                if (! ConseillerUpcycling::apercuAppartientA($value, $request->user()->id)) {
                    $fail('Cet aperçu n’est plus disponible.');
                }
            }],
        ], [], ['inspiration_id' => 'inspiration', 'atelier_id' => 'atelier', 'idee' => 'idée']);

        $vetement = $request->user()->vetements()->findOrFail($data['vetement_id']);

        if ($data['inspiration_id'] ?? null) {
            $inspiration = Inspiration::with('categorie')->findOrFail($data['inspiration_id']);
            if ($vetement->categorie_id !== $inspiration->categorie_id) {
                throw ValidationException::withMessages(['inspiration_id' => "Cette inspiration concerne la catégorie « {$inspiration->categorie->libelle} » : choisissez un vêtement de cette catégorie, ou votre propre idée."]);
            }
            $data['atelier_id'] = $inspiration->atelier_id;
            $data['idee'] = ($data['idee'] ?? null) ?: $inspiration->ideePreremplie();
        }
        $demande = null;

        return $this->executer(
            function () use ($vetement, $data, &$demande) { $demande = DemandeTransformation::creer($vetement, $data); },
            'Votre projet a été envoyé à l’atelier.',
            function () use (&$demande) { return route('transformations.show', $demande); },
        );
    }

    public function show(Request $request, DemandeTransformation $demande)
    {
        $this->autoriser($request, $demande);
        $demande->load(['vetement.categorie', 'atelier', 'review', 'inspiration']);

        return view('front.transformations.show', ['demande' => $demande]);
    }

    public function edit(Request $request, DemandeTransformation $demande)
    {
        $this->autoriser($request, $demande);

        if (! $demande->peutEtreModifiee()) {
            return redirect()->route('transformations.show', $demande)->with('error', 'Cette demande a déjà été prise en charge : elle ne peut plus être modifiée.');
        }

        $demande->load(['vetement', 'atelier']);

        return view('front.transformations.edit', ['demande' => $demande]);
    }

    public function update(Request $request, DemandeTransformation $demande)
    {
        $this->autoriser($request, $demande);
        $data = $request->validate([
            'idee' => ['required', 'string', 'max:2000'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'date_souhaitee' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        return $this->executer(fn () => $demande->modifier($data), 'Votre projet a été modifié.', route('transformations.show', $demande));
    }

    public function destroy(Request $request, DemandeTransformation $demande)
    {
        $this->autoriser($request, $demande);

        return $this->executer(fn () => $demande->supprimer(), 'Le projet a été supprimé.', route('transformations.index'));
    }

    public function annuler(Request $request, DemandeTransformation $demande)
    {
        $this->autoriser($request, $demande);

        return $this->executer(fn () => $demande->annuler(), 'Le projet a été annulé. Votre vêtement est de nouveau disponible.', route('transformations.show', $demande));
    }

    private function autoriser(Request $request, DemandeTransformation $demande): void
    {
        abort_unless($demande->particulier_id === $request->user()->id, 403);
    }
}
