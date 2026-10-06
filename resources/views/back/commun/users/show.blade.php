@extends('layouts.back')
@section('title', $user->name)
@section('breadcrumb', 'Utilisateurs / ' . $user->name)
@section('content')
@php $structure = $user->atelier ?? $user->association; @endphp
<div class="grid gap-6 xl:grid-cols-3">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
        <div class="flex flex-wrap items-center gap-4">
            <span class="grid h-14 w-14 place-items-center rounded-full bg-forest-100 text-lg font-bold text-forest-700">{{ strtoupper(mb_substr($user->name, 0, 2)) }}</span>
            <div class="flex-1"><h2 class="font-display text-xl font-bold">{{ $user->name }}</h2><p class="text-sm text-slate-500">{{ $user->email }}</p></div>
            <x-badge :label="$user->statut->label()" :color="$user->statut->color()" />
        </div>
        <dl class="mt-6 grid gap-3 text-sm sm:grid-cols-3">
            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Rôle</dt><dd class="mt-1 font-semibold">{{ $user->roleLabel() }}</dd></div>
            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Inscrit le</dt><dd class="mt-1 font-semibold">{{ $user->created_at->format('d/m/Y') }}</dd></div>
            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Dernière mise à jour</dt><dd class="mt-1 font-semibold">{{ $user->updated_at->format('d/m/Y') }}</dd></div>
            @if($user->hasRole('particulier'))
                @foreach(['Vêtements' => $user->vetements_count, 'Réparations' => $user->demandes_reparation_count, 'Transformations' => $user->demandes_transformation_count, 'Dons' => $user->demandes_don_count, 'Avis laissés' => $user->reviews_count] as $label => $valeur)
                <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">{{ $label }}</dt><dd class="mt-1 font-semibold">{{ $valeur }}</dd></div>
                @endforeach
            @endif
        </dl>
        @if($structure)
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-4">
            <div><p class="text-xs text-slate-500">{{ $user->hasRole('atelier') ? 'Atelier' : 'Association' }}</p><p class="font-semibold">{{ $structure->nom }}</p><p class="text-sm text-slate-500">{{ $structure->adresseComplete() }}</p></div>
            <a href="{{ route('admin.'.($user->hasRole('atelier') ? 'ateliers' : 'associations').'.show', $structure->id) }}" class="text-sm font-semibold text-forest-700">Voir la fiche</a>
        </div>
        @endif
    </section>
    <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="font-display font-bold">Actions</h2>
        <div class="mt-4 flex flex-wrap gap-2">@include('back.commun.users._actions', ['compact' => false])</div>
        @if($user->hasRole('admin'))<p class="mt-2 text-sm text-slate-500">Les comptes administrateur ne peuvent être ni bloqués ni supprimés depuis l'interface.</p>@endif
        <p class="mt-6 text-xs leading-5 text-slate-400">Bloquer un compte le déconnecte immédiatement et masque sa structure. La suppression n'est possible que pour un compte sans historique de demandes.</p>
    </aside>
</div>
@endsection
