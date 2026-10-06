<?php

namespace App\Http\Controllers\Transformations;

use App\Enums\StatutVetement;
use App\Enums\TypeService;
use App\Http\Controllers\Concerns\ExecuteDesTransitions;
use App\Http\Controllers\Controller;
use App\Models\Ateliers\Atelier;
use App\Models\Transformations\DemandeTransformation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        return view('front.transformations.create', [
            'vetements' => $request->user()->vetements()->where('statut', StatutVetement::DISPONIBLE)->orderBy('nom')->get(),
            'ateliers' => Atelier::actifs()->proposant(TypeService::TRANSFORMATION)->get(),
            'selection' => $request->integer('vetement'),
            'atelierChoisi' => $request->integer('atelier'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vetement_id' => ['required', Rule::exists('vetements', 'id')->where('user_id', $request->user()->id)],
            'atelier_id' => ['required', function ($attribute, $value, $fail) {
                if (! Atelier::actifs()->proposant(TypeService::TRANSFORMATION)->whereKey($value)->exists()) {
                    $fail('Cet atelier ne propose pas de transformation.');
                }
            }],
            'idee' => ['required', 'string', 'max:2000'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'date_souhaitee' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $vetement = $request->user()->vetements()->findOrFail($data['vetement_id']);
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
        $demande->load(['vetement.categorie', 'atelier', 'review']);

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
