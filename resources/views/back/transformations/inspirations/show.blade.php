@extends('layouts.back')
@section('title', $inspiration->titre)
@section('breadcrumb', 'Atelier / Inspirations / ' . $inspiration->titre)
@section('content')
<div class="grid gap-6 xl:grid-cols-3">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
        @if($inspiration->photoUrl())<img src="{{ $inspiration->photoUrl() }}" alt="" class="h-64 w-full object-cover">@endif
        <div class="p-6">
            <div class="flex flex-wrap items-center gap-2"><x-badge :label="$inspiration->categorie->libelle" color="blue" /><x-badge :label="$inspiration->actif ? 'Visible dans la galerie' : 'Masquée'" :color="$inspiration->actif ? 'green' : 'gray'" /></div>
            <p class="mt-4 leading-7 text-slate-600">{{ $inspiration->description }}</p>
            <dl class="mt-6 grid gap-3 text-sm sm:grid-cols-3">
                <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Prix</dt><dd class="mt-1 font-semibold">{{ $inspiration->prixFormate() }}</dd></div>
                <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Délai habituel</dt><dd class="mt-1 font-semibold">{{ $inspiration->delai_jours ? $inspiration->delai_jours.' jour(s)' : '—' }}</dd></div>
                <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Demandes</dt><dd class="mt-1 font-semibold">{{ $inspiration->demandes->count() }}</dd></div>
            </dl>

            <h2 class="mt-8 font-display text-lg font-bold">Demandes inspirées de ce modèle</h2>
            <div class="mt-3 divide-y divide-slate-100 text-sm">
                @forelse($inspiration->demandes as $demande)
                <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <div><a href="{{ route('atelier.transformations.show', $demande) }}" class="font-semibold text-forest-700 hover:underline">{{ $demande->reference() }}</a> <span class="text-slate-500">· {{ $demande->vetement->nom }} · {{ $demande->particulier->name }}</span></div>
                    <x-badge :label="$demande->statut->label()" :color="$demande->statut->color()" />
                </div>
                @empty
                <p class="py-3 text-slate-500">Aucune demande pour le moment.</p>
                @endforelse
            </div>
        </div>
    </section>
    <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="font-display font-bold">Actions</h2>
        <div class="mt-4 flex flex-wrap gap-2">
            <x-button :href="route('atelier.inspirations.edit', $inspiration)" label="Modifier" icon="pencil" />
            <form method="POST" action="{{ route('atelier.inspirations.basculer', $inspiration) }}">@csrf<button class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ $inspiration->actif ? 'Masquer' : 'Publier' }}</button></form>
            <form method="POST" action="{{ route('atelier.inspirations.destroy', $inspiration) }}" data-confirmer="Supprimer cette inspiration ? Les demandes déjà faites sont conservées.">@csrf @method('DELETE')<button class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700"><i data-lucide="trash-2" class="h-4 w-4"></i>Supprimer</button></form>
        </div>
        @if($inspiration->estVisible())
        <a href="{{ route('inspirations.show', $inspiration) }}" class="mt-6 inline-flex items-center gap-1 text-sm font-semibold text-forest-700 hover:underline"><i data-lucide="external-link" class="h-4 w-4"></i>Voir dans la galerie publique</a>
        @endif
        <a href="{{ route('atelier.inspirations.index') }}" class="mt-4 flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Retour aux inspirations</a>
    </aside>
</div>
@endsection
