@extends('layouts.back')
@section('title', 'Tableau de bord')
@section('breadcrumb', $atelier->nom)
@section('content')
<div class="mb-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <x-card title="Demandes en attente" :value="$stats['en_attente']" icon="inbox" accent="blue" />
    <x-card title="En cours" :value="$stats['en_cours']" icon="scissors" accent="amber" />
    <x-card title="Réalisées" :value="$stats['realisees']" icon="circle-check" accent="green" />
    <x-card title="Note moyenne" :value="$stats['note'] ? number_format($stats['note'], 1, ',', '').' / 5' : '—'" icon="star" accent="violet" />
</div>
<div class="grid gap-6 xl:grid-cols-3">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
        <div class="border-b border-slate-100 px-6 py-5"><h2 class="font-display text-lg font-bold">Dernières demandes</h2></div>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><tbody class="divide-y divide-slate-100">
            @forelse($dernieres as $demande)
            @php $segment = $demande instanceof \App\Models\Reparations\DemandeReparation ? 'reparations' : 'transformations'; @endphp
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4 font-semibold"><a href="{{ route('atelier.'.$segment.'.show', $demande) }}" class="hover:text-forest-700">{{ $demande->reference() }}</a></td>
                <td class="px-6 py-4">{{ $demande->particulier->name }}</td>
                <td class="px-6 py-4 text-slate-500">{{ $demande->vetement->nom }}</td>
                <td class="px-6 py-4"><x-badge :label="$demande->statut->label()" :color="$demande->statut->color()" /></td>
                <td class="px-6 py-4 text-slate-500">{{ $demande->created_at->format('d/m/Y') }}</td>
            </tr>
            @empty
            <tr><td class="px-6 py-12 text-center text-slate-500">Aucune demande reçue pour le moment.@unless($atelier->services()->where('actif', true)->exists()) <a href="{{ route('atelier.services.create') }}" class="font-semibold text-forest-700">Créez vos services</a> pour apparaître dans les recherches.@endunless</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
    <aside class="space-y-6">
        <section class="rounded-2xl bg-forest-900 p-6 text-white">
            <p class="text-sm text-emerald-100/70">Chiffre d'affaires (demandes terminées)</p>
            <p class="mt-2 font-display text-3xl font-bold">{{ number_format($stats['chiffre'], 2, ',', ' ') }} €</p>
            <p class="mt-4 text-sm text-emerald-100/70">{{ $stats['refusees'] }} demande{{ $stats['refusees'] > 1 ? 's' : '' }} refusée{{ $stats['refusees'] > 1 ? 's' : '' }} · {{ $stats['avis'] }} avis</p>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between"><h2 class="font-display font-bold">Derniers avis</h2><a href="{{ route('atelier.avis.index') }}" class="text-sm font-semibold text-forest-700">Tout voir</a></div>
            <div class="mt-4 space-y-4">
                @forelse($avis as $review)
                <div class="text-sm"><div class="flex items-center justify-between"><span class="font-semibold">{{ $review->particulier->name }}</span><x-etoiles :note="$review->note" taille="h-3 w-3" /></div>@if($review->commentaire)<p class="mt-1 line-clamp-2 text-slate-500">{{ $review->commentaire }}</p>@endif</div>
                @empty
                <p class="text-sm text-slate-500">Aucun avis pour le moment.</p>
                @endforelse
            </div>
        </section>
    </aside>
</div>
@endsection
