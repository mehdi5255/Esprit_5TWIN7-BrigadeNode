@extends('layouts.front')
@section('title', 'Mes réparations')
@section('meta', 'Suivez vos demandes de réparation.')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm font-bold text-forest-600">Suivi des services</p><h1 class="mt-2 font-display text-4xl font-bold">Mes réparations</h1><p class="mt-2 text-slate-500">Chaque étape de vos demandes, en un coup d'œil.</p></div><x-button :href="route('reparations.create')" label="Nouvelle demande" icon="plus" /></div>
    <div class="mb-6 flex flex-wrap gap-2 text-sm font-semibold">
        <a href="{{ route('reparations.index') }}" class="rounded-full px-4 py-2 {{ request('statut') ? 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-forest-700' : 'bg-forest-600 text-white' }}">Toutes</a>
        @foreach(\App\Enums\StatutDemande::cases() as $statut)
        <a href="{{ route('reparations.index', ['statut' => $statut->value]) }}" class="rounded-full px-4 py-2 {{ request('statut') === $statut->value ? 'bg-forest-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-forest-700' }}">{{ $statut->label() }}</a>
        @endforeach
    </div>
    <div class="space-y-4">
        @forelse($demandes as $demande)
        <article class="flex flex-col gap-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center">
            @if($demande->vetement->photoUrl())<img src="{{ $demande->vetement->photoUrl() }}" alt="" class="h-14 w-14 shrink-0 rounded-2xl object-cover">
            @else<span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-forest-50 text-forest-700"><i data-lucide="wrench" class="h-6 w-6"></i></span>@endif
            <div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-3"><h2 class="font-display text-lg font-bold">{{ $demande->vetement->nom }}</h2><x-badge :label="$demande->statut->label()" :color="$demande->statut->color()" /></div><p class="mt-1 text-sm text-slate-500">{{ $demande->type_reparation->label() }} · {{ $demande->atelier->nom }} · {{ $demande->reference() }}</p></div>
            <div class="text-sm text-slate-500"><p>Créée le {{ $demande->created_at->translatedFormat('d F Y') }}</p><a href="{{ route('reparations.show', $demande) }}" class="mt-1 inline-flex items-center gap-1 font-semibold text-forest-700">Voir le suivi <i data-lucide="arrow-right" class="h-4 w-4"></i></a></div>
        </article>
        @empty
        <div class="rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center">
            <span class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-forest-50 text-forest-700"><i data-lucide="wrench" class="h-7 w-7"></i></span>
            <h2 class="font-display text-xl font-bold">Aucune demande {{ request('statut') ? 'avec ce statut' : 'pour le moment' }}</h2>
            <p class="mt-2 text-slate-500">Un bouton, une couture, une fermeture ? Confiez votre vêtement à un atelier partenaire.</p>
        </div>
        @endforelse
    </div>
    <div class="mt-8">{{ $demandes->links() }}</div>
</section>
@endsection
