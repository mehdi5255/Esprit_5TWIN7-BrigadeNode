<?php

namespace App\Http\Controllers\Ateliers\Atelier;

use App\Enums\TypeService;
use App\Http\Controllers\Controller;
use App\Models\Ateliers\AssistantService;
use App\Models\Ateliers\ServiceAtelier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $atelier = $request->user()->atelier ?? abort(403);

        return view('back.ateliers.services.index', [
            'services' => $atelier->services()->orderBy('type')->orderBy('nom')->get(),
        ]);
    }

    public function create()
    {
        return view('back.ateliers.services.create', ['service' => new ServiceAtelier(['type' => TypeService::REPARATION])]);
    }

    public function store(Request $request)
    {
        $atelier = $request->user()->atelier ?? abort(403);
        $service = $atelier->services()->create($this->validated($request));

        return redirect()->route('atelier.services.index')->with('success', "Service « {$service->nom} » créé.");
    }

    /** Suggestions de l'IA pour la fiche du service, d'après son nom et les prix du marché (JSON). */
    public function assistant(Request $request)
    {
        $atelier = $request->user()->atelier ?? abort(403);
        $data = $request->validate([
            'nom' => ['required', 'string', 'min:3', 'max:255'],
            'type' => ['required', Rule::enum(TypeService::class)],
        ]);

        return response()->json((new AssistantService)->suggerer($data['nom'], TypeService::from($data['type']), $atelier));
    }

    public function show(Request $request, ServiceAtelier $service)
    {
        $this->autoriser($request, $service);
        $service->load('atelier');

        return view('back.ateliers.services.show', ['service' => $service]);
    }

    public function edit(Request $request, ServiceAtelier $service)
    {
        $this->autoriser($request, $service);

        return view('back.ateliers.services.edit', ['service' => $service]);
    }

    public function update(Request $request, ServiceAtelier $service)
    {
        $this->autoriser($request, $service);
        $service->update($this->validated($request));

        return redirect()->route('atelier.services.index')->with('success', "Service « {$service->nom} » mis à jour.");
    }

    /** Activer / désactiver un service sans le supprimer. */
    public function basculer(Request $request, ServiceAtelier $service)
    {
        $this->autoriser($request, $service);
        $service->update(['actif' => ! $service->actif]);

        return back()->with('success', "Service « {$service->nom} » ".($service->actif ? 'activé' : 'désactivé').'.');
    }

    public function destroy(Request $request, ServiceAtelier $service)
    {
        $this->autoriser($request, $service);
        $service->delete();

        return redirect()->route('atelier.services.index')->with('success', "Service « {$service->nom} » supprimé.");
    }

    private function autoriser(Request $request, ServiceAtelier $service): void
    {
        abort_unless($service->atelier_id === $request->user()->atelier?->id, 403);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(TypeService::class)],
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'prix' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'duree' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'delai_jours' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);
        $data['actif'] = $request->boolean('actif');

        return $data;
    }
}
