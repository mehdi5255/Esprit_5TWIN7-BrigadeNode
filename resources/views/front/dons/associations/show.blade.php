@extends('layouts.front')
@section('title', $association->nom)
@section('meta', $association->description ?: 'Association partenaire TextileCycle à ' . $association->ville . '.')
@section('content')
@php $peutDonner = ! auth()->check() || auth()->user()->hasRole('particulier'); @endphp
<section class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
    <a href="{{ route('associations.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-forest-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Toutes les associations</a>
    <div class="grid gap-6 md:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 md:col-span-2">
            @if($association->logoUrl())<img src="{{ $association->logoUrl() }}" alt="" class="h-16 w-16 rounded-2xl object-cover">
            @else<span class="grid h-16 w-16 place-items-center rounded-2xl bg-rose-50 text-rose-600"><i data-lucide="heart-handshake" class="h-8 w-8"></i></span>@endif
            <h1 class="mt-6 font-display text-4xl font-bold">{{ $association->nom }}</h1>
            <p class="mt-2 text-slate-500">{{ collect([$association->domaine_action, trim($association->code_postal.' '.$association->ville)])->filter()->join(' · ') }}</p>
            <div class="mt-3"><x-etoiles :note="$association->noteMoyenne()" :avis="$association->nombreAvis()" /></div>
            @if($association->description)<p class="mt-6 leading-7 text-slate-600">{{ $association->description }}</p>@endif
            @if($peutDonner)<div class="mt-8"><x-button :href="route('dons.create', ['association' => $association->id])" label="Faire un don" icon="heart-handshake" /></div>@endif
        </div>
        <aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-display font-bold">Coordonnées</h2>
            <dl class="mt-4 space-y-3 text-sm">
                @if($association->adresseComplete())<div class="flex items-start gap-3"><i data-lucide="map-pin" class="mt-0.5 h-4 w-4 shrink-0 text-forest-600"></i><span>{{ $association->adresseComplete() }}</span></div>@endif
                @if($association->telephone)<div class="flex items-start gap-3"><i data-lucide="phone" class="mt-0.5 h-4 w-4 shrink-0 text-forest-600"></i><span>{{ $association->telephone }}</span></div>@endif
                @if($association->email)<div class="flex items-start gap-3"><i data-lucide="mail" class="mt-0.5 h-4 w-4 shrink-0 text-forest-600"></i><span class="break-all">{{ $association->email }}</span></div>@endif
            </dl>
        </aside>
    </div>

    <h2 class="mb-5 mt-12 font-display text-2xl font-bold">Besoins actuels</h2>
    <div class="grid gap-5 md:grid-cols-2">
        @forelse($association->besoinsActifs as $besoin)
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4"><h3 class="font-display text-lg font-bold">{{ $besoin->titre }}</h3><x-badge :label="$besoin->priorite->label()" :color="$besoin->priorite->color()" /></div>
            @if($besoin->description)<p class="mt-2 text-sm leading-6 text-slate-500">{{ $besoin->description }}</p>@endif
            <div class="mt-4"><div class="mb-2 flex justify-between text-sm"><span class="text-slate-500">{{ $besoin->categorie?->libelle ?? 'Tous vêtements' }}</span><strong>{{ $besoin->collectes() }} / {{ $besoin->quantite }}</strong></div><div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-forest-500" style="width: {{ $besoin->progression() }}%"></div></div></div>
            @if($peutDonner)<a href="{{ route('dons.create', ['besoin' => $besoin->id]) }}" class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-forest-700">Donner pour ce besoin <i data-lucide="arrow-right" class="h-4 w-4"></i></a>@endif
        </article>
        @empty
        <p class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-500 md:col-span-2">Aucun besoin déclaré pour le moment : les dons libres sont bienvenus.</p>
        @endforelse
    </div>

    @if($evenements->isNotEmpty())
    <div class="mb-5 mt-12 flex flex-wrap items-end justify-between gap-3"><h2 class="font-display text-2xl font-bold">Prochains rendez-vous</h2><a href="{{ route('evenements.index', ['association' => $association->id]) }}" class="text-sm font-semibold text-forest-700 hover:underline">Tout l'agenda</a></div>
    <div class="grid gap-4 md:grid-cols-2">
        @foreach($evenements as $evenement)
        <a href="{{ route('evenements.show', $evenement) }}" class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-forest-300">
            <div class="grid h-14 w-14 shrink-0 place-items-center rounded-xl bg-rose-50 text-center text-rose-700"><div><p class="font-display text-lg font-bold leading-none">{{ $evenement->debut->format('d') }}</p><p class="text-[10px] font-semibold uppercase">{{ $evenement->debut->translatedFormat('M') }}</p></div></div>
            <div class="min-w-0"><x-badge :label="$evenement->type->label()" :color="$evenement->type->color()" /><p class="mt-1 font-semibold">{{ $evenement->titre }}</p><p class="text-sm text-slate-500">{{ $evenement->debut->format('H:i') }} · {{ $evenement->lieu }}</p></div>
        </a>
        @endforeach
    </div>
    @endif

    <h2 class="mb-5 mt-12 font-display text-2xl font-bold">Avis des donateurs</h2>
    @include('partials.front.avis', ['structure' => $association])
</section>
@endsection
