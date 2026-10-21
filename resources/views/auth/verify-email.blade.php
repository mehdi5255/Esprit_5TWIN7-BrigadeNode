@extends('layouts.front')

@section('title', 'Vérifiez votre adresse e-mail')

@section('content')
<section class="mx-auto max-w-md px-4 py-16 sm:px-6">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <div class="mb-6"><span class="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-forest-100 text-forest-700"><i data-lucide="mail-check" class="h-6 w-6"></i></span><h1 class="font-display text-3xl font-bold">Vérifiez votre adresse e-mail</h1></div>
        <p class="text-slate-600">Nous avons envoyé un lien de confirmation à <span class="font-semibold text-slate-800">{{ auth()->user()->email }}</span>. Cliquez dessus pour activer votre compte.</p>
        <p class="mt-3 text-sm text-slate-500">Pensez à regarder dans le dossier « Courrier indésirable ». Le lien est valable {{ config('auth.verification.expire', 60) }} minutes.</p>
        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-forest-700"><i data-lucide="send" class="h-4 w-4"></i>Renvoyer le lien</button>
        </form>
        <div class="mt-6 flex items-center justify-between text-sm">
            <a href="{{ route('compte.edit') }}" class="font-semibold text-forest-700 hover:underline">Corriger mon adresse</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="font-semibold text-slate-500 hover:text-red-600">Se déconnecter</button></form>
        </div>
    </div>
</section>
@endsection
