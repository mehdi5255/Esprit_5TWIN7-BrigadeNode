<?php

namespace App\Http\Controllers\Reparations\Atelier;

use App\Enums\StatutFacture;
use App\Http\Controllers\Controller;
use App\Models\Reparations\Facture;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Factures émises par l'atelier, avec le montant encaissé et le reste à encaisser. */
class FactureController extends Controller
{
    public function index(Request $request)
    {
        $atelier = $request->user()->atelier ?? abort(403);
        $request->validate(['statut' => ['nullable', Rule::enum(StatutFacture::class)]]);

        $base = Facture::whereHas('demande', fn ($q) => $q->where('atelier_id', $atelier->id));

        return view('back.reparations.factures', [
            'factures' => (clone $base)
                ->with(['demande.particulier', 'demande.vetement', 'garantie'])
                ->when($request->input('statut'), fn ($q, $statut) => $q->where('statut', $statut))
                ->latest('date_emission')
                ->paginate(15)
                ->withQueryString(),
            'totaux' => [
                'encaisse' => (float) (clone $base)->where('statut', StatutFacture::PAYEE)->sum('montant_total'),
                'a_encaisser' => (float) (clone $base)->where('statut', StatutFacture::EMISE)->sum('montant_total'),
                'nombre' => (clone $base)->count(),
            ],
        ]);
    }
}
