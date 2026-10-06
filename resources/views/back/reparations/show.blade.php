@extends('layouts.back')
@use('App\Enums\StatutDemande')
@section('title', 'Demande ' . $demande->reference())
@section('breadcrumb', ($espace === 'admin' ? 'Supervision' : 'Demandes') . ' / Réparations / ' . $demande->reference())
@section('content')
@php
    $primaire = 'inline-flex w-full items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-forest-700';
    $action = fn ($nom) => route("atelier.reparations.{$nom}", $demande);
@endphp
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        @include('partials.back.demandes.entete', [
            'texte' => $demande->type_reparation->label().' — '.$demande->description,
            'details' => [
                'Budget max' => $demande->budget_max ? number_format($demande->budget_max, 2, ',', ' ').' €' : '—',
                'Date souhaitée' => $demande->date_souhaitee?->format('d/m/Y') ?? '—',
                'Atelier' => $demande->atelier->nom,
            ],
        ])
        @if($demande->reparation)
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-display text-lg font-bold">Réparation</h2>
            @include('partials.demandes.compte-rendu', ['reparation' => $demande->reparation])
            @if($demande->cout_final !== null)<p class="mt-4 text-sm"><span class="text-slate-500">Coût final :</span> <span class="font-semibold">{{ number_format($demande->cout_final, 2, ',', ' ') }} €</span></p>@endif
        </section>
        @endif
        @include('partials.back.demandes.avis')
    </div>

    <aside>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-display text-lg font-bold">{{ $espace === 'admin' ? 'Statut' : 'Prochaine action' }}</h2>
            <div class="mt-4 space-y-4 text-sm">
            @if($espace === 'admin')
                <p class="text-slate-500">Cette demande est traitée par <span class="font-semibold text-slate-700">{{ $demande->atelier->nom }}</span>. L'administration consulte sans intervenir.</p>
            @else
            @switch($demande->statut)
                @case(StatutDemande::EN_ATTENTE)
                    @include('partials.back.demandes.decision', ['texte' => 'Acceptez ou refusez la demande. Le particulier sera notifié.', 'routeAccepter' => $action('accepter'), 'routeRefuser' => $action('refuser')])
                    @break
                @case(StatutDemande::ACCEPTEE)
                    <p class="text-slate-500">En attente du dépôt du vêtement par le particulier.</p>
                    <form method="POST" action="{{ $action('depot') }}">@csrf<button class="{{ $primaire }}"><i data-lucide="package-check" class="h-4 w-4"></i>Confirmer la remise du vêtement</button></form>
                    @break
                @case(StatutDemande::DEPOT_REMIS)
                    <form method="POST" action="{{ $action('demarrer') }}" enctype="multipart/form-data" class="space-y-4">@csrf
                        <x-champ label="Diagnostic" name="diagnostic" type="textarea" :required="true" placeholder="Constat initial…" />
                        <x-champ label="Photo avant" name="photo_avant" type="file" accept="image/*" data-max-ko="5120" />
                        <button class="{{ $primaire }}"><i data-lucide="play" class="h-4 w-4"></i>Démarrer le traitement</button>
                    </form>
                    @break
                @case(StatutDemande::EN_TRAITEMENT)
                    <form method="POST" action="{{ $action('terminer') }}" enctype="multipart/form-data" class="space-y-4">@csrf
                        <x-champ label="Actions réalisées" name="actions_realisees" type="textarea" :required="true" />
                        <x-champ label="Pièces utilisées" name="pieces_utilisees" placeholder="Ex. Fil coton, 2 boutons" />
                        <div class="grid grid-cols-2 gap-3">
                            <x-champ label="Durée (min)" name="duree_reelle" type="number" min="1" />
                            <x-champ label="Coût final (€)" name="cout_final" type="number" :required="true" :value="$demande->budget_max" min="0" step="0.01" />
                        </div>
                        <x-champ label="Photo après" name="photo_apres" type="file" accept="image/*" data-max-ko="5120" />
                        <button class="{{ $primaire }}"><i data-lucide="check-check" class="h-4 w-4"></i>Marquer comme prêt</button>
                    </form>
                    @break
                @case(StatutDemande::PRET)
                    <p class="text-slate-500">Le particulier a été prévenu que son vêtement est prêt.</p>
                    <form method="POST" action="{{ $action('recuperation') }}">@csrf<button class="{{ $primaire }}"><i data-lucide="hand-helping" class="h-4 w-4"></i>Confirmer la récupération</button></form>
                    @break
                @default
                    <p class="text-slate-500">Cette demande est clôturée ({{ mb_strtolower($demande->statut->label()) }}). Aucune action requise.</p>
            @endswitch
            @endif
            </div>
        </section>
    </aside>
</div>
@endsection
