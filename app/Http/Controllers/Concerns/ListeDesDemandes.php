<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Liste filtrable de demandes, partagée par l'admin (supervision) et les espaces atelier / association. */
trait ListeDesDemandes
{
    /**
     * @param  string  $type    reparations | transformations | dons
     * @param  string  $espace  admin | atelier | association
     */
    protected function liste(Request $request, Builder $base, string $type, string $espace)
    {
        $structure = $type === 'dons' ? 'association' : 'atelier';

        $demandes = (clone $base)
            ->with(['vetement', 'particulier', $structure])
            ->when($request->input('statut'), fn ($q, $statut) => $q->where('statut', $statut))
            ->when($request->string('q')->trim()->value(), function ($q, $search) {
                $q->where(fn ($q) => $q
                    ->whereHas('vetement', fn ($v) => $v->where('nom', 'like', "%{$search}%"))
                    ->orWhereHas('particulier', fn ($p) => $p->where('name', 'like', "%{$search}%")));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $compteurs = (clone $base)->toBase()->reorder()->selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut');

        return view("back.{$type}.index", compact('demandes', 'compteurs', 'espace'));
    }
}
