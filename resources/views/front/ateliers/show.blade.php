@extends('layouts.front')
@use('App\Enums\TypeService')
@section('title', $atelier->nom)
@section('meta', $atelier->description ?: 'Atelier partenaire TextileCycle à ' . $atelier->ville . '.')
@section('content')
@php $peutDemander = ! auth()->check() || auth()->user()->hasRole('particulier'); @endphp
<section class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
    <a href="{{ route('ateliers.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-forest-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Tous les ateliers</a>
    <div class="grid gap-6 md:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 md:col-span-2">
            @if($atelier->logoUrl())<img src="{{ $atelier->logoUrl() }}" alt="" class="h-16 w-16 rounded-2xl object-cover">
            @else<span class="grid h-16 w-16 place-items-center rounded-2xl bg-forest-100 text-forest-700"><i data-lucide="store" class="h-8 w-8"></i></span>@endif
            <h1 class="mt-6 font-display text-4xl font-bold">{{ $atelier->nom }}</h1>
            @if($atelier->specialite)<p class="mt-1 font-medium text-forest-700">{{ $atelier->specialite }}</p>@endif
            <div class="mt-3"><x-etoiles :note="$atelier->noteMoyenne()" :avis="$atelier->nombreAvis()" /></div>
            @if($atelier->description)<p class="mt-6 leading-7 text-slate-600">{{ $atelier->description }}</p>@endif
            @if($peutDemander)
            <div class="mt-8 flex flex-wrap gap-3">
                @if($atelier->propose(TypeService::REPARATION))<x-button :href="route('reparations.create', ['atelier' => $atelier->id])" label="Demander une réparation" icon="wrench" />@endif
                @if($atelier->propose(TypeService::TRANSFORMATION))<x-button :href="route('transformations.create', ['atelier' => $atelier->id])" label="Proposer une transformation" icon="scissors" variant="secondary" />@endif
            </div>
            @endif
        </div>
        <aside class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-display font-bold">Coordonnées</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    @if($atelier->adresseComplete())<div class="flex items-start gap-3"><i data-lucide="map-pin" class="mt-0.5 h-4 w-4 shrink-0 text-forest-600"></i><span>{{ $atelier->adresseComplete() }}</span></div>@endif
                    @if($atelier->telephone)<div class="flex items-start gap-3"><i data-lucide="phone" class="mt-0.5 h-4 w-4 shrink-0 text-forest-600"></i><span>{{ $atelier->telephone }}</span></div>@endif
                    @if($atelier->email)<div class="flex items-start gap-3"><i data-lucide="mail" class="mt-0.5 h-4 w-4 shrink-0 text-forest-600"></i><span class="break-all">{{ $atelier->email }}</span></div>@endif
                </dl>
            </div>
            @if($atelier->horaires)
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-display font-bold">Horaires</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $atelier->horaires }}</p>
            </div>
            @endif
        </aside>
    </div>

    <h2 class="mb-5 mt-12 font-display text-2xl font-bold">Services et tarifs</h2>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        @forelse($atelier->services as $service)
        <div class="flex flex-wrap items-center gap-4 border-b border-slate-100 p-5 last:border-0">
            <div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><p class="font-semibold">{{ $service->nom }}</p><x-badge :label="$service->type->label()" :color="$service->type->color()" /></div>@if($service->description)<p class="mt-1 text-sm text-slate-500">{{ $service->description }}</p>@endif</div>
            <div class="text-right text-sm"><p class="font-display text-lg font-bold">{{ $service->prixFormate() }}</p><p class="text-slate-500">{{ collect([$service->dureeFormatee(), $service->delai_jours !== null ? 'délai '.$service->delai_jours.' j' : null])->filter()->join(' · ') }}</p></div>
        </div>
        @empty
        <p class="p-8 text-center text-sm text-slate-500">Aucun service publié pour le moment.</p>
        @endforelse
    </div>

    <h2 class="mb-5 mt-12 font-display text-2xl font-bold">Avis clients</h2>
    @include('partials.front.avis', ['structure' => $atelier])
</section>
@endsection
