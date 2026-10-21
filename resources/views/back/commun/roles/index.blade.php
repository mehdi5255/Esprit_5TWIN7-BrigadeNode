@extends('layouts.back')
@section('title', 'Rôles et permissions')
@section('breadcrumb', 'Pilotage / Rôles et permissions')
@section('content')
@php $carte = 'rounded-2xl border border-slate-200 bg-white shadow-sm'; @endphp

<p class="mb-6 max-w-3xl text-sm text-slate-500">Chaque compte a un rôle ; chaque rôle a des permissions. Les pages vérifient la permission <strong>côté serveur</strong> (middleware <code class="rounded bg-slate-100 px-1">permission:</code> de Spatie) : retirer une permission ferme immédiatement l’accès correspondant.</p>

{{-- Matrice rôles × permissions --}}
<section class="{{ $carte }} overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-3">Permission</th>
                    @foreach($roles as $role)
                    <th class="px-4 py-3 text-center">{{ \App\Models\User::ROLES[$role->name] ?? $role->name }}<span class="block font-normal normal-case text-slate-400">{{ $role->users_count }} compte{{ $role->users_count > 1 ? 's' : '' }}</span></th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($permissions as $espace => $liste)
                <tr class="bg-slate-50/60"><td colspan="{{ $roles->count() + 1 }}" class="px-5 py-2 text-xs font-bold uppercase tracking-wide text-forest-700">{{ $espace }}</td></tr>
                @foreach($liste as $cle => [$libelle])
                <tr>
                    <td class="px-5 py-3"><span class="font-medium text-slate-800">{{ $libelle }}</span><code class="ml-2 text-xs text-slate-400">{{ $cle }}</code></td>
                    @foreach($roles as $role)
                    <td class="px-4 py-3 text-center">
                        <input type="checkbox" form="role-{{ $role->id }}" name="permissions[]" value="{{ $cle }}" aria-label="{{ $libelle }} — {{ $role->name }}"
                               @checked($role->permissions->contains('name', $cle)) @disabled($role->name === 'admin')
                               class="h-4 w-4 rounded border-slate-300 text-forest-600 focus:ring-forest-500 disabled:opacity-50">
                    </td>
                    @endforeach
                </tr>
                @endforeach
                @endforeach
            </tbody>
            <tfoot class="border-t border-slate-200 bg-slate-50">
                <tr>
                    <td class="px-5 py-3 text-xs text-slate-500">Cochez puis enregistrez le rôle modifié.</td>
                    @foreach($roles as $role)
                    <td class="px-4 py-3 text-center">
                        @if($role->name === 'admin')
                            <span class="text-xs text-slate-400">Toutes</span>
                        @else
                            <form id="role-{{ $role->id }}" method="POST" action="{{ route('admin.roles.update', $role) }}">@csrf @method('PUT')
                                <button class="rounded-lg bg-forest-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-forest-700">Enregistrer</button>
                            </form>
                        @endif
                    </td>
                    @endforeach
                </tr>
            </tfoot>
        </table>
    </div>
</section>

{{-- Modérateurs --}}
<section class="{{ $carte }} mt-6 p-6">
    <h2 class="font-display text-lg font-bold">Modérateurs</h2>
    <p class="mt-1 text-sm text-slate-500">Un modérateur entre dans l’administration, mais ne voit que ce que permet son rôle (par défaut : la supervision des demandes et la modération des avis).</p>
    <form method="POST" action="{{ route('admin.roles.nommer') }}" class="mt-4 flex flex-wrap items-end gap-3">@csrf
        <div class="w-full max-w-sm"><x-champ label="E-mail d’un particulier" name="email" type="email" :required="true" placeholder="prenom.nom@example.com" /></div>
        <button class="rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white hover:bg-forest-700">Nommer modérateur</button>
    </form>
    <ul class="mt-5 divide-y divide-slate-100 text-sm">
        @forelse($moderateurs as $moderateur)
        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
            <span><span class="font-semibold">{{ $moderateur->name }}</span> <span class="text-slate-500">· {{ $moderateur->email }}</span></span>
            <form method="POST" action="{{ route('admin.roles.retirer', $moderateur) }}" data-confirmer="Retirer le rôle de modérateur ?">@csrf @method('DELETE')
                <button class="font-semibold text-red-600 hover:underline">Retirer le rôle</button>
            </form>
        </li>
        @empty
        <li class="py-3 text-slate-500">Aucun modérateur pour le moment.</li>
        @endforelse
    </ul>
</section>
@endsection
