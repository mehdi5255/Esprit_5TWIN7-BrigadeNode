@extends('layouts.back')
@use('App\Enums\StatutDon')
@section('title', $evenement->titre)
@section('breadcrumb', 'Association / Événements / ' . $evenement->type->label())
@section('content')
@php
    $actifs = $evenement->dons->reject(fn ($d) => in_array($d->statut, [StatutDon::REFUSEE, StatutDon::ANNULEE], true));
    $recus = $actifs->where('depot_remis', true)->count();
@endphp
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center gap-2"><x-badge :label="$evenement->type->label()" :color="$evenement->type->color()" /><x-badge :label="$evenement->statutLibelle()" :color="$evenement->statutCouleur()" /></div>
            <p class="mt-4 flex items-center gap-2 text-sm"><i data-lucide="calendar" class="h-4 w-4 text-forest-600"></i>{{ $evenement->horaire() }}</p>
            <p class="mt-1 flex items-center gap-2 text-sm"><i data-lucide="map-pin" class="h-4 w-4 text-forest-600"></i>{{ $evenement->lieu }}</p>
            @include('partials.carte.lieu')
            @if($evenement->description)<p class="mt-4 text-sm leading-6 text-slate-600">{{ $evenement->description }}</p>@endif
            <dl class="mt-6 grid gap-3 text-sm sm:grid-cols-3">
                @if($evenement->estCollecte())
                <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Dons attendus</dt><dd class="mt-1 font-semibold">{{ $actifs->count() }}{{ $evenement->places ? ' / '.$evenement->places : '' }}</dd></div>
                <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Reçus</dt><dd class="mt-1 font-semibold">{{ $recus }}</dd></div>
                <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Places restantes</dt><dd class="mt-1 font-semibold">{{ $evenement->placesRestantes() ?? 'Sans limite' }}</dd></div>
                @else
                <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Dons remis</dt><dd class="mt-1 font-semibold">{{ $evenement->dons->count() }}</dd></div>
                <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Bénéficiaires</dt><dd class="mt-1 font-semibold">{{ $evenement->dons->sum('nombre_beneficiaires') }}</dd></div>
                @endif
            </dl>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <h2 class="border-b border-slate-100 px-6 py-4 font-display text-lg font-bold">{{ $evenement->estCollecte() ? 'Dons à recevoir' : 'Dons remis' }}</h2>
            @forelse($evenement->dons as $don)
            <div class="flex flex-wrap items-center gap-4 border-b border-slate-100 px-6 py-4 text-sm last:border-0">
                <div class="min-w-0 flex-1"><a href="{{ route('association.dons.show', $don) }}" class="font-semibold text-forest-700 hover:underline">{{ $don->reference() }}</a> <span class="text-slate-500">· {{ $don->vetement->nom }} · {{ $don->particulier->name }}</span></div>
                <x-badge :label="$don->statut->label()" :color="$don->statut->color()" />
                @if($evenement->estCollecte() && $don->statut === StatutDon::ACCEPTEE)
                <form method="POST" action="{{ route('association.dons.depot', $don) }}">@csrf<input type="hidden" name="evenement" value="{{ $evenement->id }}"><button class="inline-flex items-center gap-1 rounded-lg bg-forest-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-forest-700"><i data-lucide="package-check" class="h-4 w-4"></i>Reçu</button></form>
                @elseif($evenement->estCollecte() && $don->statut === StatutDon::EN_ATTENTE)
                <a href="{{ route('association.dons.show', $don) }}" class="text-xs font-semibold text-slate-500 hover:text-forest-700">À accepter d'abord</a>
                @endif
            </div>
            @empty
            <p class="px-6 py-8 text-center text-sm text-slate-500">{{ $evenement->estCollecte() ? 'Aucun don rattaché pour le moment : les particuliers choisissent la collecte en proposant leur don.' : 'Aucun don remis lors de cette distribution. Choisissez-la au moment de « Remettre » un don.' }}</p>
            @endforelse
        </section>
    </div>

    <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="font-display font-bold">Actions</h2>
        <div class="mt-4 flex flex-wrap gap-2">
            <x-button :href="route('association.evenements.edit', $evenement)" label="Modifier" icon="pencil" />
            @unless($evenement->annule)
            <form method="POST" action="{{ route('association.evenements.annuler', $evenement) }}" data-confirmer="Annuler cet événement ?{{ $evenement->estCollecte() ? ' Les particuliers qui devaient y déposer leur don seront prévenus.' : '' }}" data-confirmer-bouton="Oui, annuler">@csrf<button class="inline-flex items-center justify-center gap-2 rounded-xl border border-amber-300 bg-white px-4 py-2.5 text-sm font-semibold text-amber-700 hover:bg-amber-50"><i data-lucide="calendar-x" class="h-4 w-4"></i>Annuler l'événement</button></form>
            @endunless
            <form method="POST" action="{{ route('association.evenements.destroy', $evenement) }}" data-confirmer="Supprimer définitivement cet événement ?">@csrf @method('DELETE')<button class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700"><i data-lucide="trash-2" class="h-4 w-4"></i>Supprimer</button></form>
        </div>
        <p class="mt-4 text-xs leading-5 text-slate-400">Un événement auquel des dons sont rattachés ne peut pas être supprimé : annulez-le.</p>
        @if(! $evenement->annule && ! $evenement->estPasse())
        <a href="{{ route('evenements.show', $evenement) }}" class="mt-6 inline-flex items-center gap-1 text-sm font-semibold text-forest-700 hover:underline"><i data-lucide="external-link" class="h-4 w-4"></i>Voir la page publique</a>
        @endif
        <a href="{{ route('association.evenements.index') }}" class="mt-4 flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Retour aux événements</a>
    </aside>
</div>
@endsection
