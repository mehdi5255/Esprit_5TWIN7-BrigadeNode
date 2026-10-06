<?php

namespace App\Http\Controllers\Reparations;

use App\Enums\StatutVetement;
use App\Enums\TypeReparation;
use App\Enums\TypeService;
use App\Http\Controllers\Concerns\ExecuteDesTransitions;
use App\Http\Controllers\Controller;
use App\Models\Ateliers\Atelier;
use App\Models\Reparations\DemandeReparation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DemandeReparationController extends Controller
{
    use ExecuteDesTransitions;

    public function index(Request $request)
    {
        $demandes = $request->user()->demandesReparation()
            ->with(['vetement', 'atelier'])
            ->when($request->input('statut'), fn ($q, $statut) => $q->where('statut', $statut))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('front.reparations.index', ['demandes' => $demandes]);
    }

    public function create(Request $request)
    {
        return view('front.reparations.create', [
            'vetements' => $request->user()->vetements()->where('statut', StatutVetement::DISPONIBLE)->orderBy('nom')->get(),
            'ateliers' => Atelier::actifs()->proposant(TypeService::REPARATION)->get(),
            'selection' => $request->integer('vetement'),
            'atelierChoisi' => $request->integer('atelier'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vetement_id' => ['required', Rule::exists('vetements', 'id')->where('user_id', $request->user()->id)],
            'atelier_id' => ['required', function ($attribute, $value, $fail) {
                if (! Atelier::actifs()->proposant(TypeService::REPARATION)->whereKey($value)->exists()) {
                    $fail('Cet atelier ne propose pas de réparation.');
                }
            }],
            'type_reparation' => ['required', Rule::enum(TypeReparation::class)],
            'description' => ['required', 'string', 'max:2000'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'date_souhaitee' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $vetement = $request->user()->vetements()->findOrFail($data['vetement_id']);
        $demande = null;

        return $this->executer(
            function () use ($vetement, $data, &$demande) { $demande = DemandeReparation::creer($vetement, $data); },
            'Votre demande a été envoyée à l’atelier.',
            function () use (&$demande) { return route('reparations.show', $demande); },
        );
    }

    public function show(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);
        $demande->load(['vetement.categorie', 'atelier', 'reparation', 'review']);

        return view('front.reparations.show', ['demande' => $demande]);
    }

    public function edit(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);

        if (! $demande->peutEtreModifiee()) {
            return redirect()->route('reparations.show', $demande)->with('error', 'Cette demande a déjà été prise en charge : elle ne peut plus être modifiée.');
        }

        $demande->load(['vetement', 'atelier']);

        return view('front.reparations.edit', ['demande' => $demande]);
    }

    public function update(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);
        $data = $request->validate([
            'type_reparation' => ['required', Rule::enum(TypeReparation::class)],
            'description' => ['required', 'string', 'max:2000'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'date_souhaitee' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        return $this->executer(fn () => $demande->modifier($data), 'Votre demande a été modifiée.', route('reparations.show', $demande));
    }

    public function destroy(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);

        return $this->executer(fn () => $demande->supprimer(), 'La demande a été supprimée.', route('reparations.index'));
    }

    public function annuler(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);

        return $this->executer(fn () => $demande->annuler(), 'La demande a été annulée. Votre vêtement est de nouveau disponible.', route('reparations.show', $demande));
    }

    private function autoriser(Request $request, DemandeReparation $demande): void
    {
        abort_unless($demande->particulier_id === $request->user()->id, 403);
    }
}
