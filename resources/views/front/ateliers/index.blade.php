@extends('layouts.front')
@section('title', 'Ateliers partenaires')
@section('meta', 'Trouvez un atelier textile proche de chez vous.')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="mb-8"><p class="text-sm font-bold text-forest-600">Artisans engagés</p><h1 class="mt-2 font-display text-4xl font-bold">Trouver un atelier</h1><p class="mt-2 text-slate-500">Des savoir-faire locaux pour prendre soin de vos vêtements.</p></div>
    <form method="GET" action="{{ route('ateliers.index') }}" class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3">
        <i data-lucide="map-pin" class="ml-2 h-5 w-5 text-forest-600"></i>
        <input name="q" value="{{ request('q') }}" class="min-w-0 flex-1 bg-transparent py-2 text-sm outline-none" placeholder="Ville, code postal, nom ou spécialité">
        @if(request('service'))<input type="hidden" name="service" value="{{ request('service') }}">@endif
        <button class="rounded-xl bg-forest-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-forest-700">Rechercher</button>
    </form>
    <div class="mb-8 flex flex-wrap gap-2 text-sm font-semibold">
        <a href="{{ route('ateliers.index', request()->except('service', 'page')) }}" class="rounded-full px-4 py-2 {{ request('service') ? 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-forest-700' : 'bg-forest-600 text-white' }}">Tous les ateliers</a>
        @foreach(\App\Enums\TypeService::cases() as $type)
        <a href="{{ route('ateliers.index', ['service' => $type->value] + request()->except('service', 'page')) }}" class="rounded-full px-4 py-2 {{ request('service') === $type->value ? 'bg-forest-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-forest-700' }}">{{ $type->label() }}</a>
        @endforeach
    </div>
    @if($ateliers->isEmpty())
        <div class="rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center">
            <span class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-forest-50 text-forest-700"><i data-lucide="store" class="h-7 w-7"></i></span>
            <h2 class="font-display text-xl font-bold">Aucun atelier trouvé</h2>
            <p class="mt-2 text-slate-500">Essayez une autre ville ou un autre service.</p>
        </div>
    @else
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @foreach($ateliers as $atelier)
        <article class="flex flex-col rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
            <div class="flex items-start justify-between gap-3">
                @if($atelier->logoUrl())<img src="{{ $atelier->logoUrl() }}" alt="" class="h-14 w-14 rounded-2xl object-cover">
                @else<span class="grid h-14 w-14 place-items-center rounded-2xl bg-forest-100 text-forest-700"><i data-lucide="store" class="h-7 w-7"></i></span>@endif
                <x-etoiles :note="$atelier->noteMoyenne()" :avis="$atelier->nombreAvis()" taille="h-3.5 w-3.5" />
            </div>
            <h2 class="mt-6 font-display text-xl font-bold"><a href="{{ route('ateliers.show', $atelier) }}" class="hover:text-forest-700">{{ $atelier->nom }}</a></h2>
            @if($atelier->specialite)<p class="mt-1 text-sm font-medium text-forest-700">{{ $atelier->specialite }}</p>@endif
            <p class="mt-2 flex items-center gap-2 text-sm text-slate-500"><i data-lucide="map-pin" class="h-4 w-4"></i>{{ trim($atelier->code_postal.' '.$atelier->ville) }}</p>
            <div class="mt-4 flex flex-wrap gap-2">@foreach($atelier->typesProposes() as $type)<span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $type->label() }}</span>@endforeach</div>
            <a href="{{ route('ateliers.show', $atelier) }}" class="mt-auto inline-flex items-center gap-2 pt-6 text-sm font-bold text-forest-700">Voir l'atelier et ses tarifs <i data-lucide="arrow-right" class="h-4 w-4"></i></a>
        </article>
        @endforeach
    </div>
    <div class="mt-8">{{ $ateliers->links() }}</div>
    @endif
</section>
@endsection
