@extends('layouts.back')
@section('title', 'Mes services')
@section('breadcrumb', 'Atelier / Services')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-slate-500">Vos prestations, leurs tarifs et délais. Un service actif « Réparation » ou « Transformation » vous rend visible pour ce type de demande.</p><x-button :href="route('atelier.services.create')" label="Nouveau service" icon="plus" /></div>
<div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
    @forelse($services as $service)
    <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm {{ $service->actif ? '' : 'opacity-60' }}">
        <div class="flex items-start justify-between gap-3">
            <x-badge :label="$service->type->label()" :color="$service->type->color()" />
            <x-badge :label="$service->actif ? 'Actif' : 'Désactivé'" :color="$service->actif ? 'green' : 'gray'" />
        </div>
        <h2 class="mt-4 font-display text-lg font-bold"><a href="{{ route('atelier.services.show', $service) }}" class="hover:text-forest-700">{{ $service->nom }}</a></h2>
        @if($service->description)<p class="mt-2 text-sm text-slate-500">{{ $service->description }}</p>@endif
        <dl class="mt-4 grid grid-cols-3 gap-2 text-sm">
            <div><dt class="text-xs text-slate-500">Prix</dt><dd class="font-semibold">{{ $service->prix !== null ? number_format($service->prix, 2, ',', ' ').' €' : 'Devis' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Durée</dt><dd class="font-semibold">{{ $service->dureeFormatee() ?? '—' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Délai</dt><dd class="font-semibold">{{ $service->delai_jours !== null ? $service->delai_jours.' j' : '—' }}</dd></div>
        </dl>
        <div class="mt-auto flex items-center gap-2 pt-5 text-sm">
            <a href="{{ route('atelier.services.show', $service) }}" class="rounded-lg px-3 py-2 font-semibold text-slate-600 hover:bg-slate-100">Détail</a>
            <a href="{{ route('atelier.services.edit', $service) }}" class="rounded-lg px-3 py-2 font-semibold text-slate-600 hover:bg-slate-100">Modifier</a>
            <form method="POST" action="{{ route('atelier.services.basculer', $service) }}">@csrf<button class="rounded-lg px-3 py-2 font-semibold text-slate-600 hover:bg-slate-100">{{ $service->actif ? 'Désactiver' : 'Activer' }}</button></form>
            <form method="POST" action="{{ route('atelier.services.destroy', $service) }}" class="ml-auto" data-confirmer="Supprimer ce service ?">@csrf @method('DELETE')<button class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Supprimer"><i data-lucide="trash-2" class="h-4 w-4"></i></button></form>
        </div>
    </article>
    @empty
    <p class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-10 text-center text-slate-500 sm:col-span-2 xl:col-span-3">Aucun service. Créez votre premier service pour recevoir des demandes.</p>
    @endforelse
</div>
@endsection
