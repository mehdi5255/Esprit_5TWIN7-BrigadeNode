@extends('layouts.backoffice')
@section('title', 'Rapports')
@section('breadcrumb', 'Supervision / Rapports d’activité')
@section('content')
<form method="GET" action="{{ route('admin.rapports.index') }}" class="mb-6 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="w-44">@include('components.champ', ['label' => 'Du', 'name' => 'du', 'type' => 'date', 'value' => $du->toDateString()])</div>
    <div class="w-44">@include('components.champ', ['label' => 'Au', 'name' => 'au', 'type' => 'date', 'value' => $au->toDateString()])</div>
    <button class="rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white hover:bg-forest-700">Générer</button>
    <a href="{{ route('admin.rapports.export', ['du' => $du->toDateString(), 'au' => $au->toDateString()]) }}" class="ml-auto inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="download" class="h-4 w-4"></i>Exporter en CSV</a>
</form>
<p class="mb-4 text-sm text-slate-500">Période du <span class="font-semibold text-slate-700">{{ $du->translatedFormat('d F Y') }}</span> au <span class="font-semibold text-slate-700">{{ $au->translatedFormat('d F Y') }}</span></p>
<div class="mb-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    @include('components.card', ['title' => 'Demandes créées', 'value' => $resume['demandes'], 'icon' => 'clipboard-list', 'accent' => 'blue'])
    @include('components.card', ['title' => 'Demandes terminées', 'value' => $resume['terminees'], 'icon' => 'circle-check', 'accent' => 'green'])
    @include('components.card', ['title' => 'Chiffre d’affaires ateliers', 'value' => number_format($resume['chiffre'], 2, ',', ' ').' €', 'icon' => 'euro', 'accent' => 'violet'])
    @include('components.card', ['title' => 'Bénéficiaires aidés', 'value' => $resume['beneficiaires'], 'icon' => 'heart', 'accent' => 'amber'])
</div>
<div class="grid gap-6 xl:grid-cols-3">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
        <div class="border-b border-slate-100 px-6 py-5"><h2 class="font-display text-lg font-bold">Demandes par type</h2><p class="mt-1 text-sm text-slate-500">Demandes créées sur la période · {{ $resume['inscriptions'] }} nouvelle{{ $resume['inscriptions'] > 1 ? 's' : '' }} inscription{{ $resume['inscriptions'] > 1 ? 's' : '' }}</p></div>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Type</th><th class="px-6 py-3">Total</th><th class="px-6 py-3">Terminées</th><th class="px-6 py-3">Refusées</th><th class="px-6 py-3">Annulées</th><th class="px-6 py-3">Taux de réussite</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @foreach($parType as $libelle => $ligne)
                <tr><td class="px-6 py-4 font-semibold">{{ $libelle }}</td><td class="px-6 py-4">{{ $ligne['total'] }}</td><td class="px-6 py-4">{{ $ligne['terminees'] }}</td><td class="px-6 py-4">{{ $ligne['refusees'] }}</td><td class="px-6 py-4">{{ $ligne['annulees'] }}</td><td class="px-6 py-4">{{ $ligne['total'] ? round($ligne['terminees'] / $ligne['total'] * 100).' %' : '—' }}</td></tr>
            @endforeach
            </tbody>
        </table></div>
    </section>
    <aside class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-display font-bold">Ateliers les plus actifs</h2>
            <ol class="mt-4 space-y-2 text-sm">
                @forelse($topAteliers as $atelier)<li class="flex justify-between"><span>{{ $atelier->nom }}</span><strong>{{ $atelier->terminees_rep + $atelier->terminees_tra }}</strong></li>@empty<li class="text-slate-500">Aucune demande terminée.</li>@endforelse
            </ol>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-display font-bold">Associations les plus actives</h2>
            <ol class="mt-4 space-y-2 text-sm">
                @forelse($topAssociations as $association)<li class="flex justify-between"><span>{{ $association->nom }}</span><strong>{{ $association->remis }} don{{ $association->remis > 1 ? 's' : '' }}</strong></li>@empty<li class="text-slate-500">Aucun don remis.</li>@endforelse
            </ol>
        </section>
    </aside>
</div>
@endsection
