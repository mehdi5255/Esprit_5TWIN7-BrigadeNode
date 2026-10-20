@extends('layouts.back')
@section('title', 'Tableau de bord')
@section('breadcrumb', 'Vue d’ensemble de la plateforme')
@section('content')
<div class="mb-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <x-card title="Vêtements enregistrés" :value="$stats['vetements']" icon="shirt" accent="green" />
    <x-card title="Demandes actives" :value="$stats['demandes_actives']" icon="clipboard-list" accent="amber" />
    <x-card title="Partenaires actifs" :value="$stats['partenaires']" icon="handshake" accent="blue" />
    <x-card title="Vêtements sauvés" :value="$stats['vetements_sauves']" icon="leaf" accent="violet" />
</div>

@if($enAttente->isNotEmpty())
@can('gerer-utilisateurs')
<section class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-6">
    <h2 class="flex items-center gap-2 font-display text-lg font-bold text-amber-900"><i data-lucide="hourglass" class="h-5 w-5"></i>{{ $enAttente->count() }} compte{{ $enAttente->count() > 1 ? 's' : '' }} en attente de validation</h2>
    <div class="mt-4 divide-y divide-amber-200/60">
        @foreach($enAttente as $compte)
        <div class="flex flex-wrap items-center gap-4 py-3">
            <div class="min-w-0 flex-1"><p class="font-semibold">{{ $compte->atelier?->nom ?? $compte->association?->nom ?? $compte->name }}</p><p class="text-sm text-amber-900/70">{{ $compte->roleLabel() }} · {{ $compte->name }} · {{ $compte->email }} · inscrit {{ $compte->created_at->diffForHumans() }}</p></div>
            <a href="{{ route('admin.users.show', $compte) }}" class="text-sm font-semibold text-amber-900 hover:underline">Consulter</a>
            <form method="POST" action="{{ route('admin.users.valider', $compte) }}">@csrf<button class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white hover:bg-forest-700">Valider</button></form>
        </div>
        @endforeach
    </div>
</section>
@endcan
@endif

<div class="grid gap-6 xl:grid-cols-3">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
        <h2 class="font-display text-lg font-bold">Activité des demandes</h2><p class="mt-1 text-sm text-slate-500">Nouvelles demandes sur les 6 derniers mois</p>
        @php $max = max(1, $activite->max('total')); @endphp
        <div class="mt-8 flex h-56 items-end gap-3 sm:gap-5">
            @foreach($activite as $mois)
            <div class="flex flex-1 flex-col items-center gap-3">
                <div class="relative flex h-44 w-full items-end rounded-lg bg-slate-50" title="{{ $mois['total'] }} demande(s)">
                    <div class="w-full rounded-lg bg-forest-500" style="height: {{ max(2, $mois['total'] / $max * 100) }}%"></div>
                    <span class="absolute inset-x-0 -top-5 text-center text-xs font-semibold text-slate-500">{{ $mois['total'] }}</span>
                </div>
                <span class="text-xs text-slate-400">{{ $mois['label'] }}</span>
            </div>
            @endforeach
        </div>
    </section>
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="font-display text-lg font-bold">Répartition des services</h2><p class="mt-1 text-sm text-slate-500">Toutes les demandes</p>
        @php
            $total = max(1, $repartition->sum());
            $pctRep = round($repartition['Réparations'] / $total * 100);
            $pctTra = round($repartition['Transformations'] / $total * 100);
        @endphp
        <div class="mx-auto my-7 grid h-40 w-40 place-items-center rounded-full" style="background: conic-gradient(#2f7d57 0 {{ $pctRep }}%, #8b5cf6 {{ $pctRep }}% {{ $pctRep + $pctTra }}%, #f59e0b {{ $pctRep + $pctTra }}% 100%)"><div class="grid h-24 w-24 place-items-center rounded-full bg-white text-center"><div><strong class="font-display text-2xl">{{ $repartition->sum() }}</strong><span class="block text-xs text-slate-400">demandes</span></div></div></div>
        <div class="space-y-3 text-sm">
            @foreach(['Réparations' => 'bg-forest-500', 'Transformations' => 'bg-violet-500', 'Dons' => 'bg-amber-500'] as $label => $couleur)
            <div class="flex items-center"><span class="mr-3 h-2.5 w-2.5 rounded-full {{ $couleur }}"></span><span class="text-slate-600">{{ $label }}</span><strong class="ml-auto">{{ $repartition[$label] }} <span class="font-normal text-slate-400">({{ round($repartition[$label] / $total * 100) }} %)</span></strong></div>
            @endforeach
        </div>
    </section>
</div>

<section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5"><div><h2 class="font-display text-lg font-bold">Demandes récentes</h2><p class="mt-1 text-sm text-slate-500">Dernières activités de la plateforme · {{ $stats['utilisateurs'] }} utilisateurs · note moyenne {{ $stats['note_moyenne'] ? number_format($stats['note_moyenne'], 1, ',', '').' / 5' : '—' }}</p></div>@can('voir-rapports')<a href="{{ route('admin.rapports.index') }}" class="text-sm font-semibold text-forest-700">Rapports</a>@endcan</div>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-6 py-3">Demande</th><th class="px-6 py-3">Membre</th><th class="px-6 py-3">Partenaire</th><th class="px-6 py-3">Statut</th><th class="px-6 py-3">Date</th></tr></thead><tbody class="divide-y divide-slate-100">
        @forelse($recentes as $demande)
        @php $segment = ['REP' => 'reparations', 'TRA' => 'transformations', 'DON' => 'dons'][$demande::PREFIXE]; @endphp
        <tr class="hover:bg-slate-50"><td class="px-6 py-4 font-semibold"><a href="{{ route('admin.'.$segment.'.show', $demande) }}" class="hover:text-forest-700">{{ $demande->reference() }}</a></td><td class="px-6 py-4">{{ $demande->particulier->name }}</td><td class="px-6 py-4 text-slate-500">{{ $demande->structure->nom }}</td><td class="px-6 py-4"><x-badge :label="$demande->statut->label()" :color="$demande->statut->color()" /></td><td class="px-6 py-4 text-slate-500">{{ $demande->created_at->format('d/m/Y') }}</td></tr>
        @empty
        <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">Aucune demande pour le moment.</td></tr>
        @endforelse
    </tbody></table></div>
</section>
@endsection
