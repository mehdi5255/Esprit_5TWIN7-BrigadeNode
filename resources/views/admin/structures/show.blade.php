@extends('layouts.backoffice')
@php $ateliers = $type === 'ateliers'; @endphp
@section('title', $structure->nom)
@section('breadcrumb', ($ateliers ? 'Ateliers' : 'Associations') . ' / ' . $structure->nom)
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">@include('components.badge', ['label' => $structure->statut->label(), 'color' => $structure->statut->color()])@include('components.etoiles', ['note' => $stats['note'], 'avis' => $stats['avis']])</div>
    <div class="flex gap-2">
        @if($structure->estActif())@include('components.button', ['href' => route($ateliers ? 'ateliers.show' : 'associations.show', $structure), 'label' => 'Page publique', 'icon' => 'external-link', 'variant' => 'secondary'])@endif
        @include('admin.structures._actions', ['compact' => false])
    </div>
</div>
<div class="mb-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    @if($ateliers)
        @include('components.card', ['title' => 'En attente', 'value' => $stats['en_attente'], 'icon' => 'inbox', 'accent' => 'blue'])
        @include('components.card', ['title' => 'En cours', 'value' => $stats['en_cours'], 'icon' => 'scissors', 'accent' => 'amber'])
        @include('components.card', ['title' => 'Réalisées', 'value' => $stats['realisees'], 'icon' => 'circle-check', 'accent' => 'green'])
        @include('components.card', ['title' => 'Chiffre d’affaires', 'value' => number_format($stats['chiffre'], 2, ',', ' ').' €', 'icon' => 'euro', 'accent' => 'violet'])
    @else
        @include('components.card', ['title' => 'Propositions en attente', 'value' => $stats['en_attente'], 'icon' => 'inbox', 'accent' => 'blue'])
        @include('components.card', ['title' => 'Dons en cours', 'value' => $stats['en_cours'], 'icon' => 'package', 'accent' => 'amber'])
        @include('components.card', ['title' => 'Dons remis', 'value' => $stats['remis'], 'icon' => 'heart', 'accent' => 'green'])
        @include('components.card', ['title' => 'Bénéficiaires', 'value' => $stats['beneficiaires'], 'icon' => 'users', 'accent' => 'violet'])
    @endif
</div>
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5"><h2 class="font-display text-lg font-bold">{{ $ateliers ? 'Services proposés' : 'Besoins déclarés' }}</h2></div>
            <div class="divide-y divide-slate-100">
            @if($ateliers)
                @forelse($structure->services as $service)
                <div class="flex flex-wrap items-center gap-3 px-6 py-4 text-sm {{ $service->actif ? '' : 'opacity-60' }}"><span class="min-w-0 flex-1 font-semibold">{{ $service->nom }}</span>@include('components.badge', ['label' => $service->type->label(), 'color' => $service->type->color()])<span class="text-slate-500">{{ $service->prixFormate() }}</span>@unless($service->actif)@include('components.badge', ['label' => 'Désactivé', 'color' => 'gray'])@endunless</div>
                @empty
                <p class="px-6 py-8 text-center text-sm text-slate-500">Aucun service créé.</p>
                @endforelse
            @else
                @forelse($structure->besoins as $besoin)
                <div class="flex flex-wrap items-center gap-3 px-6 py-4 text-sm {{ $besoin->actif ? '' : 'opacity-60' }}"><span class="min-w-0 flex-1 font-semibold">{{ $besoin->titre }}</span>@include('components.badge', ['label' => $besoin->priorite->label(), 'color' => $besoin->priorite->color()])<span class="text-slate-500">{{ $besoin->collectes() }} / {{ $besoin->quantite }}</span>@unless($besoin->actif)@include('components.badge', ['label' => 'Désactivé', 'color' => 'gray'])@endunless</div>
                @empty
                <p class="px-6 py-8 text-center text-sm text-slate-500">Aucun besoin déclaré.</p>
                @endforelse
            @endif
            </div>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between"><h2 class="font-display text-lg font-bold">Derniers avis</h2><a href="{{ route('admin.reviews.index') }}" class="text-sm font-semibold text-forest-700">Modération</a></div>
            <div class="mt-4 space-y-4">
                @forelse($reviews as $review)
                <div class="text-sm {{ $review->statut->value === 'masque' ? 'opacity-50' : '' }}"><div class="flex items-center justify-between"><span class="font-semibold">{{ $review->particulier->name }}</span>@include('components.etoiles', ['note' => $review->note, 'taille' => 'h-3 w-3'])</div>@if($review->commentaire)<p class="mt-1 text-slate-500">{{ $review->commentaire }}</p>@endif</div>
                @empty
                <p class="text-sm text-slate-500">Aucun avis.</p>
                @endforelse
            </div>
        </section>
    </div>
    <aside class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-display font-bold">Profil</h2>
            <dl class="mt-4 space-y-3 text-sm">
                @foreach([
                    ['tag', $ateliers ? $structure->specialite : $structure->domaine_action],
                    ['map-pin', $structure->adresseComplete()],
                    ['mail', $structure->email],
                    ['phone', $structure->telephone],
                    ['user', $structure->responsable ? 'Responsable : '.$structure->responsable->name : null],
                ] as [$icone, $valeur])
                    @if($valeur)<div class="flex items-start gap-3"><i data-lucide="{{ $icone }}" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400"></i><span>{{ $valeur }}</span></div>@endif
                @endforeach
            </dl>
            @if($structure->description)<p class="mt-4 text-sm leading-6 text-slate-600">{{ $structure->description }}</p>@endif
            @if($structure->responsable)<a href="{{ route('admin.users.show', $structure->responsable) }}" class="mt-4 inline-flex text-sm font-semibold text-forest-700">Compte du responsable</a>@endif
        </section>
    </aside>
</div>
@endsection
