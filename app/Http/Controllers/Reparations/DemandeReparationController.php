<?php

namespace App\Http\Controllers\Reparations;

use App\Enums\StatutVetement;
use App\Enums\TypeReparation;
use App\Enums\TypeService;
use App\Http\Controllers\Concerns\ExecuteDesTransitions;
use App\Http\Controllers\Controller;
use App\Models\Ateliers\Atelier;
use App\Models\Reparations\DemandeReparation;
use App\Models\Reparations\Garantie;
use App\Models\Reparations\PreDiagnostic;
use App\Support\Photo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DemandeReparationController extends Controller
{
    use ExecuteDesTransitions;

    public function index(Request $request)
    {
        $demandes = $request->user()->demandesReparation()
            ->with(['vetement', 'atelier', 'devis'])
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
            'photo_probleme' => ['nullable', 'image', 'max:5120'],
        ]);

        $vetement = $request->user()->vetements()->findOrFail($data['vetement_id']);
        $data['photo_probleme'] = Photo::store($request->file('photo_probleme'), 'reparations/problemes');
        $demande = null;

        return $this->executer(
            function () use ($vetement, $data, &$demande) {
                $demande = DemandeReparation::creer($vetement, $data);
            },
            'Votre demande a été envoyée à l’atelier.',
            function () use (&$demande) {
                return route('reparations.show', $demande);
            },
        );
    }

    /** Pré-diagnostic IA (JSON) : type, description, gravité et fourchette de prix à partir de la photo. */
    public function preDiagnostic(Request $request)
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
            'vetement_id' => ['nullable', Rule::exists('vetements', 'id')->where('user_id', $request->user()->id)],
            'atelier_id' => ['nullable', 'integer'],
        ]);
        $vetement = isset($data['vetement_id']) ? $request->user()->vetements()->find($data['vetement_id']) : null;
        $resultat = (new PreDiagnostic)->analyser($request->file('photo'), $vetement, $data['atelier_id'] ?? null);

        return $resultat === null
            ? response()->json(['erreur' => 'L’analyse automatique est indisponible pour le moment : décrivez le problème vous-même.'], 503)
            : response()->json($resultat, isset($resultat['erreur']) ? 422 : 200);
    }

    public function show(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);
        $demande->load(['vetement.categorie', 'atelier', 'reparation', 'review', 'devis', 'facture.garantie.retours', 'garantie.facture.demande']);

        return view('front.reparations.show', ['demande' => $demande]);
    }

    public function accepterDevis(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);

        return $this->executer(fn () => $demande->accepterDevis(), 'Devis accepté ! Vous pouvez déposer votre vêtement à l’atelier.', route('reparations.show', $demande));
    }

    public function refuserDevis(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);

        return $this->executer(fn () => $demande->refuserDevis(), 'Devis refusé : la demande est annulée et votre vêtement est de nouveau disponible.', route('reparations.show', $demande));
    }

    /** Facture imprimable. */
    public function facture(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);
        abort_unless($demande->facture, 404);
        $demande->load(['vetement', 'atelier', 'particulier', 'reparation', 'devis', 'facture.garantie']);

        return view('front.reparations.facture', ['demande' => $demande, 'retour' => route('reparations.show', $demande)]);
    }

    /** Retour sous garantie : crée une nouvelle demande gratuite chez le même atelier. */
    public function ouvrirRetour(Request $request, Garantie $garantie)
    {
        $origine = $garantie->demandeOrigine();
        abort_unless($origine->particulier_id === $request->user()->id, 403);
        $description = $request->validate([
            'description' => ['required', 'string', 'min:10', 'max:2000'],
        ], [], ['description' => 'description du problème'])['description'];
        $retour = null;

        return $this->executer(
            function () use ($garantie, $description, &$retour) {
                $retour = DemandeReparation::ouvrirRetour($garantie, $description);
            },
            'Retour sous garantie envoyé à l’atelier.',
            function () use (&$retour) {
                return route('reparations.show', $retour);
            },
        );
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
