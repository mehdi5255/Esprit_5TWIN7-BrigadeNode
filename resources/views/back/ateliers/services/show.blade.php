@extends('layouts.back')
@section('title', $service->nom)
@section('breadcrumb', 'Atelier / Services / ' . $service->nom)
@section('content')
<div class="grid gap-6 xl:grid-cols-3">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
        <div class="flex flex-wrap items-center gap-3">
            <x-badge :label="$service->type->label()" :color="$service->type->color()" />
            <x-badge :label="$service->actif ? 'Actif' : 'Désactivé'" :color="$service->actif ? 'green' : 'gray'" />
        </div>
        <h2 class="mt-4 font-display text-xl font-bold">{{ $service->nom }}</h2>
        <p class="mt-2 text-sm text-slate-600">{{ $service->description ?: 'Aucune description.' }}</p>
        <dl class="mt-6 grid gap-3 text-sm sm:grid-cols-3">
            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Prix</dt><dd class="mt-1 font-semibold">{{ $service->prixFormate() }}</dd></div>
            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Durée</dt><dd class="mt-1 font-semibold">{{ $service->dureeFormatee() ?? '—' }}</dd></div>
            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Délai</dt><dd class="mt-1 font-semibold">{{ $service->delai_jours !== null ? $service->delai_jours.' jour(s)' : '—' }}</dd></div>
            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Créé le</dt><dd class="mt-1 font-semibold">{{ $service->created_at->format('d/m/Y') }}</dd></div>
            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Dernière mise à jour</dt><dd class="mt-1 font-semibold">{{ $service->updated_at->format('d/m/Y') }}</dd></div>
        </dl>
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-4">
            <div><p class="text-xs text-slate-500">Atelier</p><p class="font-semibold">{{ $service->atelier->nom }}</p><p class="text-sm text-slate-500">{{ $service->atelier->adresseComplete() }}</p></div>
            @if($service->atelier->estActif())<a href="{{ route('ateliers.show', $service->atelier) }}" class="text-sm font-semibold text-forest-700">Voir la page publique</a>@endif
        </div>
    </section>
    <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="font-display font-bold">Actions</h2>
        <div class="mt-4 flex flex-wrap gap-2">
            <x-button :href="route('atelier.services.edit', $service)" label="Modifier" icon="pencil" />
            <form method="POST" action="{{ route('atelier.services.basculer', $service) }}">@csrf<button class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ $service->actif ? 'Désactiver' : 'Activer' }}</button></form>
            <form method="POST" action="{{ route('atelier.services.destroy', $service) }}" data-confirmer="Supprimer ce service ?">@csrf @method('DELETE')<button class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700"><i data-lucide="trash-2" class="h-4 w-4"></i>Supprimer</button></form>
        </div>
        <a href="{{ route('atelier.services.index') }}" class="mt-6 inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Retour aux services</a>
    </aside>
</div>
@endsection
