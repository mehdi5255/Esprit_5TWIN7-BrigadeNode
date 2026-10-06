@extends('layouts.back')
@section('title', 'Catégories de vêtements')
@section('breadcrumb', 'Administration / Catégories')
@section('content')
<div class="grid gap-6 xl:grid-cols-3">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Catégorie</th><th class="px-6 py-3">Vêtements</th><th class="px-6 py-3">Statut</th><th class="px-6 py-3"></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($categories as $categorie)
                <tr>
                    <td class="px-6 py-4"><div class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-forest-50 text-forest-700"><i data-lucide="{{ $categorie->icone }}" class="h-4 w-4"></i></span><div><p class="font-semibold">{{ $categorie->libelle }}</p>@if($categorie->description)<p class="text-xs text-slate-500">{{ $categorie->description }}</p>@endif</div></div></td>
                    <td class="px-6 py-4 text-slate-500">{{ $categorie->vetements_count }}</td>
                    <td class="px-6 py-4"><x-badge :label="$categorie->actif ? 'Active' : 'Inactive'" :color="$categorie->actif ? 'green' : 'gray'" /></td>
                    <td class="px-6 py-4"><div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.categories.edit', $categorie) }}" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-forest-700" aria-label="Modifier"><i data-lucide="pencil" class="h-4 w-4"></i></a>
                        <form method="POST" action="{{ route('admin.categories.destroy', $categorie) }}" data-confirmer="Supprimer la catégorie {{ $categorie->libelle }} ?">@csrf @method('DELETE')<button class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Supprimer"><i data-lucide="trash-2" class="h-4 w-4"></i></button></form>
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-6 py-12 text-center text-slate-500">Aucune catégorie.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
    <section class="h-fit rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="font-display text-lg font-bold">Nouvelle catégorie</h2>
        <form method="POST" action="{{ route('admin.categories.store') }}" class="mt-5 space-y-4">
            @include('back.vetements.categories._form', ['categorie' => new \App\Models\Vetements\CategorieVetement(['actif' => true]), 'submit' => 'Ajouter'])
        </form>
    </section>
</div>
@endsection
