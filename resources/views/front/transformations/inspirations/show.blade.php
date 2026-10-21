@extends('layouts.front')
@section('title', $inspiration->titre)
@section('meta', $inspiration->titre.' — inspiration d’upcycling par '.$inspiration->atelier->nom)
@section('content')
@php
    $particulier = auth()->user()?->hasRole('particulier');
    $projet = route('transformations.create', ['inspiration' => $inspiration->id]);
@endphp
<section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
    <a href="{{ route('inspirations.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-forest-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Toutes les inspirations</a>
    <div class="grid gap-8 lg:grid-cols-2">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            @if($inspiration->photoUrl())<img src="{{ $inspiration->photoUrl() }}" alt="{{ $inspiration->titre }}" class="h-full max-h-[28rem] w-full object-cover">
            @else<div class="grid h-80 place-items-center bg-violet-50 text-violet-600"><i data-lucide="sparkles" class="h-16 w-16"></i></div>@endif
        </div>
        <div>
            <x-badge :label="'Pour : '.$inspiration->categorie->libelle" color="blue" />
            <h1 class="mt-3 font-display text-4xl font-bold">{{ $inspiration->titre }}</h1>
            <p class="mt-4 leading-7 text-slate-600">{{ $inspiration->description }}</p>
            <dl class="mt-6 grid grid-cols-3 gap-3 text-sm">
                <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200"><dt class="text-xs text-slate-500">Prix</dt><dd class="mt-1 font-semibold">{{ $inspiration->prixFormate() }}</dd></div>
                <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200"><dt class="text-xs text-slate-500">Délai habituel</dt><dd class="mt-1 font-semibold">{{ $inspiration->delai_jours ? $inspiration->delai_jours.' jours' : '—' }}</dd></div>
                <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200"><dt class="text-xs text-slate-500">Déjà demandé</dt><dd class="mt-1 font-semibold">{{ $inspiration->demandes_count }} fois</dd></div>
            </dl>
            <a href="{{ route('ateliers.show', $inspiration->atelier) }}" class="mt-6 flex items-center gap-3 rounded-2xl bg-white p-4 ring-1 ring-slate-200 hover:ring-forest-300">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-forest-50 text-forest-700"><i data-lucide="store" class="h-5 w-5"></i></span>
                <span><span class="block font-semibold">{{ $inspiration->atelier->nom }}</span><span class="text-sm text-slate-500">{{ $inspiration->atelier->ville }}</span></span>
            </a>
            <div class="mt-8">
                @if($particulier)
                    <x-button :href="$projet" label="Je veux ce projet" icon="sparkles" />
                    <p class="mt-2 text-xs text-slate-500">Vous choisirez ensuite le vêtement à transformer (catégorie « {{ $inspiration->categorie->libelle }} »).</p>
                @elseif(! auth()->check())
                    <x-button :href="route('login')" label="Se connecter pour demander ce projet" icon="log-in" />
                @endif
            </div>
        </div>
    </div>

    @if($autres->isNotEmpty())
    <h2 class="mb-5 mt-14 font-display text-2xl font-bold">Autres inspirations de {{ $inspiration->atelier->nom }}</h2>
    <div class="grid gap-6 sm:grid-cols-3">
        @foreach($autres as $autre)
        <a href="{{ route('inspirations.show', $autre) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-forest-300">
            <x-badge :label="$autre->categorie->libelle" color="blue" />
            <p class="mt-3 font-display font-bold">{{ $autre->titre }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $autre->prixFormate() }}</p>
        </a>
        @endforeach
    </div>
    @endif
</section>
@endsection
