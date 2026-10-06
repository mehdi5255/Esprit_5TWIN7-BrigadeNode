@extends('layouts.backoffice')
@section('title', 'Dépôts')
@section('breadcrumb', $espace === 'admin' ? 'Supervision / Dépôts' : 'Demandes / Dépôts')
@section('content')
@php
    $admin = $espace === 'admin';
    $delai = \App\Support\Depots::DELAI_JOURS;
    $lienDemande = fn ($ligne) => route("{$espace}.{$ligne['segment']}.show", $ligne['demande']);
    $couleurType = ['Réparation' => 'amber', 'Transformation' => 'blue', 'Don' => 'green'];
@endphp

<div class="mb-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    @include('components.card', ['title' => 'Dépôts attendus', 'value' => $stats['attendus'], 'icon' => 'package', 'accent' => 'blue'])
    @include('components.card', ['title' => "En retard (+ de {$delai} jours)", 'value' => $stats['en_retard'], 'icon' => 'alarm-clock', 'accent' => 'amber'])
    @include('components.card', ['title' => 'Reçus (30 derniers jours)', 'value' => $stats['recus'], 'icon' => 'package-check', 'accent' => 'green'])
    @include('components.card', ['title' => 'Délai moyen de dépôt', 'value' => $stats['delai_moyen'] !== null ? number_format($stats['delai_moyen'], 1, ',', '').' j' : '—', 'icon' => 'timer', 'accent' => 'violet'])
</div>

<p class="mb-4 flex items-center gap-2 text-sm text-slate-500">
    <i data-lucide="{{ $admin ? 'eye' : 'info' }}" class="h-4 w-4"></i>
    @if($admin)
        Supervision en lecture seule : la réception des vêtements est confirmée par les ateliers et les associations.
    @else
        Confirmez ici la réception des vêtements que les particuliers vous apportent. Au-delà de {{ $delai }} jours, un dépôt est signalé en retard.
    @endif
</p>

@if(count($types) > 1)
<div class="mb-6 flex flex-wrap gap-2 text-sm font-semibold">
    <a href="{{ route("{$espace}.depots.index") }}" class="rounded-full px-4 py-2 {{ request('type') ? 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-forest-700' : 'bg-forest-600 text-white' }}">Toutes les demandes</a>
    @foreach($types as $segment => $libelle)
    <a href="{{ route("{$espace}.depots.index", ['type' => $segment]) }}" class="rounded-full px-4 py-2 {{ request('type') === $segment ? 'bg-forest-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-forest-700' }}">{{ $libelle }}s</a>
    @endforeach
</div>
@endif

{{-- ── Dépôts attendus ─────────────────────────────────────── --}}
<section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-6 py-5"><h2 class="font-display text-lg font-bold">Vêtements attendus</h2><p class="mt-1 text-sm text-slate-500">Demandes acceptées, en attente du dépôt par le particulier — les plus anciennes d'abord</p></div>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Demande</th><th class="px-6 py-3">Particulier</th><th class="px-6 py-3">Vêtement</th>@if($admin)<th class="px-6 py-3">Destinataire</th>@endif<th class="px-6 py-3">Acceptée le</th><th class="px-6 py-3">Attente</th><th class="px-6 py-3"></th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($attendus as $ligne)
            <tr class="{{ $ligne['en_retard'] ? 'bg-amber-50/60' : 'hover:bg-slate-50' }}">
                <td class="px-6 py-4"><a href="{{ $lienDemande($ligne) }}" class="font-semibold hover:text-forest-700">{{ $ligne['demande']->reference() }}</a><div class="mt-1">@include('components.badge', ['label' => $ligne['type'], 'color' => $couleurType[$ligne['type']]])</div></td>
                <td class="px-6 py-4">{{ $ligne['demande']->particulier->name }}</td>
                <td class="px-6 py-4 text-slate-500">{{ $ligne['demande']->vetement->nom }}</td>
                @if($admin)<td class="px-6 py-4 text-slate-500">{{ $ligne['structure']?->nom }}</td>@endif
                <td class="px-6 py-4 text-slate-500">{{ $ligne['demande']->date_acceptation?->format('d/m/Y') ?? '—' }}</td>
                <td class="px-6 py-4">
                    @if($ligne['jours'] === null)
                        <span class="text-slate-400">—</span>
                    @elseif($ligne['en_retard'])
                        <span class="inline-flex items-center gap-1 font-semibold text-amber-700"><i data-lucide="alarm-clock" class="h-4 w-4"></i>{{ $ligne['jours'] }} jours</span>
                    @else
                        <span class="text-slate-600">{{ $ligne['jours'] }} jour{{ $ligne['jours'] > 1 ? 's' : '' }}</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-right">
                    @if($admin)
                        <a href="{{ $lienDemande($ligne) }}" class="inline-flex items-center gap-1 font-semibold text-forest-700">Consulter <i data-lucide="arrow-right" class="h-4 w-4"></i></a>
                    @else
                        <form method="POST" action="{{ route("{$espace}.{$ligne['segment']}.depot", $ligne['demande']) }}">
                            @csrf <input type="hidden" name="retour" value="depots">
                            <button class="inline-flex items-center gap-2 rounded-xl bg-forest-600 px-3 py-2 text-xs font-semibold text-white hover:bg-forest-700"><i data-lucide="package-check" class="h-4 w-4"></i>Confirmer le dépôt</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">Aucun vêtement attendu pour le moment.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>

{{-- ── Dépôts reçus ────────────────────────────────────────── --}}
<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-6 py-5"><h2 class="font-display text-lg font-bold">Vêtements reçus</h2><p class="mt-1 text-sm text-slate-500">Dépôts confirmés sur les 30 derniers jours</p></div>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Demande</th><th class="px-6 py-3">Particulier</th><th class="px-6 py-3">Vêtement</th>@if($admin)<th class="px-6 py-3">Destinataire</th>@endif<th class="px-6 py-3">Déposé le</th><th class="px-6 py-3">Délai</th><th class="px-6 py-3">Statut actuel</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($recus as $ligne)
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4"><a href="{{ $lienDemande($ligne) }}" class="font-semibold hover:text-forest-700">{{ $ligne['demande']->reference() }}</a><div class="mt-1">@include('components.badge', ['label' => $ligne['type'], 'color' => $couleurType[$ligne['type']]])</div></td>
                <td class="px-6 py-4">{{ $ligne['demande']->particulier->name }}</td>
                <td class="px-6 py-4 text-slate-500">{{ $ligne['demande']->vetement->nom }}</td>
                @if($admin)<td class="px-6 py-4 text-slate-500">{{ $ligne['structure']?->nom }}</td>@endif
                <td class="px-6 py-4 text-slate-500">{{ $ligne['demande']->date_depot_effectif->format('d/m/Y H:i') }}</td>
                <td class="px-6 py-4 text-slate-600">{{ $ligne['delai'] !== null ? number_format($ligne['delai'], 1, ',', '').' j' : '—' }}</td>
                <td class="px-6 py-4">@include('components.badge', ['label' => $ligne['demande']->statut->label(), 'color' => $ligne['demande']->statut->color()])</td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">Aucun dépôt confirmé ces 30 derniers jours.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
