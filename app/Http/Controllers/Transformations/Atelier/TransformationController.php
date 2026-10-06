<?php

namespace App\Http\Controllers\Transformations\Atelier;

use App\Http\Controllers\Concerns\ExecuteDesTransitions;
use App\Http\Controllers\Concerns\ListeDesDemandes;
use App\Http\Controllers\Controller;
use App\Models\Transformations\DemandeTransformation;
use App\Support\Photo;
use Illuminate\Http\Request;

class TransformationController extends Controller
{
    use ExecuteDesTransitions, ListeDesDemandes;

    public function index(Request $request)
    {
        $atelier = $request->user()->atelier ?? abort(403);

        return $this->liste($request, $atelier->demandesTransformation()->getQuery(), 'transformations', 'atelier');
    }

    public function show(Request $request, DemandeTransformation $demande)
    {
        $this->autoriser($request, $demande);
        $demande->load(['vetement.categorie', 'atelier', 'particulier', 'review']);

        return view('back.transformations.show', ['demande' => $demande, 'espace' => 'atelier']);
    }

    public function accepter(Request $request, DemandeTransformation $demande)
    {
        $this->autoriser($request, $demande);

        return $this->executer(fn () => $demande->accepter(), 'Projet accepté. Le particulier a été notifié.', $this->retour($demande));
    }

    public function refuser(Request $request, DemandeTransformation $demande)
    {
        $this->autoriser($request, $demande);
        $motif = $request->validate(['motif_refus' => ['required', 'string', 'max:1000']])['motif_refus'];

        return $this->executer(fn () => $demande->refuser($motif), 'Projet refusé. Le particulier a été notifié.', $this->retour($demande));
    }

    public function confirmerDepot(Request $request, DemandeTransformation $demande)
    {
        $this->autoriser($request, $demande);

        // Confirmé depuis la page « Dépôts » : on y revient.
        $retour = $request->input('retour') === 'depots' ? route('atelier.depots.index') : $this->retour($demande);

        return $this->executer(fn () => $demande->confirmerDepot(), 'Remise du vêtement confirmée.', $retour);
    }

    public function demarrer(Request $request, DemandeTransformation $demande)
    {
        $this->autoriser($request, $demande);
        $request->validate(['photo_avant' => ['nullable', 'image', 'max:5120']]);
        $photo = Photo::store($request->file('photo_avant'), 'transformations');

        return $this->executer(fn () => $demande->demarrerTraitement($photo), 'Confection démarrée.', $this->retour($demande));
    }

    public function terminer(Request $request, DemandeTransformation $demande)
    {
        $this->autoriser($request, $demande);
        $data = $request->validate([
            'cout_final' => ['required', 'numeric', 'min:0', 'max:9999'],
            'produit_final' => ['required', 'string', 'max:2000'],
            'photo_apres' => ['nullable', 'image', 'max:5120'],
        ]);
        $photo = Photo::store($request->file('photo_apres'), 'transformations');

        return $this->executer(
            fn () => $demande->terminer((float) $data['cout_final'], $data['produit_final'], $photo),
            'Transformation terminée. Le particulier a été notifié.',
            $this->retour($demande),
        );
    }

    public function confirmerRecuperation(Request $request, DemandeTransformation $demande)
    {
        $this->autoriser($request, $demande);

        return $this->executer(fn () => $demande->confirmerRecuperation(), 'Récupération confirmée. Projet clôturé.', $this->retour($demande));
    }

    private function retour(DemandeTransformation $demande): string
    {
        return route('atelier.transformations.show', $demande);
    }

    private function autoriser(Request $request, DemandeTransformation $demande): void
    {
        abort_unless($demande->atelier_id === $request->user()->atelier?->id, 403);
    }
}
