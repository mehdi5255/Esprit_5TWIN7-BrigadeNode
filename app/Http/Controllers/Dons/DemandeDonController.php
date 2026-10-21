<?php

namespace App\Http\Controllers\Dons;

use App\Enums\StatutVetement;
use App\Enums\TypeEvenement;
use App\Http\Controllers\Concerns\ExecuteDesTransitions;
use App\Http\Controllers\Controller;
use App\Models\Dons\Association;
use App\Models\Dons\BesoinAssociation;
use App\Models\Dons\ControleQualite;
use App\Models\Dons\DemandeDon;
use App\Models\Dons\Evenement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            'associations' => Association::actifs()->with('besoinsActifs.categorie')->get(),
            // Collectes à venir où le particulier peut déposer son don (complètes affichées mais désactivées).
            'collectes' => Evenement::visibles()->deType(TypeEvenement::COLLECTE)->orderBy('debut')->get(),
            'selection' => $request->integer('vetement'),
            'associationChoisie' => $besoin?->association_id ?? $request->integer('association') ?: Evenement::find($request->integer('collecte'))?->association_id,
            'besoinChoisi' => $besoin?->id,
            'collecteChoisie' => $request->integer('collecte') ?: null,
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
            // Dépôt lors d'une collecte de la même association (vide = dépôt au local).
            'collecte_id' => ['nullable', Rule::exists('evenements', 'id')->where('association_id', $request->input('association_id'))->where('type', TypeEvenement::COLLECTE->value)],
            'message' => ['nullable', 'string', 'max:1000'],
            'controle_ia' => ['nullable', Rule::in(array_keys(ControleQualite::VERDICTS))],
        ], ['collecte_id.exists' => 'Choisissez une collecte organisée par cette association.']);

        $vetement = $request->user()->vetements()->with('categorie')->findOrFail($data['vetement_id']);
        // Un besoin rattaché à une catégorie n'accepte que les vêtements de cette catégorie.
        if (($besoin = isset($data['besoin_id']) ? BesoinAssociation::with('categorie')->find($data['besoin_id']) : null)
            && $message = ControleQualite::incompatibilite($vetement, $besoin)) {
            throw ValidationException::withMessages(['besoin_id' => $message]);
        }
        $collecte = isset($data['collecte_id']) ? Evenement::find($data['collecte_id']) : null;
        unset($data['collecte_id']);
        $demande = null;

        return $this->executer(
            function () use ($vetement, $data, $collecte, &$demande) {
                DB::transaction(function () use ($vetement, $data, $collecte, &$demande) {
                    $demande = DemandeDon::creer($vetement, $data);
                    $collecte && $demande->rattacherCollecte($collecte);
                });
            },
            'Merci ! Votre proposition de don a été envoyée à l’association.',
            function () use (&$demande) {
                return route('dons.show', $demande);
            },
        );
    }

    /** Contrôle qualité IA (JSON) : le vêtement est-il donnable tel quel ? */
    public function controle(Request $request)
    {
        $data = $request->validate([
            'vetement_id' => ['required', Rule::exists('vetements', 'id')->where('user_id', $request->user()->id)],
            'photo' => ['nullable', 'image', 'max:5120'],
            'besoin_id' => ['nullable', Rule::exists('besoin_associations', 'id')->where('actif', true)],
        ]);
        $vetement = $request->user()->vetements()->findOrFail($data['vetement_id']);
        $besoin = isset($data['besoin_id']) ? BesoinAssociation::find($data['besoin_id']) : null;
        $resultat = (new ControleQualite)->verifier($vetement, $request->file('photo'), $besoin);

        if ($resultat === null) {
            return response()->json(['erreur' => 'La vérification automatique est indisponible pour le moment.'], 503);
        }
        if (isset($resultat['erreur'])) {
            return response()->json($resultat, 422);
        }

        // Liens vers les autres modules quand le vêtement n'est pas donnable tel quel.
        return response()->json($resultat + ['liens' => [
            'reparation' => route('reparations.create', ['vetement' => $vetement->id]),
            'transformation' => route('transformations.idees', ['vetement' => $vetement->id]),
        ]]);
    }

    public function show(Request $request, DemandeDon $demande)
    {
        $this->autoriser($request, $demande);
        $demande->load(['vetement.categorie', 'association', 'besoin', 'review', 'evenements']);

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
        if (($besoin = isset($data['besoin_id']) ? BesoinAssociation::with('categorie')->find($data['besoin_id']) : null)
            && $message = ControleQualite::incompatibilite($demande->vetement->loadMissing('categorie'), $besoin)) {
            throw ValidationException::withMessages(['besoin_id' => $message]);
        }

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
