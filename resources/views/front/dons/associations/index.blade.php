@extends('layouts.front')
@section('title', 'Associations')
@section('meta', 'Découvrez les associations partenaires de TextileCycle.')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4"><div class="max-w-2xl"><p class="text-sm font-bold text-forest-600">Solidarité locale</p><h1 class="mt-2 font-display text-4xl font-bold">Associations partenaires</h1><p class="mt-3 text-slate-500">Donnez utile : consultez les besoins actuels et choisissez la cause qui vous ressemble.</p></div><a href="{{ route('besoins.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-forest-700">Tous les besoins <i data-lucide="arrow-right" class="h-4 w-4"></i></a></div>
    <form method="GET" action="{{ route('associations.index') }}" class="mb-8 flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3">
        <i data-lucide="map-pin" class="ml-2 h-5 w-5 text-forest-600"></i>
        <input name="q" value="{{ request('q') }}" class="min-w-0 flex-1 bg-transparent py-2 text-sm outline-none" placeholder="Ville, code postal, nom ou domaine d'action">
        <button class="rounded-xl bg-forest-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-forest-700">Rechercher</button>
    </form>
    <div class="grid gap-6 lg:grid-cols-2">
        @forelse($associations as $association)
        @php $besoin = $association->besoinsActifs->first(); @endphp
        <article class="flex gap-5 rounded-3xl border border-slate-200 bg-white p-6">
            @if($association->logoUrl())<img src="{{ $association->logoUrl() }}" alt="" class="h-14 w-14 shrink-0 rounded-2xl object-cover">
            @else<span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-rose-50 text-rose-600"><i data-lucide="heart-handshake" class="h-7 w-7"></i></span>@endif
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center justify-between gap-2"><h2 class="font-display text-lg font-bold"><a href="{{ route('associations.show', $association) }}" class="hover:text-forest-700">{{ $association->nom }}</a></h2>@if($besoin)<x-badge :label="$besoin->priorite->label()" :color="$besoin->priorite->color()" />@endif</div>
                <p class="mt-1 text-sm text-slate-500">{{ collect([$association->domaine_action, trim($association->code_postal.' '.$association->ville)])->filter()->join(' · ') }}</p>
                <div class="mt-2"><x-etoiles :note="$association->noteMoyenne()" :avis="$association->nombreAvis()" /></div>
                @if($besoin)<p class="mt-4 text-sm"><span class="font-semibold">Besoin actuel :</span> {{ $besoin->titre }}@if($association->besoinsActifs->count() > 1) <span class="text-slate-400">(+{{ $association->besoinsActifs->count() - 1 }})</span>@endif</p>@endif
                <a href="{{ route('associations.show', $association) }}" class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-forest-700">Voir les besoins <i data-lucide="arrow-right" class="h-4 w-4"></i></a>
            </div>
        </article>
        @empty
        <p class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-10 text-center text-slate-500 lg:col-span-2">Aucune association trouvée.</p>
        @endforelse
    </div>
    <div class="mt-8">{{ $associations->links() }}</div>
</section>
@endsection
