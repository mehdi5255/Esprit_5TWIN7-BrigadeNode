<?php

namespace App\Http\Controllers\Reparations\Atelier;

use App\Http\Controllers\Concerns\ExecuteDesTransitions;
use App\Http\Controllers\Concerns\ListeDesDemandes;
use App\Http\Controllers\Controller;
use App\Models\Reparations\DemandeReparation;
use App\Support\Photo;
use Illuminate\Http\Request;

class ReparationController extends Controller
{
    use ExecuteDesTransitions, ListeDesDemandes;

    public function index(Request $request)
    {
        $atelier = $request->user()->atelier ?? abort(403);

        return $this->liste($request, $atelier->demandesReparation()->getQuery(), 'reparations', 'atelier');
    }

    public function show(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);
        $demande->load(['vetement.categorie', 'atelier', 'particulier', 'reparation', 'review']);

        return view('back.reparations.show', ['demande' => $demande, 'espace' => 'atelier']);
    }

    public function accepter(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);

        return $this->executer(fn () => $demande->accepter(), 'Demande acceptée. Le particulier a été notifié.', $this->retour($demande));
    }

    public function refuser(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);
        $motif = $request->validate(['motif_refus' => ['required', 'string', 'max:1000']])['motif_refus'];

        return $this->executer(fn () => $demande->refuser($motif), 'Demande refusée. Le particulier a été notifié.', $this->retour($demande));
    }

    public function confirmerDepot(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);

        // Confirmé depuis la page « Dépôts » : on y revient.
        $retour = $request->input('retour') === 'depots' ? route('atelier.depots.index') : $this->retour($demande);

        return $this->executer(fn () => $demande->confirmerDepot(), 'Remise du vêtement confirmée.', $retour);
    }

    public function demarrer(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);
        $data = $request->validate([
            'diagnostic' => ['required', 'string', 'max:2000'],
            'photo_avant' => ['nullable', 'image', 'max:5120'],
        ]);
        $data['photo_avant'] = Photo::store($request->file('photo_avant'), 'reparations');

        return $this->executer(fn () => $demande->demarrerTraitement($data), 'Traitement démarré.', $this->retour($demande));
    }

    public function terminer(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);
        $data = $request->validate([
            'cout_final' => ['required', 'numeric', 'min:0', 'max:9999'],
            'actions_realisees' => ['required', 'string', 'max:2000'],
            'pieces_utilisees' => ['nullable', 'string', 'max:2000'],
            'duree_reelle' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'photo_apres' => ['nullable', 'image', 'max:5120'],
        ]);
        $cout = (float) $data['cout_final'];
        unset($data['cout_final'], $data['photo_apres']);
        if ($request->hasFile('photo_apres')) {
            $data['photo_apres'] = Photo::store($request->file('photo_apres'), 'reparations');
        }

        return $this->executer(fn () => $demande->terminer($cout, $data), 'Réparation terminée. Le particulier a été notifié.', $this->retour($demande));
    }

    public function confirmerRecuperation(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);

        return $this->executer(fn () => $demande->confirmerRecuperation(), 'Récupération confirmée. Demande clôturée.', $this->retour($demande));
    }

    private function retour(DemandeReparation $demande): string
    {
        return route('atelier.reparations.show', $demande);
    }

    private function autoriser(Request $request, DemandeReparation $demande): void
    {
        abort_unless($demande->atelier_id === $request->user()->atelier?->id, 403);
    }
}
