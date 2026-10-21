@extends('layouts.front')
@section('title', 'Mes transformations')
@section('meta', 'Transformez vos vêtements avec nos ateliers partenaires.')
@section('content')
@php
    // Avancement affiché sur chaque carte.
    $avancement = ['en_attente' => 10, 'acceptee' => 25, 'depot_remis' => 45, 'en_traitement' => 70, 'pret' => 90, 'recupere' => 100, 'refusee' => 0, 'annulee' => 0];
@endphp
<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    @include('front.transformations._onglets')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm font-bold text-violet-600">Créer plutôt que jeter</p><h1 class="mt-2 font-display text-4xl font-bold">Mes transformations</h1><p class="mt-2 text-slate-500">Suivez vos projets d'upcycling créatif.</p></div><div class="flex flex-wrap gap-3"><x-button :href="route('transformations.idees')" label="Idées avec l’IA" icon="wand-sparkles" variant="secondary" /><x-button :href="route('transformations.create')" label="Nouveau projet" icon="scissors" /></div></div>
    <div class="mb-6 flex flex-wrap gap-2 text-sm font-semibold">
        <a href="{{ route('transformations.index') }}" class="rounded-full px-4 py-2 {{ request('statut') ? 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-forest-700' : 'bg-forest-600 text-white' }}">Tous</a>
        @foreach(\App\Enums\StatutDemande::cases() as $statut)
        <a href="{{ route('transformations.index', ['statut' => $statut->value]) }}" class="rounded-full px-4 py-2 {{ request('statut') === $statut->value ? 'bg-forest-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-forest-700' }}">{{ $statut->label() }}</a>
        @endforeach
    </div>
    <div class="grid gap-6 md:grid-cols-2">
        @forelse($demandes as $demande)
        <a href="{{ route('transformations.show', $demande) }}" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
            <div class="flex items-start justify-between">
                @if($demande->photoApresUrl() ?? $demande->vetement->photoUrl())<img src="{{ $demande->photoApresUrl() ?? $demande->vetement->photoUrl() }}" alt="" class="h-12 w-12 rounded-2xl object-cover">
                @else<span class="grid h-12 w-12 place-items-center rounded-2xl bg-violet-50 text-violet-700"><i data-lucide="scissors" class="h-6 w-6"></i></span>@endif
                <x-badge :label="$demande->statut->label()" :color="$demande->statut->color()" />
            </div>
            <h2 class="mt-5 font-display text-xl font-bold">{{ $demande->vetement->nom }}</h2>
            <p class="mt-1 line-clamp-1 text-sm text-slate-500">{{ $demande->idee }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $demande->atelier->nom }} · {{ $demande->reference() }}</p>
            @php $pourcentage = $avancement[$demande->statut->value]; @endphp
            @if($pourcentage)
            <div class="mt-6"><div class="mb-2 flex justify-between text-xs font-semibold"><span>Avancement</span><span>{{ $pourcentage }} %</span></div><div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-violet-500" style="width: {{ $pourcentage }}%"></div></div></div>
            @endif
        </a>
        @empty
        <div class="rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center md:col-span-2">
            <span class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-violet-50 text-violet-700"><i data-lucide="scissors" class="h-7 w-7"></i></span>
            <h2 class="font-display text-xl font-bold">Aucun projet {{ request('statut') ? 'avec ce statut' : 'pour le moment' }}</h2>
            <p class="mt-2 text-slate-500">Une robe devenue jupe, une chemise devenue top : proposez votre idée à un atelier d'upcycling.</p>
        </div>
        @endforelse
    </div>
    <div class="mt-8">{{ $demandes->links() }}</div>
</section>
@endsection
