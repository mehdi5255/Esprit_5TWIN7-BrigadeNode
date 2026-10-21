@extends('layouts.back')
@section('title', 'Tableau de bord')
@section('breadcrumb', $association->nom)
@section('content')
<div class="mb-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <x-card title="Propositions en attente" :value="$stats['en_attente']" icon="inbox" accent="blue" />
    <x-card title="Dons en cours" :value="$stats['en_cours']" icon="package" accent="amber" />
    <x-card title="Bénéficiaires aidés" :value="$stats['beneficiaires']" icon="heart" accent="green" />
    <x-card title="Note moyenne" :value="$stats['note'] ? number_format($stats['note'], 1, ',', '').' / 5' : '—'" icon="star" accent="violet" />
</div>
<div class="grid gap-6 xl:grid-cols-3">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5"><h2 class="font-display text-lg font-bold">Dernières propositions de don</h2><a href="{{ route('association.dons.index') }}" class="text-sm font-semibold text-forest-700">Tout voir</a></div>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><tbody class="divide-y divide-slate-100">
            @forelse($dernieres as $demande)
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4 font-semibold"><a href="{{ route('association.dons.show', $demande) }}" class="hover:text-forest-700">{{ $demande->reference() }}</a></td>
                <td class="px-6 py-4">{{ $demande->particulier->name }}</td>
                <td class="px-6 py-4 text-slate-500">{{ $demande->vetement->nom }}</td>
                <td class="px-6 py-4"><x-badge :label="$demande->statut->label()" :color="$demande->statut->color()" /></td>
                <td class="px-6 py-4 text-slate-500">{{ $demande->created_at->format('d/m/Y') }}</td>
            </tr>
            @empty
            <tr><td class="px-6 py-12 text-center text-slate-500">Aucune proposition de don pour le moment.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
    <aside class="space-y-6">
        <section class="rounded-2xl bg-forest-900 p-6 text-white">
            <p class="text-sm text-emerald-100/70">Dons remis aux bénéficiaires</p>
            <p class="mt-2 font-display text-3xl font-bold">{{ $stats['remis'] }}</p>
            <p class="mt-4 text-sm text-emerald-100/70">{{ $stats['besoins_actifs'] }} besoin{{ $stats['besoins_actifs'] > 1 ? 's' : '' }} actif{{ $stats['besoins_actifs'] > 1 ? 's' : '' }} · {{ $stats['avis'] }} avis</p>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between"><h2 class="font-display font-bold">Besoins prioritaires</h2><a href="{{ route('association.besoins.index') }}" class="text-sm font-semibold text-forest-700">Gérer</a></div>
            <div class="mt-4 space-y-4">
                @forelse($besoins as $besoin)
                <div class="text-sm"><div class="flex items-center justify-between gap-2"><span class="font-semibold">{{ $besoin->titre }}</span><x-badge :label="$besoin->priorite->label()" :color="$besoin->priorite->color()" /></div><div class="mt-2 h-1.5 rounded-full bg-slate-100"><div class="h-1.5 rounded-full bg-forest-500" style="width: {{ $besoin->progression() }}%"></div></div><p class="mt-1 text-xs text-slate-400">{{ $besoin->collectes() }} / {{ $besoin->quantite }}</p></div>
                @empty
                <p class="text-sm text-slate-500">Aucun besoin actif. <a href="{{ route('association.besoins.create') }}" class="font-semibold text-forest-700">Déclarer un besoin</a></p>
                @endforelse
            </div>
        </section>
    </aside>
</div>
@endsection
