@extends('layouts.front')
@section('title', 'Inspirations d’upcycling')
@section('meta', 'Des idées de transformation proposées par les ateliers partenaires.')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    @include('front.transformations._onglets')
    <div class="mb-8">
        <p class="text-sm font-bold text-violet-600">Galerie d'upcycling</p>
        <h1 class="mt-2 font-display text-4xl font-bold">Inspirations</h1>
        <p class="mt-2 max-w-2xl text-slate-500">Des idées réalisées par nos ateliers partenaires. Choisissez un modèle : votre demande de transformation sera préremplie.</p>
    </div>
    <form method="GET" action="{{ route('inspirations.index') }}" class="mb-8 flex flex-wrap gap-3">
        <select name="categorie" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold" aria-label="Catégorie">
            <option value="">Toutes les catégories</option>
            @foreach($categories as $categorie)<option value="{{ $categorie->id }}" @selected(request()->integer('categorie') === $categorie->id)>{{ $categorie->libelle }}</option>@endforeach
        </select>
        <select name="atelier" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold" aria-label="Atelier">
            <option value="">Tous les ateliers</option>
            @foreach($ateliers as $atelier)<option value="{{ $atelier->id }}" @selected(request()->integer('atelier') === $atelier->id)>{{ $atelier->nom }}</option>@endforeach
        </select>
        @if(request('categorie') || request('atelier'))<a href="{{ route('inspirations.index') }}" class="self-center text-sm font-semibold text-slate-500 hover:text-forest-700">Réinitialiser</a>@endif
    </form>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($inspirations as $inspiration)
        <article class="group flex flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <a href="{{ route('inspirations.show', $inspiration) }}">
                @if($inspiration->photoUrl())<img src="{{ $inspiration->photoUrl() }}" alt="" class="h-52 w-full object-cover">
                @else<div class="grid h-52 place-items-center bg-violet-50 text-violet-600"><i data-lucide="sparkles" class="h-12 w-12"></i></div>@endif
            </a>
            <div class="flex flex-1 flex-col p-6">
                <x-badge :label="$inspiration->categorie->libelle" color="blue" />
                <h2 class="mt-3 font-display text-xl font-bold"><a href="{{ route('inspirations.show', $inspiration) }}" class="hover:text-forest-700">{{ $inspiration->titre }}</a></h2>
                <p class="mt-2 line-clamp-2 text-sm text-slate-500">{{ $inspiration->description }}</p>
                <div class="mt-auto flex items-end justify-between gap-3 pt-5 text-sm">
                    <div><p class="font-semibold">{{ $inspiration->atelier->nom }}</p><p class="text-slate-500">{{ $inspiration->prixFormate() }}{{ $inspiration->demandes_count ? ' · '.$inspiration->demandes_count.' demande(s)' : '' }}</p></div>
                    <a href="{{ route('inspirations.show', $inspiration) }}" class="inline-flex items-center gap-1 font-semibold text-forest-700">Voir <i data-lucide="arrow-right" class="h-4 w-4"></i></a>
                </div>
            </div>
        </article>
        @empty
        <div class="rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center sm:col-span-2 lg:col-span-3">
            <span class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-violet-50 text-violet-600"><i data-lucide="sparkles" class="h-7 w-7"></i></span>
            <h2 class="font-display text-xl font-bold">Aucune inspiration {{ request('categorie') || request('atelier') ? 'pour ces filtres' : 'pour le moment' }}</h2>
            <p class="mt-2 text-slate-500">Vous pouvez toujours proposer votre propre idée à un atelier.</p>
        </div>
        @endforelse
    </div>
    <div class="mt-8">{{ $inspirations->links() }}</div>
</section>
@endsection
