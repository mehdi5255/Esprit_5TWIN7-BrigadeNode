@extends('layouts.front')
@section('title', 'Nouveau projet de transformation')
@section('content')
<section class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
    <a href="{{ route('transformations.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-forest-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Retour à mes transformations</a>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <div class="mb-8"><span class="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-violet-50 text-violet-700"><i data-lucide="scissors" class="h-6 w-6"></i></span><h1 class="font-display text-3xl font-bold">Transformer un vêtement</h1><p class="mt-2 text-slate-500">Proposez votre idée à un atelier spécialisé en upcycling.</p></div>
        @if($vetements->isEmpty())
            @include('partials.front.aucun-vetement')
        @elseif($ateliers->isEmpty())
            <p class="rounded-2xl bg-slate-50 p-6 text-center text-sm text-slate-500">Aucun atelier ne propose de transformation pour le moment.</p>
        @else
        <form method="POST" action="{{ route('transformations.store') }}" class="space-y-6">
            @csrf
            @include('partials.front.choix-vetement')
            <x-champ label="Atelier d’upcycling" name="atelier_id" type="select" :required="true" vide="Choisir un atelier…" :value="$atelierChoisi" :options="$ateliers->mapWithKeys(fn ($a) => [$a->id => $a->nom.($a->specialite ? ' — '.$a->specialite : '').($a->ville ? ' ('.$a->ville.')' : '')])" />
            <x-champ label="Votre idée de transformation" name="idee" type="textarea" :rows="4" :required="true" placeholder="Ex. Transformer cette robe longue en jupe mi-longue avec une poche plaquée." />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-champ label="Budget maximum (€)" name="budget_max" type="number" placeholder="Ex. 60" min="0" step="0.5" />
                <x-champ label="Date souhaitée" name="date_souhaitee" type="date" :min="now()->toDateString()" />
            </div>
            <div class="flex justify-end gap-3">
                <x-button :href="route('transformations.index')" label="Annuler" variant="secondary" />
                <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-forest-700"><i data-lucide="send" class="h-4 w-4"></i>Envoyer le projet</button>
            </div>
        </form>
        @endif
    </div>
</section>
@endsection
