@extends('layouts.front')
@section('title', 'Mes dons')
@section('meta', 'Donnez vos vêtements à des associations locales.')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="overflow-hidden rounded-3xl bg-forest-900 text-white"><div class="grid items-center md:grid-cols-2"><div class="p-8 lg:p-12"><span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-emerald-200">Un geste solidaire</span><h1 class="mt-5 font-display text-4xl font-bold">Transmettez ce que vous ne portez plus.</h1><p class="mt-4 leading-7 text-emerald-50/70">Vos vêtements peuvent répondre à un besoin concret près de chez vous.</p><div class="mt-7 flex flex-wrap gap-3"><x-button :href="route('dons.create')" label="Faire un don" icon="heart-handshake" /><a href="{{ route('besoins.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/20 px-4 py-2.5 text-sm font-semibold hover:bg-white/10">Voir les besoins <i data-lucide="arrow-right" class="h-4 w-4"></i></a></div></div><img src="https://images.unsplash.com/photo-1600376492476-9ca38b2ec5b7?fit=crop&w=1000&q=80" alt="Vêtements préparés pour une seconde vie" class="h-full min-h-72 w-full object-cover"></div></div>
    <h2 class="mb-5 mt-12 font-display text-2xl font-bold">Mes dons</h2>
    <div class="grid gap-5 md:grid-cols-3">
        @forelse($demandes as $demande)
        <a href="{{ route('dons.show', $demande) }}" class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:shadow-lg">
            <div class="flex justify-between">
                @if($demande->vetement->photoUrl())<img src="{{ $demande->vetement->photoUrl() }}" alt="" class="h-10 w-10 rounded-xl object-cover">
                @else<span class="grid h-10 w-10 place-items-center rounded-xl bg-rose-50 text-rose-600"><i data-lucide="gift" class="h-5 w-5"></i></span>@endif
                <x-badge :label="$demande->statut->label()" :color="$demande->statut->color()" />
            </div>
            <h3 class="mt-5 font-display font-bold">{{ $demande->vetement->nom }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $demande->association->nom }}</p>
            @if($demande->besoin)<p class="mt-1 text-xs text-slate-400">Pour : {{ $demande->besoin->titre }}</p>@endif
        </a>
        @empty
        <p class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-10 text-center text-slate-500 md:col-span-3">Vous n'avez pas encore fait de don.</p>
        @endforelse
    </div>
    <div class="mt-8">{{ $demandes->links() }}</div>
</section>
@endsection
