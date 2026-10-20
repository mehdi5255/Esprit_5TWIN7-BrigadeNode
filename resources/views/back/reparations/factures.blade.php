@extends('layouts.back')
@section('title', 'Mes factures')
@section('breadcrumb', 'Atelier / Factures')
@section('content')
@php $euros = fn ($montant) => number_format((float) $montant, 2, ',', ' ').' €'; @endphp
<div class="mb-6 grid gap-5 sm:grid-cols-3">
    <x-card title="Encaissé" :value="$euros($totaux['encaisse'])" icon="wallet" accent="green" />
    <x-card title="À encaisser" :value="$euros($totaux['a_encaisser'])" icon="hourglass" accent="amber" />
    <x-card title="Factures émises" :value="$totaux['nombre']" icon="receipt" accent="blue" />
</div>
<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap gap-2 border-b border-slate-100 p-4 text-sm font-semibold">
        <a href="{{ route('atelier.factures.index') }}" class="rounded-full px-4 py-2 {{ request('statut') ? 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-forest-700' : 'bg-forest-600 text-white' }}">Toutes</a>
        @foreach(\App\Enums\StatutFacture::cases() as $statut)
        <a href="{{ route('atelier.factures.index', ['statut' => $statut->value]) }}" class="rounded-full px-4 py-2 {{ request('statut') === $statut->value ? 'bg-forest-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-forest-700' }}">{{ $statut->label() }}</a>
        @endforeach
    </div>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Facture</th><th class="px-6 py-3">Client</th><th class="px-6 py-3">Demande</th><th class="px-6 py-3">Émise le</th><th class="px-6 py-3 text-right">Total</th><th class="px-6 py-3">Statut</th><th class="px-6 py-3"></th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($factures as $facture)
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4 font-semibold">{{ $facture->numero() }}@if($facture->supplement > 0)<span class="block text-xs font-normal text-amber-700">dont supplément {{ $euros($facture->supplement) }}</span>@endif</td>
                <td class="px-6 py-4">{{ $facture->demande->particulier->name }}</td>
                <td class="px-6 py-4"><a href="{{ route('atelier.reparations.show', $facture->demande) }}" class="font-semibold text-forest-700 hover:underline">{{ $facture->demande->reference() }}</a><span class="block text-xs text-slate-500">{{ $facture->demande->vetement->nom }}</span></td>
                <td class="px-6 py-4 text-slate-500">{{ $facture->date_emission->format('d/m/Y') }}</td>
                <td class="px-6 py-4 text-right font-semibold">{{ $euros($facture->montant_total) }}</td>
                <td class="px-6 py-4"><x-badge :label="$facture->statut->label()" :color="$facture->statut->color()" />@if($facture->garantie)<span class="mt-1 block text-xs text-slate-500">Garantie jusqu'au {{ $facture->garantie->date_fin->format('d/m/Y') }}</span>@endif</td>
                <td class="px-6 py-4 text-right"><a href="{{ route('atelier.reparations.facture', $facture->demande) }}" class="inline-flex items-center gap-1 font-semibold text-slate-600 hover:text-forest-700"><i data-lucide="printer" class="h-4 w-4"></i>Voir</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">Aucune facture {{ request('statut') ? 'avec ce statut' : 'pour le moment' }}. Une facture est émise quand vous terminez une réparation.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($factures->hasPages())<div class="border-t border-slate-100 px-6 py-4">{{ $factures->links() }}</div>@endif
</section>
@endsection
