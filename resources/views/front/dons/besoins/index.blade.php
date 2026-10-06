@extends('layouts.front')
@section('title', 'Besoins des associations')
@section('meta', 'Consultez les besoins actuels des associations partenaires et donnez utile.')
@section('content')
@php $select = 'rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 outline-none focus:border-forest-500'; @endphp
<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="mb-8 max-w-2xl"><p class="text-sm font-bold text-forest-600">Donner utile</p><h1 class="mt-2 font-display text-4xl font-bold">Besoins des associations</h1><p class="mt-3 text-slate-500">Ce dont les associations ont besoin en ce moment, les plus urgents d'abord.</p></div>
    <form method="GET" action="{{ route('besoins.index') }}" class="mb-8 flex flex-wrap items-center gap-3">
        <select name="categorie" class="{{ $select }}" aria-label="Catégorie" onchange="this.form.submit()"><option value="">Toutes catégories</option>@foreach($categories as $categorie)<option value="{{ $categorie->id }}" @selected(request('categorie') == $categorie->id)>{{ $categorie->libelle }}</option>@endforeach</select>
        <select name="priorite" class="{{ $select }}" aria-label="Priorité" onchange="this.form.submit()"><option value="">Toutes priorités</option>@foreach(\App\Enums\PrioriteBesoin::cases() as $priorite)<option value="{{ $priorite->value }}" @selected(request('priorite') === $priorite->value)>{{ $priorite->label() }}</option>@endforeach</select>
    </form>
    <div class="grid gap-5 lg:grid-cols-2">
        @forelse($besoins as $besoin)
        <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4"><div><a href="{{ route('associations.show', $besoin->association) }}" class="text-xs font-bold uppercase text-forest-600 hover:underline">{{ $besoin->association->nom }}</a><h2 class="mt-2 font-display text-lg font-bold">{{ $besoin->titre }}</h2></div><x-badge :label="$besoin->priorite->label()" :color="$besoin->priorite->color()" /></div>
            @if($besoin->description)<p class="mt-3 text-sm leading-6 text-slate-500">{{ $besoin->description }}</p>@endif
            <div class="mt-5 border-t border-slate-100 pt-4">
                <div class="mb-2 flex justify-between text-sm"><span class="text-slate-500">{{ $besoin->categorie?->libelle ?? 'Tous vêtements' }} · objectif collecté</span><strong>{{ $besoin->collectes() }} / {{ $besoin->quantite }}</strong></div>
                <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-forest-500" style="width: {{ $besoin->progression() }}%"></div></div>
            </div>
            <div class="mt-5">
                @auth @if(auth()->user()->hasRole('particulier'))
                    <x-button :href="route('dons.create', ['besoin' => $besoin->id])" label="Donner pour ce besoin" icon="heart-handshake" />
                @endif @else
                    <x-button :href="route('dons.create', ['besoin' => $besoin->id])" label="Donner pour ce besoin" icon="heart-handshake" />
                @endauth
            </div>
        </article>
        @empty
        <p class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-10 text-center text-slate-500 lg:col-span-2">Aucun besoin ne correspond à ces filtres.</p>
        @endforelse
    </div>
    <div class="mt-8">{{ $besoins->links() }}</div>
</section>
@endsection
