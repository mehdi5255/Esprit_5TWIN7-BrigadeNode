@extends('layouts.front')
@section('title', $evenement->titre)
@section('meta', $evenement->type->label().' organisée par '.$evenement->association->nom)
@section('content')
<section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
    <a href="{{ route('evenements.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-forest-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Tous les événements</a>
    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <div class="flex flex-wrap items-center gap-2"><x-badge :label="$evenement->type->label()" :color="$evenement->type->color()" /><x-badge :label="$evenement->statutLibelle()" :color="$evenement->statutCouleur()" /></div>
        <h1 class="mt-4 font-display text-4xl font-bold">{{ $evenement->titre }}</h1>
        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <p class="flex items-center gap-3 rounded-2xl bg-slate-50 p-4 text-sm"><i data-lucide="calendar" class="h-5 w-5 text-rose-600"></i>{{ $evenement->horaire() }}</p>
            <p class="flex items-center gap-3 rounded-2xl bg-slate-50 p-4 text-sm"><i data-lucide="map-pin" class="h-5 w-5 text-rose-600"></i>{{ $evenement->lieu }}</p>
        </div>
        @include('partials.carte.lieu')
        @if($evenement->description)<p class="mt-6 leading-7 text-slate-600">{{ $evenement->description }}</p>@endif
        <a href="{{ route('associations.show', $evenement->association) }}" class="mt-6 flex items-center gap-3 rounded-2xl p-4 ring-1 ring-slate-200 hover:ring-forest-300">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-rose-50 text-rose-600"><i data-lucide="heart-handshake" class="h-5 w-5"></i></span>
            <span><span class="block font-semibold">{{ $evenement->association->nom }}</span><span class="text-sm text-slate-500">Organisateur</span></span>
        </a>

        @if($evenement->estCollecte())
        <div class="mt-8 border-t border-slate-100 pt-6">
            @if($evenement->accepteDesDons())
                <p class="mb-4 text-sm text-slate-500">{{ $evenement->placesRestantes() !== null ? $evenement->placesRestantes().' place(s) restante(s).' : 'Places sans limite.' }} Proposez votre don en choisissant cette collecte comme lieu de dépôt.</p>
                @if(auth()->user()?->hasRole('particulier'))
                    <x-button :href="route('dons.create', ['collecte' => $evenement->id])" label="Déposer un don à cette collecte" icon="package-plus" />
                @elseif(! auth()->check())
                    <x-button :href="route('login')" label="Se connecter pour proposer un don" icon="log-in" />
                @endif
            @else
                <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">{{ $evenement->annule ? 'Cette collecte est annulée.' : ($evenement->estPasse() ? 'Cette collecte est terminée.' : 'Cette collecte est complète.') }} Vous pouvez toujours proposer un don et le déposer au local de l'association.</p>
            @endif
        </div>
        @endif
    </article>
</section>
@endsection
