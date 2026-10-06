@extends('layouts.frontoffice')

@section('title', 'Mot de passe oublié')

@section('content')
<section class="mx-auto max-w-md px-4 py-16 sm:px-6">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <div class="mb-8"><span class="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-forest-100 text-forest-700"><i data-lucide="key-round" class="h-6 w-6"></i></span><h1 class="font-display text-3xl font-bold">Mot de passe oublié</h1><p class="mt-2 text-slate-500">Indiquez votre e-mail : nous vous enverrons un lien pour choisir un nouveau mot de passe.</p></div>
        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf
            @include('components.champ', ['label' => 'E-mail', 'name' => 'email', 'type' => 'email', 'required' => true, 'attrs' => 'autofocus autocomplete="email"'])
            <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-forest-700"><i data-lucide="send" class="h-4 w-4"></i>Envoyer le lien</button>
        </form>
        <p class="mt-6 text-center text-sm text-slate-500"><a href="{{ route('login') }}" class="font-semibold text-forest-700 hover:underline">Retour à la connexion</a></p>
    </div>
</section>
@endsection
