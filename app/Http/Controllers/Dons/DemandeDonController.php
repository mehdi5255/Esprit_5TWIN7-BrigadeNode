<?php

namespace App\Http\Controllers\Dons;

use App\Enums\StatutVetement;
use App\Http\Controllers\Concerns\ExecuteDesTransitions;
use App\Http\Controllers\Controller;
use App\Models\Dons\Association;
use App\Models\Dons\BesoinAssociation;
use App\Models\Dons\DemandeDon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DemandeDonController extends Controller
{
    use ExecuteDesTransitions;

    public function index(Request $request)
    {
        $demandes = $request->user()->demandesDon()
            ->with(['vetement', 'association', 'besoin'])
            ->when($request->input('statut'), fn ($q, $statut) => $q->where('statut', $statut))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('front.dons.index', ['demandes' => $demandes]);
    }

    public function create(Request $request)
    {
        $besoin = $request->integer('besoin') ? BesoinAssociation::with('association')->find($request->integer('besoin')) : null;

        return view('front.dons.create', [
            'vetements' => $request->user()->vetements()->where('statut', StatutVetement::DISPONIBLE)->orderBy('nom')->get(),
            'associations' => Association::actifs()->with('besoinsActifs')->get(),
            'selection' => $request->integer('vetement'),
            'associationChoisie' => $besoin?->association_id ?? $request->integer('association'),
            'besoinChoisi' => $besoin?->id,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vetement_id' => ['required', Rule::exists('vetements', 'id')->where('user_id', $request->user()->id)],
            'association_id' => ['required', function ($attribute, $value, $fail) {
                if (! Association::actifs()->whereKey($value)->exists()) {
                    $fail("Cette association n'accepte pas de dons pour le moment.");
                }
            }],
            'besoin_id' => ['nullable', Rule::exists('besoin_associations', 'id')->where('association_id', $request->input('association_id'))->where('actif', true)],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $vetement = $request->user()->vetements()->findOrFail($data['vetement_id']);
        $demande = null;

        return $this->executer(
            function () use ($vetement, $data, &$demande) { $demande = DemandeDon::creer($vetement, $data); },
            'Merci ! Votre proposition de don a été envoyée à l’association.',
            function () use (&$demande) { return route('dons.show', $demande); },
        );
    }

    public function show(Request $request, DemandeDon $demande)
    {
        $this->autoriser($request, $demande);
        $demande->load(['vetement.categorie', 'association', 'besoin', 'review']);

        return view('front.dons.show', ['demande' => $demande]);
    }

    public function edit(Request $request, DemandeDon $demande)
    {
        $this->autoriser($request, $demande);

        if (! $demande->peutEtreModifiee()) {
            return redirect()->route('dons.show', $demande)->with('error', 'Cette demande a déjà été prise en charge : elle ne peut plus être modifiée.');
        }

        $demande->load(['vetement', 'association']);

        return view('front.dons.edit', ['demande' => $demande, 'besoins' => $demande->association->besoinsActifs()->get()]);
    }

    public function update(Request $request, DemandeDon $demande)
    {
        $this->autoriser($request, $demande);
        $data = $request->validate([
            'besoin_id' => ['nullable', Rule::exists('besoin_associations', 'id')->where('association_id', $demande->association_id)->where('actif', true)],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->executer(fn () => $demande->modifier($data), 'Votre proposition de don a été modifiée.', route('dons.show', $demande));
    }

    public function destroy(Request $request, DemandeDon $demande)
    {
        $this->autoriser($request, $demande);

        return $this->executer(fn () => $demande->supprimer(), 'La proposition de don a été supprimée.', route('dons.index'));
    }

    public function annuler(Request $request, DemandeDon $demande)
    {
        $this->autoriser($request, $demande);

        return $this->executer(fn () => $demande->annuler(), 'Le don a été annulé. Votre vêtement est de nouveau disponible.', route('dons.show', $demande));
    }

    private function autoriser(Request $request, DemandeDon $demande): void
    {
        abort_unless($demande->particulier_id === $request->user()->id, 403);
    }
}
