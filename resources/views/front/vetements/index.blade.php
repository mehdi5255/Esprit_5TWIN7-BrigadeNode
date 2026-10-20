@extends('layouts.front')

@section('title', 'Mes vêtements')
@section('meta', 'Gérez votre dressing circulaire TextileCycle.')

@section('content')
@php $select = 'rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 outline-none focus:border-forest-500'; @endphp
<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="text-sm font-bold text-forest-600">Mon dressing circulaire</p><h1 class="mt-2 font-display text-4xl font-bold text-slate-900">Mes vêtements</h1><p class="mt-2 text-slate-500">Gérez vos pièces et suivez leur seconde vie.</p></div><x-button :href="route('vetements.create')" label="Ajouter un vêtement" icon="plus" /></div>
    <div class="mb-7 flex flex-wrap items-center gap-x-8 gap-y-3 rounded-2xl bg-forest-900 px-6 py-5 text-white">
        <p class="flex items-center gap-2 text-sm font-semibold text-emerald-200"><i data-lucide="leaf" class="h-5 w-5"></i>Grâce à vous</p>
        @if($impact['vetements'])
        <p><span class="font-display text-2xl font-bold">{{ $impact['vetements'] }}</span> <span class="text-sm text-emerald-100/70">vêtement(s) sauvé(s)</span></p>
        <p><span class="font-display text-2xl font-bold">≈ {{ number_format($impact['co2'], 1, ',', ' ') }} kg</span> <span class="text-sm text-emerald-100/70">de CO₂ évités</span></p>
        <p><span class="font-display text-2xl font-bold">≈ {{ number_format($impact['eau'], 0, ',', ' ') }} L</span> <span class="text-sm text-emerald-100/70">d'eau économisés</span></p>
        @else
        <p class="text-sm text-emerald-100/80">Réparez, transformez ou donnez un vêtement : l'impact évité s'affichera ici.</p>
        @endif
    </div>
    <form method="GET" action="{{ route('vetements.index') }}" class="mb-7 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3">
        <label class="flex min-w-56 flex-1 items-center gap-2 px-2 text-sm"><i data-lucide="search" class="h-4 w-4 text-slate-400"></i><input name="q" value="{{ request('q') }}" placeholder="Rechercher un vêtement..." class="w-full bg-transparent py-2 outline-none placeholder:text-slate-400"></label>
        <select name="categorie" class="{{ $select }}" aria-label="Catégorie"><option value="">Toutes catégories</option>@foreach($categories as $categorie)<option value="{{ $categorie->id }}" @selected(request('categorie') == $categorie->id)>{{ $categorie->libelle }}</option>@endforeach</select>
        <select name="etat" class="{{ $select }}" aria-label="État"><option value="">Tous états</option>@foreach(\App\Enums\EtatVetement::cases() as $etat)<option value="{{ $etat->value }}" @selected(request('etat') === $etat->value)>{{ $etat->label() }}</option>@endforeach</select>
        <select name="statut" class="{{ $select }}" aria-label="Statut"><option value="">Tous statuts</option>@foreach(\App\Enums\StatutVetement::cases() as $statut)<option value="{{ $statut->value }}" @selected(request('statut') === $statut->value)>{{ $statut->label() }}</option>@endforeach</select>
        <button class="inline-flex items-center gap-2 rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white hover:bg-forest-700"><i data-lucide="sliders-horizontal" class="h-4 w-4"></i>Filtrer</button>
        @if(request()->hasAny(['q', 'categorie', 'etat', 'statut']))<a href="{{ route('vetements.index') }}" class="text-sm font-semibold text-slate-500 hover:text-forest-700">Réinitialiser</a>@endif
    </form>
    @if($vetements->isEmpty())
        <div class="rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center">
            <span class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-forest-50 text-forest-700"><i data-lucide="shirt" class="h-7 w-7"></i></span>
            <h2 class="font-display text-xl font-bold">{{ request()->hasAny(['q', 'categorie', 'etat', 'statut']) ? 'Aucun vêtement ne correspond à ces filtres' : 'Votre dressing est vide' }}</h2>
            <p class="mt-2 text-slate-500">Ajoutez une pièce pour la faire réparer, la transformer ou la donner.</p>
        </div>
    @else
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($vetements as $vetement)
        <a href="{{ route('vetements.show', $vetement) }}" class="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
            <div class="relative">
                @if($vetement->photoUrl())<img src="{{ $vetement->photoUrl() }}" alt="{{ $vetement->nom }}" class="h-64 w-full object-cover">
                @else<div class="grid h-64 w-full place-items-center bg-forest-50 text-forest-600"><i data-lucide="{{ $vetement->categorie->icone }}" class="h-12 w-12"></i></div>@endif
                <span class="absolute right-4 top-4"><x-badge :label="$vetement->statut->label()" :color="$vetement->statut->color()" /></span>
            </div>
            <div class="p-5"><p class="text-xs font-bold uppercase tracking-wider text-forest-600">{{ $vetement->categorie->libelle }}</p><h2 class="mt-1 font-display text-xl font-bold group-hover:text-forest-700">{{ $vetement->nom }}</h2><p class="mt-4 text-sm text-slate-500">État : <span class="font-semibold text-slate-700">{{ $vetement->etat->label() }}</span>@if($vetement->taille) · Taille <span class="font-semibold text-slate-700">{{ $vetement->taille }}</span>@endif</p></div>
        </a>
        @endforeach
    </div>
    <div class="mt-8">{{ $vetements->links() }}</div>
    @endif
</section>
@endsection
