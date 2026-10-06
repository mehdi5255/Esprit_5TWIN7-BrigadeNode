@extends('layouts.backoffice')
@php $ateliers = $type === 'ateliers'; @endphp
@section('title', $ateliers ? 'Ateliers' : 'Associations')
@section('breadcrumb', 'Partenaires / ' . ($ateliers ? 'Ateliers' : 'Associations'))
@section('content')
<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <form method="GET" action="{{ route("admin.{$type}.index") }}" class="flex flex-wrap gap-3 border-b border-slate-100 p-4">
        <div class="flex min-w-64 flex-1 items-center gap-2 rounded-xl bg-slate-100 px-3"><i data-lucide="search" class="h-4 w-4 text-slate-400"></i><input name="q" value="{{ request('q') }}" class="w-full bg-transparent py-2.5 text-sm outline-none" placeholder="Rechercher par nom, ville ou code postal"></div>
        <select name="statut" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold"><option value="">Tous les statuts</option>@foreach(\App\Enums\StatutStructure::cases() as $statut)<option value="{{ $statut->value }}" @selected(request('statut') === $statut->value)>{{ $statut->label() }}</option>@endforeach</select>
        <button class="rounded-xl bg-forest-600 px-4 text-sm font-semibold text-white hover:bg-forest-700">Filtrer</button>
    </form>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">{{ $ateliers ? 'Atelier' : 'Association' }}</th><th class="px-6 py-3">Responsable</th><th class="px-6 py-3">{{ $ateliers ? 'Services actifs' : 'Besoins actifs' }}</th><th class="px-6 py-3">Demandes</th><th class="px-6 py-3">Note</th><th class="px-6 py-3">Statut</th><th class="px-6 py-3"></th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($structures as $structure)
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4"><a href="{{ route("admin.{$type}.show", $structure->id) }}" class="font-semibold hover:text-forest-700">{{ $structure->nom }}</a><p class="text-xs text-slate-500">{{ trim($structure->code_postal.' '.$structure->ville) }}</p></td>
                <td class="px-6 py-4 text-slate-600">{{ $structure->responsable?->name ?? '—' }}</td>
                <td class="px-6 py-4 text-slate-500">{{ $ateliers ? $structure->services_count : $structure->besoins_count }}</td>
                <td class="px-6 py-4 text-slate-500">{{ $ateliers ? $structure->demandes_reparation_count + $structure->demandes_transformation_count : $structure->demandes_don_count }}</td>
                <td class="px-6 py-4">@include('components.etoiles', ['note' => $structure->noteMoyenne(), 'taille' => 'h-3 w-3'])</td>
                <td class="px-6 py-4">@include('components.badge', ['label' => $structure->statut->label(), 'color' => $structure->statut->color()])</td>
                <td class="px-6 py-4 text-right">@include('admin.structures._actions', ['compact' => true])</td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">Aucun résultat.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($structures->hasPages())<div class="border-t border-slate-100 px-6 py-4">{{ $structures->links() }}</div>@endif
</section>
@endsection
