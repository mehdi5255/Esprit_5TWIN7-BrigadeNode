@extends('layouts.back')
@section('title', 'Mes besoins')
@section('breadcrumb', 'Association / Besoins')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-slate-500">Déclarez ce dont vous avez besoin : les particuliers les consultent avant de donner.</p><x-button :href="route('association.besoins.create')" label="Déclarer un besoin" icon="plus" /></div>
<div class="grid gap-5 lg:grid-cols-2">
    @forelse($besoins as $besoin)
    <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm {{ $besoin->actif ? '' : 'opacity-60' }}">
        <div class="flex items-start justify-between gap-4">
            <div><p class="text-xs font-bold uppercase text-forest-600">{{ $besoin->categorie?->libelle ?? 'Tous vêtements' }}</p><h2 class="mt-2 font-display text-lg font-bold">{{ $besoin->titre }}</h2></div>
            <div class="flex flex-col items-end gap-2"><x-badge :label="$besoin->priorite->label()" :color="$besoin->priorite->color()" />@unless($besoin->actif)<x-badge label="Désactivé" color="gray" />@endunless</div>
        </div>
        @if($besoin->description)<p class="mt-3 text-sm leading-6 text-slate-500">{{ $besoin->description }}</p>@endif
        <div class="mt-5 border-t border-slate-100 pt-4">
            <div class="mb-2 flex justify-between text-sm"><span class="text-slate-500">Objectif collecté</span><strong>{{ $besoin->collectes() }} / {{ $besoin->quantite }}</strong></div>
            <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-forest-500" style="width: {{ $besoin->progression() }}%"></div></div>
        </div>
        <div class="mt-auto flex items-center gap-2 pt-5 text-sm">
            <a href="{{ route('association.besoins.edit', $besoin) }}" class="rounded-lg px-3 py-2 font-semibold text-slate-600 hover:bg-slate-100">Modifier</a>
            <form method="POST" action="{{ route('association.besoins.basculer', $besoin) }}">@csrf<button class="rounded-lg px-3 py-2 font-semibold text-slate-600 hover:bg-slate-100">{{ $besoin->actif ? 'Désactiver (satisfait)' : 'Réactiver' }}</button></form>
            <form method="POST" action="{{ route('association.besoins.destroy', $besoin) }}" class="ml-auto" data-confirmer="Supprimer ce besoin ?">@csrf @method('DELETE')<button class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Supprimer"><i data-lucide="trash-2" class="h-4 w-4"></i></button></form>
        </div>
    </article>
    @empty
    <p class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-10 text-center text-slate-500 lg:col-span-2">Aucun besoin déclaré.</p>
    @endforelse
</div>
@endsection
