@extends('layouts.frontoffice')

@section('title', 'Connexion')

@section('content')
<section class="mx-auto max-w-md px-4 py-16 sm:px-6">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <div class="mb-8"><span class="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-forest-100 text-forest-700"><i data-lucide="log-in" class="h-6 w-6"></i></span><h1 class="font-display text-3xl font-bold">Connexion</h1><p class="mt-2 text-slate-500">Accédez à votre dressing et au suivi de vos demandes.</p></div>
        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">E-mail</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="w-full rounded-xl border {{ $errors->has('email') ? 'border-red-400' : 'border-slate-200' }} bg-white px-4 py-3 text-sm outline-none focus:border-forest-500 focus:ring-4 focus:ring-forest-100">
                @error('email')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Mot de passe</span>
                <input type="password" name="password" required autocomplete="current-password" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-forest-500 focus:ring-4 focus:ring-forest-100">
            </label>
            <div class="flex items-center justify-between gap-3 text-sm">
                <label class="flex items-center gap-3 text-slate-600"><input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-forest-600 focus:ring-forest-500">Se souvenir de moi</label>
                <a href="{{ route('password.request') }}" class="font-semibold text-forest-700 hover:underline">Mot de passe oublié ?</a>
            </div>
            <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-forest-700">Se connecter</button>
        </form>
        <p class="mt-6 text-center text-sm text-slate-500">Pas encore de compte ? <a href="{{ route('register') }}" class="font-semibold text-forest-700 hover:underline">Inscrivez-vous</a></p>
    </div>
</section>
@endsection
