<?php

namespace App\Http\Controllers\Dons\Association;

use App\Enums\OrientationDon;
use App\Enums\TypeEvenement;
use App\Http\Controllers\Concerns\ExecuteDesTransitions;
use App\Http\Controllers\Concerns\ListeDesDemandes;
use App\Http\Controllers\Controller;
use App\Models\Dons\ControleQualite;
use App\Models\Dons\DemandeDon;
use App\Models\Dons\Evenement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DonController extends Controller
{
    use ExecuteDesTransitions, ListeDesDemandes;

    public function index(Request $request)
    {
        $association = $request->user()->association ?? abort(403);

        return $this->liste($request, $association->demandesDon()->getQuery(), 'dons', 'association');
    }

    public function show(Request $request, DemandeDon $demande)
    {
        $this->autoriser($request, $demande);
        $demande->load(['vetement.categorie', 'association', 'particulier', 'besoin', 'review', 'evenements']);

        return view('back.dons.show', [
            'demande' => $demande,
            'espace' => 'association',
            // Distributions proposées à la remise : non annulées, récentes ou à venir.
            'distributions' => $demande->association->evenements()->deType(TypeEvenement::DISTRIBUTION)
                ->where('annule', false)->where('fin', '>=', now()->subDays(30))->orderBy('debut')->get(),
        ]);
    }

    public function accepter(Request $request, DemandeDon $demande)
    {
        $this->autoriser($request, $demande);

        return $this->executer(fn () => $demande->accepter(), 'Don accepté. Le particulier a été notifié.', $this->retour($demande));
    }

    public function refuser(Request $request, DemandeDon $demande)
    {
        $this->autoriser($request, $demande);
        $motif = $request->validate(['motif_refus' => ['required', 'string', 'max:1000']])['motif_refus'];

        return $this->executer(fn () => $demande->refuser($motif), 'Don refusé. Le particulier a été notifié.', $this->retour($demande));
    }

    public function confirmerDepot(Request $request, DemandeDon $demande)
    {
        $this->autoriser($request, $demande);

        // Confirmé depuis la page « Dépôts » ou depuis une collecte : on y revient.
        $retour = match (true) {
            $request->input('retour') === 'depots' => route('association.depots.index'),
            $request->filled('evenement') => route('association.evenements.show', $request->integer('evenement')),
            default => $this->retour($demande),
        };

        return $this->executer(fn () => $demande->confirmerDepot(), 'Réception du vêtement confirmée.', $retour);
    }

    /** Orientation proposée par l'IA à partir de la photo du vêtement reçu (JSON). */
    public function analyserOrientation(Request $request, DemandeDon $demande)
    {
        $this->autoriser($request, $demande);
        $request->validate(['photo' => ['nullable', 'image', 'max:5120']]);
        $resultat = (new ControleQualite)->orienter($demande, $request->file('photo'));

        return $resultat === null
            ? response()->json(['erreur' => 'L’analyse automatique est indisponible : choisissez l’orientation vous-même.'], 503)
            : response()->json($resultat, isset($resultat['erreur']) ? 422 : 200);
    }

    /** L'association valide l'orientation du don (proposée par l'IA ou choisie elle-même). */
    public function orienter(Request $request, DemandeDon $demande)
    {
        $this->autoriser($request, $demande);
        $data = $request->validate([
            'orientation' => ['required', Rule::enum(OrientationDon::class)],
            'orientation_motif' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->executer(
            fn () => $demande->orienter(OrientationDon::from($data['orientation']), $data['orientation_motif'] ?? null),
            'Orientation enregistrée.',
            $this->retour($demande),
        );
    }

    public function preparer(Request $request, DemandeDon $demande)
    {
        $this->autoriser($request, $demande);

        return $this->executer(fn () => $demande->preparer(), 'Don en préparation.', $this->retour($demande));
    }

    public function remettre(Request $request, DemandeDon $demande)
    {
        $this->autoriser($request, $demande);
        $data = $request->validate([
            'beneficiaire_info' => ['required', 'string', 'max:1000'],
            'nombre_beneficiaires' => ['required', 'integer', 'min:1', 'max:1000'],
            'distribution_id' => ['nullable', Rule::exists('evenements', 'id')->where('association_id', $demande->association_id)->where('type', TypeEvenement::DISTRIBUTION->value)->where('annule', 0)],
        ], ['distribution_id.exists' => 'Choisissez une distribution de votre association, non annulée.']);
        $distribution = isset($data['distribution_id']) ? Evenement::find($data['distribution_id']) : null;

        return $this->executer(
            fn () => $demande->remettre($data['beneficiaire_info'], (int) $data['nombre_beneficiaires'], $distribution),
            'Don remis aux bénéficiaires. Le donateur a été notifié.',
            $this->retour($demande),
        );
    }

    private function retour(DemandeDon $demande): string
    {
        return route('association.dons.show', $demande);
    }

    private function autoriser(Request $request, DemandeDon $demande): void
    {
        abort_unless($demande->association_id === $request->user()->association?->id, 403);
    }
}
