@extends('layouts.back')
@section('title', 'Matières')
@section('breadcrumb', 'Administration / Matières')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <p class="max-w-2xl text-sm text-slate-500">Catalogue des matières proposées aux particuliers pour décrire la composition de leurs vêtements. L'impact par kg sert à estimer le CO₂ et l'eau économisés.</p>
    <x-button :href="route('admin.matieres.create')" label="Nouvelle matière" icon="plus" />
</div>
<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <form method="GET" action="{{ route('admin.matieres.index') }}" class="flex gap-3 border-b border-slate-100 p-4">
        <div class="flex min-w-64 flex-1 items-center gap-2 rounded-xl bg-slate-100 px-3"><i data-lucide="search" class="h-4 w-4 text-slate-400"></i><input name="q" value="{{ request('q') }}" class="w-full bg-transparent py-2.5 text-sm outline-none" placeholder="Rechercher une matière"></div>
        <button class="rounded-xl bg-forest-600 px-4 text-sm font-semibold text-white hover:bg-forest-700">Rechercher</button>
    </form>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Matière</th><th class="px-6 py-3">Type</th><th class="px-6 py-3">Abréviations</th><th class="px-6 py-3 text-right">CO₂ / kg</th><th class="px-6 py-3 text-right">Eau / kg</th><th class="px-6 py-3 text-right">Vêtements</th><th class="px-6 py-3"></th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($matieres as $matiere)
            <tr class="hover:bg-slate-50 {{ $matiere->actif ? '' : 'opacity-60' }}">
                <td class="px-6 py-4 font-semibold">{{ $matiere->nom }}@unless($matiere->actif)<span class="ml-2 text-xs font-normal text-slate-400">(désactivée)</span>@endunless</td>
                <td class="px-6 py-4"><x-badge :label="$matiere->type->label()" :color="$matiere->type->color()" /></td>
                <td class="px-6 py-4 text-slate-500">{{ implode(', ', $matiere->abreviations ?? []) ?: '—' }}</td>
                <td class="px-6 py-4 text-right">{{ number_format((float) $matiere->co2_kg_par_kg, 1, ',', ' ') }} kg</td>
                <td class="px-6 py-4 text-right">{{ number_format($matiere->eau_litres_par_kg, 0, ',', ' ') }} L</td>
                <td class="px-6 py-4 text-right">{{ $matiere->vetements_count }}</td>
                <td class="px-6 py-4"><div class="flex items-center justify-end gap-1">
                    <a href="{{ route('admin.matieres.edit', $matiere) }}" class="rounded-lg px-3 py-2 font-semibold text-slate-600 hover:bg-slate-100">Modifier</a>
                    <form method="POST" action="{{ route('admin.matieres.basculer', $matiere) }}">@csrf<button class="rounded-lg px-3 py-2 font-semibold text-slate-600 hover:bg-slate-100">{{ $matiere->actif ? 'Désactiver' : 'Activer' }}</button></form>
                    <form method="POST" action="{{ route('admin.matieres.destroy', $matiere) }}" data-confirmer="Supprimer la matière {{ $matiere->nom }} ?">@csrf @method('DELETE')<button class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Supprimer"><i data-lucide="trash-2" class="h-4 w-4"></i></button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">Aucune matière.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
