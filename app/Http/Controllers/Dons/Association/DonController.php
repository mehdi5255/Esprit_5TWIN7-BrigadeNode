<?php

namespace App\Http\Controllers\Dons\Association;

use App\Http\Controllers\Concerns\ExecuteDesTransitions;
use App\Http\Controllers\Concerns\ListeDesDemandes;
use App\Http\Controllers\Controller;
use App\Models\Dons\DemandeDon;
use Illuminate\Http\Request;

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
        $demande->load(['vetement.categorie', 'association', 'particulier', 'besoin', 'review']);

        return view('back.dons.show', ['demande' => $demande, 'espace' => 'association']);
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

        // Confirmé depuis la page « Dépôts » : on y revient.
        $retour = $request->input('retour') === 'depots' ? route('association.depots.index') : $this->retour($demande);

        return $this->executer(fn () => $demande->confirmerDepot(), 'Réception du vêtement confirmée.', $retour);
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
        ]);

        return $this->executer(
            fn () => $demande->remettre($data['beneficiaire_info'], (int) $data['nombre_beneficiaires']),
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
