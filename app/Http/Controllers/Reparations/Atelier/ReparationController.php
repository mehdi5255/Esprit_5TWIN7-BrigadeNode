<?php

namespace App\Http\Controllers\Reparations\Atelier;

use App\Http\Controllers\Concerns\ExecuteDesTransitions;
use App\Http\Controllers\Concerns\ListeDesDemandes;
use App\Http\Controllers\Controller;
use App\Models\Reparations\DemandeReparation;
use App\Models\Reparations\EstimateurDevis;
use App\Support\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

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
        $demande->load(['vetement.categorie', 'atelier', 'particulier', 'reparation', 'review', 'devis', 'facture.garantie.retours', 'garantie.facture.demande']);

        return view('back.reparations.show', ['demande' => $demande, 'espace' => 'atelier']);
    }

    /** Accepter la demande, sans devis : il sera établi après le dépôt et le diagnostic. */
    public function accepter(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);

        return $this->executer(fn () => $demande->accepter(), 'Demande acceptée. Le particulier peut déposer son vêtement.', $this->retour($demande));
    }

    /** Après le dépôt : diagnostic + devis (un retour sous garantie n'a pas de montant à fixer). */
    public function etablirDevis(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);
        $diagnostic = $request->validate([
            'diagnostic' => ['required', 'string', 'max:2000'],
            'photo_avant' => ['nullable', 'image', 'max:5120'],
        ]);
        $devis = $demande->estRetourSousGarantie()
            ? $request->validate(['delai_jours' => ['nullable', 'integer', 'min:1', 'max:365']])
            : $this->validerDevis($request);
        $diagnostic['photo_avant'] = Photo::store($request->file('photo_avant'), 'reparations');
        $message = $demande->estRetourSousGarantie()
            ? 'Diagnostic enregistré. Réparation sous garantie : vous pouvez la démarrer.'
            : 'Diagnostic enregistré et devis envoyé au particulier.';

        return $this->executer(fn () => $demande->etablirDevis($diagnostic, $devis), $message, $this->retour($demande));
    }

    /** Aide au chiffrage (JSON) : estimation à partir du diagnostic et des devis passés comparables. */
    public function estimer(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);
        $diagnostic = $request->validate(['diagnostic' => ['nullable', 'string', 'max:2000']])['diagnostic'] ?? '';

        return response()->json((new EstimateurDevis)->estimer($demande, $diagnostic));
    }

    public function modifierDevis(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);
        $devis = $this->validerDevis($request);

        return $this->executer(fn () => $demande->modifierDevis($devis), 'Devis modifié.', $this->retour($demande));
    }

    /** Facture imprimable. */
    public function facture(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);
        abort_unless($demande->facture, 404);
        $demande->load(['vetement', 'atelier', 'particulier', 'reparation', 'devis', 'facture.garantie']);

        return view('front.reparations.facture', ['demande' => $demande, 'retour' => $this->retour($demande)]);
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

        return $this->executer(fn () => $demande->demarrerTraitement(), 'Réparation démarrée.', $this->retour($demande));
    }

    public function terminer(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);
        $data = $request->validate([
            'supplement' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            // Tout supplément au devis doit être justifié auprès du particulier.
            'motif_supplement' => [Rule::requiredIf(fn () => (float) $request->input('supplement') > 0), 'nullable', 'string', 'max:500'],
            'actions_realisees' => ['required', 'string', 'max:2000'],
            'pieces_utilisees' => ['nullable', 'string', 'max:2000'],
            'duree_reelle' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'photo_apres' => ['nullable', 'image', 'max:5120'],
        ], ['motif_supplement.required' => 'Expliquez au particulier la raison du supplément.']);
        $facture = Arr::only($data, ['supplement', 'motif_supplement']);
        $reparation = Arr::except($data, ['supplement', 'motif_supplement', 'photo_apres']);
        if ($request->hasFile('photo_apres')) {
            $reparation['photo_apres'] = Photo::store($request->file('photo_apres'), 'reparations');
        }

        return $this->executer(fn () => $demande->terminer($facture, $reparation), 'Réparation terminée, facture émise. Le particulier a été notifié.', $this->retour($demande));
    }

    /** Le particulier récupère son vêtement et paie : facture payée, garantie créée. */
    public function confirmerRecuperation(Request $request, DemandeReparation $demande)
    {
        $this->autoriser($request, $demande);

        return $this->executer(fn () => $demande->confirmerRecuperation(), 'Paiement et récupération confirmés. Garantie activée.', $this->retour($demande));
    }

    private function validerDevis(Request $request): array
    {
        return $request->validate([
            'main_oeuvre' => ['required', 'numeric', 'min:0', 'max:9999'],
            'delai_jours' => ['nullable', 'integer', 'min:1', 'max:365'],
            'valide_jusqu_au' => ['required', 'date', 'after_or_equal:today'],
        ], [], ['main_oeuvre' => 'montant du devis', 'valide_jusqu_au' => 'date de validité']);
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
