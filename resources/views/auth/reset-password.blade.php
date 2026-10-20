@extends('layouts.front')

@section('title', 'Nouveau mot de passe')

@section('content')
<section class="mx-auto max-w-md px-4 py-16 sm:px-6">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <div class="mb-8"><span class="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-forest-100 text-forest-700"><i data-lucide="lock-keyhole" class="h-6 w-6"></i></span><h1 class="font-display text-3xl font-bold">Nouveau mot de passe</h1><p class="mt-2 text-slate-500">Choisissez un nouveau mot de passe pour votre compte.</p></div>
        <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <x-champ label="E-mail" name="email" type="email" :value="$request->email" :required="true" autocomplete="email" />
            <x-champ label="Nouveau mot de passe" name="password" type="password" :required="true" help="8 caractères minimum." autocomplete="new-password" autofocus />
            <x-champ label="Confirmer le mot de passe" name="password_confirmation" type="password" :required="true" autocomplete="new-password" />
            <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-forest-700">Enregistrer le mot de passe</button>
        </form>
    </div>
</section>
@endsection
