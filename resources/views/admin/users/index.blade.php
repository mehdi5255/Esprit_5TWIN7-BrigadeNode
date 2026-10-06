@extends('layouts.backoffice')
@section('title', 'Utilisateurs')
@section('breadcrumb', 'Administration / Utilisateurs')
@section('content')
<div class="mb-6 grid gap-5 sm:grid-cols-3">
    @include('components.card',['title'=>'Utilisateurs','value'=>$stats['total'],'icon'=>'users','accent'=>'green'])
    @include('components.card',['title'=>'En attente de validation','value'=>$stats['en_attente'],'icon'=>'hourglass','accent'=>'amber'])
    @include('components.card',['title'=>'Comptes bloqués','value'=>$stats['bloques'],'icon'=>'ban','accent'=>'violet'])
</div>
<p class="mb-4 flex items-center gap-2 text-sm text-slate-500"><i data-lucide="shield-check" class="h-4 w-4"></i>Conformément au RGPD, les données personnelles ne sont modifiables que par leur titulaire.</p>
<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-3 border-b border-slate-100 p-4">
        <div class="flex min-w-64 flex-1 items-center gap-2 rounded-xl bg-slate-100 px-3"><i data-lucide="search" class="h-4 w-4 text-slate-400"></i><input name="q" value="{{ request('q') }}" class="w-full bg-transparent py-2.5 text-sm outline-none" placeholder="Rechercher par nom ou e-mail"></div>
        <select name="role" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold"><option value="">Tous les rôles</option>@foreach(\App\Models\User::ROLES as $value => $label)<option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>@endforeach</select>
        <select name="statut" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold"><option value="">Tous les statuts</option>@foreach(\App\Enums\StatutCompte::cases() as $statut)<option value="{{ $statut->value }}" @selected(request('statut') === $statut->value)>{{ $statut->label() }}</option>@endforeach</select>
        <button class="rounded-xl bg-forest-600 px-4 text-sm font-semibold text-white hover:bg-forest-700">Filtrer</button>
    </form>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Utilisateur</th><th class="px-6 py-3">Rôle</th><th class="px-6 py-3">Structure</th><th class="px-6 py-3">Statut</th><th class="px-6 py-3">Inscrit le</th><th class="px-6 py-3"></th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($users as $user)
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4"><a href="{{ route('admin.users.show', $user) }}" class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-full bg-forest-100 text-xs font-bold text-forest-700">{{ strtoupper(mb_substr($user->name, 0, 2)) }}</span><span><span class="block font-semibold hover:text-forest-700">{{ $user->name }}</span><span class="text-xs text-slate-500">{{ $user->email }}</span></span></a></td>
                <td class="px-6 py-4">@include('components.badge',['label'=>$user->roleLabel(),'color'=>['admin'=>'blue','atelier'=>'amber','association'=>'green'][$user->role] ?? 'gray'])</td>
                <td class="px-6 py-4 text-slate-500">{{ $user->structure()?->nom ?? '—' }}</td>
                <td class="px-6 py-4">@include('components.badge',['label'=>$user->statut->label(),'color'=>$user->statut->color()])</td>
                <td class="px-6 py-4 text-slate-500">{{ $user->created_at?->format('d/m/Y') }}</td>
                <td class="px-6 py-4"><div class="flex items-center justify-end gap-2">@include('admin.users._actions', ['compact' => true])</div></td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">Aucun utilisateur trouvé.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($users->hasPages())<div class="border-t border-slate-100 px-6 py-4">{{ $users->links() }}</div>@endif
</section>
@endsection
