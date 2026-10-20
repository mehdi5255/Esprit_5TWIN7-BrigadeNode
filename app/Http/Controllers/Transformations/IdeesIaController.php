<?php

namespace App\Http\Controllers\Transformations;

use App\Enums\StatutVetement;
use App\Http\Controllers\Controller;
use App\Models\Transformations\ConseillerUpcycling;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** « Que faire de ce vêtement ? » : idées de transformation et aperçu du résultat, par l'IA. */
class IdeesIaController extends Controller
{
    public function index(Request $request)
    {
        return view('front.transformations.idees', [
            'vetements' => $request->user()->vetements()->where('statut', StatutVetement::DISPONIBLE)->with('categorie')->orderBy('nom')->get(),
            'selection' => $request->integer('vetement') ?: null,
        ]);
    }

    /** Trois idées de projet pour le vêtement choisi (JSON). */
    public function idees(Request $request)
    {
        $vetement = $this->vetement($request);

        return response()->json((new ConseillerUpcycling)->idees($vetement));
    }

    /** Aperçu du vêtement transformé selon l'idée choisie (JSON). */
    public function apercu(Request $request)
    {
        $vetement = $this->vetement($request);
        $idee = $request->validate(['idee' => ['required', 'string', 'max:600']])['idee'];

        return response()->json((new ConseillerUpcycling)->apercu($vetement, $idee));
    }

    private function vetement(Request $request)
    {
        $id = $request->validate([
            'vetement_id' => ['required', Rule::exists('vetements', 'id')->where('user_id', $request->user()->id)],
        ])['vetement_id'];

        return $request->user()->vetements()->findOrFail($id);
    }
}
