<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatutCompte;
use App\Enums\StatutStructure;
use App\Http\Controllers\Controller;
use App\Models\Ateliers\Atelier;
use App\Models\Dons\Association;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/** Ateliers et associations : consultation, statistiques, validation et désactivation. */
class StructureController extends Controller
{
    private const TYPES = [
        'ateliers' => Atelier::class,
        'associations' => Association::class,
    ];

    public function index(Request $request, string $type)
    {
        $classe = self::TYPES[$type];

        $structures = $classe::query()
            ->with('responsable')
            ->avecNote()
            ->when($type === 'ateliers', fn ($q) => $q->withCount(['services' => fn ($s) => $s->where('actif', true), 'demandesReparation', 'demandesTransformation']))
            ->when($type === 'associations', fn ($q) => $q->withCount(['besoins' => fn ($b) => $b->where('actif', true), 'demandesDon']))
            ->when($request->string('q')->trim()->value(), fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('nom', 'like', "%{$search}%")
                ->orWhere('ville', 'like', "%{$search}%")
                ->orWhere('code_postal', 'like', "{$search}%")))
            ->when($request->input('statut'), fn ($q, $statut) => $q->where('statut', $statut))
            ->orderByRaw("case statut when 'en_attente' then 0 else 1 end")
            ->orderBy('nom')
            ->paginate(15)
            ->withQueryString();

        return view('back.commun.structures.index', compact('structures', 'type'));
    }

    public function show(string $id)
    {
        $type = request()->route('type');
        $structure = $this->trouver($type, $id);
        $structure->load($type === 'ateliers' ? ['responsable', 'services'] : ['responsable', 'besoins.categorie']);

        return view('back.commun.structures.show', [
            'structure' => $structure,
            'type' => $type,
            'stats' => $structure->statistiques(),
            'reviews' => $structure->reviews()->with('particulier')->latest()->limit(5)->get(),
        ]);
    }

    /** Valider (ou réactiver) : la structure devient visible et peut recevoir des demandes. */
    public function valider(string $id)
    {
        $type = request()->route('type');
        $structure = $this->trouver($type, $id);

        if ($structure->responsable?->estBloque()) {
            return back()->with('error', 'Le compte du responsable est bloqué : débloquez-le d’abord.');
        }

        $structure->forceFill(['statut' => StatutStructure::ACTIF])->save();
        if ($structure->responsable?->estEnAttente()) {
            $structure->responsable->forceFill(['statut' => StatutCompte::ACTIF])->save();
        }

        return back()->with('success', "{$structure->nom} est maintenant actif.");
    }

    public function desactiver(string $id)
    {
        $type = request()->route('type');
        $structure = $this->trouver($type, $id);
        $structure->forceFill(['statut' => StatutStructure::DESACTIVE])->save();

        return back()->with('success', "{$structure->nom} a été désactivé : il n’est plus visible et ne reçoit plus de demandes.");
    }

    private function trouver(string $type, string $id): Model
    {
        return self::TYPES[$type]::findOrFail($id);
    }
}
