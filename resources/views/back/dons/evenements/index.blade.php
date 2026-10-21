@extends('layouts.back')
@section('title', 'Événements')
@section('breadcrumb', 'Association / Événements')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <p class="max-w-2xl text-sm text-slate-500">Organisez vos collectes (les particuliers y déposent leurs dons) et vos distributions (vous y remettez les dons aux bénéficiaires).</p>
    <x-button :href="route('association.evenements.create')" label="Nouvel événement" icon="plus" />
</div>
<div class="mb-6 flex flex-wrap gap-2 text-sm font-semibold">
    <a href="{{ route('association.evenements.index') }}" class="rounded-full px-4 py-2 {{ request('type') ? 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-forest-700' : 'bg-forest-600 text-white' }}">Tous</a>
    @foreach(\App\Enums\TypeEvenement::cases() as $type)
    <a href="{{ route('association.evenements.index', ['type' => $type->value]) }}" class="rounded-full px-4 py-2 {{ request('type') === $type->value ? 'bg-forest-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-forest-700' }}">{{ $type->label() }}s</a>
    @endforeach
</div>

@foreach(['À venir' => $aVenir, 'Passés et annulés' => $passes] as $titre => $liste)
<h2 class="mb-3 mt-8 font-display text-lg font-bold first:mt-0">{{ $titre }} <span class="text-sm font-medium text-slate-400">({{ $liste->count() }})</span></h2>
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    @forelse($liste as $evenement)
    <a href="{{ route('association.evenements.show', $evenement) }}" class="flex flex-wrap items-center gap-4 border-b border-slate-100 p-5 last:border-0 hover:bg-slate-50 {{ $evenement->annule ? 'opacity-60' : '' }}">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-forest-50 text-forest-700"><i data-lucide="{{ $evenement->type->icone() }}" class="h-5 w-5"></i></span>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2"><p class="font-semibold">{{ $evenement->titre }}</p><x-badge :label="$evenement->type->label()" :color="$evenement->type->color()" /><x-badge :label="$evenement->statutLibelle()" :color="$evenement->statutCouleur()" /></div>
            <p class="mt-1 text-sm text-slate-500">{{ $evenement->horaire() }} · {{ $evenement->lieu }}</p>
        </div>
        <p class="text-sm font-semibold text-slate-600">{{ $evenement->dons_count }} don(s){{ $evenement->places ? ' / '.$evenement->places.' places' : '' }}</p>
    </a>
    @empty
    <p class="p-8 text-center text-sm text-slate-500">Aucun événement.</p>
    @endforelse
</div>
@endforeach
@endsection
