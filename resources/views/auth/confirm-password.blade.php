@extends('layouts.front')

@section('title', 'Confirmez votre mot de passe')

@section('content')
<section class="mx-auto max-w-md px-4 py-16 sm:px-6">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <div class="mb-8"><span class="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-forest-100 text-forest-700"><i data-lucide="shield-check" class="h-6 w-6"></i></span><h1 class="font-display text-3xl font-bold">Zone sécurisée</h1><p class="mt-2 text-slate-500">Confirmez votre mot de passe avant de continuer.</p></div>
        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
            @csrf
            <x-champ label="Mot de passe" name="password" type="password" :required="true" autocomplete="current-password" autofocus />
            <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-forest-700">Confirmer</button>
        </form>
    </div>
</section>
@endsection
