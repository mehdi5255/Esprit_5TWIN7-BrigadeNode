@extends('layouts.back')
@section('title', 'Mes inspirations')
@section('breadcrumb', 'Atelier / Inspirations')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <p class="max-w-2xl text-sm text-slate-500">Montrez vos réalisations d'upcycling. Les particuliers les voient dans la galerie et en suggestion quand ils choisissent un vêtement de la même catégorie.</p>
    @if($peutPublier)<x-button :href="route('atelier.inspirations.create')" label="Nouvelle inspiration" icon="plus" />@endif
</div>
@unless($peutPublier)
<div class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
    <i data-lucide="info" class="mt-0.5 h-5 w-5 shrink-0"></i>
    <p>Les inspirations sont réservées aux ateliers qui proposent la transformation. <a href="{{ route('atelier.services.create') }}" class="font-semibold underline">Ajoutez un service « Transformation » actif</a> pour pouvoir en publier.</p>
</div>
@endunless
<div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
    @forelse($inspirations as $inspiration)
    <article class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm {{ $inspiration->actif ? '' : 'opacity-60' }}">
        @if($inspiration->photoUrl())<img src="{{ $inspiration->photoUrl() }}" alt="" class="h-40 w-full object-cover">
        @else<div class="grid h-40 place-items-center bg-violet-50 text-violet-600"><i data-lucide="sparkles" class="h-10 w-10"></i></div>@endif
        <div class="flex flex-1 flex-col p-5">
            <div class="flex flex-wrap items-center gap-2"><x-badge :label="$inspiration->categorie->libelle" color="blue" /><x-badge :label="$inspiration->actif ? 'Visible' : 'Masquée'" :color="$inspiration->actif ? 'green' : 'gray'" /></div>
            <h2 class="mt-3 font-display text-lg font-bold"><a href="{{ route('atelier.inspirations.show', $inspiration) }}" class="hover:text-forest-700">{{ $inspiration->titre }}</a></h2>
            <p class="mt-1 text-sm text-slate-500">{{ $inspiration->prixFormate() }}{{ $inspiration->delai_jours ? ' · '.$inspiration->delai_jours.' j' : '' }} · demandée {{ $inspiration->demandes_count }} fois</p>
            <div class="mt-auto flex items-center gap-2 pt-5 text-sm">
                <a href="{{ route('atelier.inspirations.show', $inspiration) }}" class="rounded-lg px-3 py-2 font-semibold text-slate-600 hover:bg-slate-100">Détail</a>
                <a href="{{ route('atelier.inspirations.edit', $inspiration) }}" class="rounded-lg px-3 py-2 font-semibold text-slate-600 hover:bg-slate-100">Modifier</a>
                <form method="POST" action="{{ route('atelier.inspirations.basculer', $inspiration) }}">@csrf<button class="rounded-lg px-3 py-2 font-semibold text-slate-600 hover:bg-slate-100">{{ $inspiration->actif ? 'Masquer' : 'Publier' }}</button></form>
                <form method="POST" action="{{ route('atelier.inspirations.destroy', $inspiration) }}" class="ml-auto" data-confirmer="Supprimer cette inspiration ? Les demandes déjà faites sont conservées.">@csrf @method('DELETE')<button class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Supprimer"><i data-lucide="trash-2" class="h-4 w-4"></i></button></form>
            </div>
        </div>
    </article>
    @empty
    <p class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-10 text-center text-slate-500 sm:col-span-2 xl:col-span-3">Aucune inspiration publiée pour le moment.</p>
    @endforelse
</div>
@endsection
