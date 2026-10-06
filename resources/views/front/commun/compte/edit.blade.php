@extends($layout)
@section('title', 'Mon compte')
@section('breadcrumb', 'Mon compte')
@section('content')
@php $carte = 'rounded-2xl border border-slate-200 bg-white p-6 shadow-sm'; @endphp
<section class="{{ $layout === 'layouts.front' ? 'mx-auto max-w-3xl px-4 py-12 sm:px-6' : 'max-w-3xl' }} space-y-6">
    @if($layout === 'layouts.front')<h1 class="font-display text-4xl font-bold">Mon compte</h1>@endif

    <form method="POST" action="{{ route('compte.update') }}" class="{{ $carte }} space-y-5">
        @csrf @method('PUT')
        <div><h2 class="font-display text-lg font-bold">Informations personnelles</h2><p class="mt-1 text-sm text-slate-500">{{ $user->roleLabel() }} · inscrit le {{ $user->created_at->translatedFormat('d F Y') }}</p></div>
        <div class="grid gap-5 sm:grid-cols-2">
            <x-champ label="Nom complet" name="name" :value="$user->name" :required="true" autocomplete="name" />
            <x-champ label="E-mail" name="email" type="email" :value="$user->email" :required="true" autocomplete="email" />
        </div>
        <button class="inline-flex items-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-forest-700"><i data-lucide="save" class="h-4 w-4"></i>Enregistrer</button>
    </form>

    <form method="POST" action="{{ route('compte.password') }}" class="{{ $carte }} space-y-5">
        @csrf @method('PUT')
        <h2 class="font-display text-lg font-bold">Mot de passe</h2>
        <x-champ label="Mot de passe actuel" name="current_password" type="password" :required="true" autocomplete="current-password" />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-champ label="Nouveau mot de passe" name="password" type="password" :required="true" help="8 caractères minimum." autocomplete="new-password" />
            <x-champ label="Confirmer" name="password_confirmation" type="password" :required="true" autocomplete="new-password" />
        </div>
        <button class="inline-flex items-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-forest-700"><i data-lucide="lock-keyhole" class="h-4 w-4"></i>Changer le mot de passe</button>
    </form>

    @unless($user->hasRole('admin'))
    <details class="{{ $carte }} border-red-200" @if($errors->has('password_suppression')) open @endif>
        <summary class="cursor-pointer font-display text-lg font-bold text-red-700">Supprimer mon compte</summary>
        <form method="POST" action="{{ route('compte.destroy') }}" class="mt-4 space-y-4" data-confirmer="Supprimer définitivement votre compte ?">
            @csrf @method('DELETE')
            <p class="text-sm text-slate-600">La suppression est définitive{{ $user->hasRole('particulier') ? ' : vos vêtements seront effacés' : ' : votre '.($user->hasRole('atelier') ? 'atelier' : 'association').' et ses données seront effacés' }}. Elle n'est possible que si vous n'avez aucun historique de demandes.</p>
            <x-champ label="Confirmez avec votre mot de passe" name="password_suppression" type="password" :required="true" autocomplete="current-password" />
            <button class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700"><i data-lucide="trash-2" class="h-4 w-4"></i>Supprimer mon compte</button>
        </form>
    </details>
    @endunless
</section>
@endsection
