<?php

namespace App\Http\Controllers\Ateliers;

use App\Enums\TypeService;
use App\Http\Controllers\Controller;
use App\Models\Ateliers\Atelier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AtelierController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['service' => ['nullable', Rule::enum(TypeService::class)]]);

        $ateliers = Atelier::actifs()
            ->avecNote()
            ->with(['services' => fn ($q) => $q->where('actif', true)])
            ->when($request->string('q')->trim()->value(), fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('nom', 'like', "%{$search}%")
                ->orWhere('specialite', 'like', "%{$search}%")
                ->orWhere('ville', 'like', "%{$search}%")
                ->orWhere('code_postal', 'like', "{$search}%")))
            ->when($request->input('service'), fn ($q, $service) => $q->proposant(TypeService::from($service)))
            ->paginate(12)
            ->withQueryString();

        return view('front.ateliers.index', ['ateliers' => $ateliers]);
    }

    public function show(Atelier $atelier)
    {
        abort_unless($atelier->estActif(), 404);

        return view('front.ateliers.show', [
            'atelier' => Atelier::avecNote()->with(['services' => fn ($q) => $q->where('actif', true)->orderBy('type')])->find($atelier->id),
            'reviews' => $atelier->reviews()->publies()->with('particulier')->latest()->paginate(10),
        ]);
    }
}
