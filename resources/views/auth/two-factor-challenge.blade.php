@extends('layouts.front')

@section('title', 'Vérification en deux étapes')

@section('content')
<section class="mx-auto max-w-md px-4 py-16 sm:px-6">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10" data-deux-etapes>
        <div class="mb-8"><span class="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-forest-100 text-forest-700"><i data-lucide="smartphone" class="h-6 w-6"></i></span><h1 class="font-display text-3xl font-bold">Vérification en deux étapes</h1>
            <p class="mt-2 text-slate-500" data-texte-code>Saisissez le code à 6 chiffres affiché dans votre application d’authentification (Google Authenticator, Microsoft Authenticator…).</p>
            <p class="mt-2 hidden text-slate-500" data-texte-recuperation>Saisissez l’un de vos codes de récupération. Chaque code ne fonctionne qu’une fois.</p>
        </div>
        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5">
            @csrf
            <div data-bloc-code @if($errors->has('recovery_code')) class="hidden" @endif>
                <x-champ label="Code de vérification" name="code" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" maxlength="6" autofocus />
            </div>
            <div data-bloc-recuperation @unless($errors->has('recovery_code')) class="hidden" @endunless>
                <x-champ label="Code de récupération" name="recovery_code" autocomplete="off" placeholder="abcdef1234-abcdef1234" />
            </div>
            <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-forest-700"><i data-lucide="shield-check" class="h-4 w-4"></i>Valider</button>
        </form>
        <button type="button" data-basculer class="mt-6 w-full text-center text-sm font-semibold text-forest-700 hover:underline">Utiliser un code de récupération</button>
        <p class="mt-4 text-center text-sm text-slate-500"><a href="{{ route('login') }}" class="hover:underline">Retour à la connexion</a></p>
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Bascule entre le code de l'application et un code de récupération (un seul champ envoyé à la fois).
    (() => {
        const bloc = document.querySelector('[data-deux-etapes]');
        const bouton = bloc.querySelector('[data-basculer]');
        const majBouton = () => {
            const recuperation = !bloc.querySelector('[data-bloc-recuperation]').classList.contains('hidden');
            bouton.textContent = recuperation ? 'Utiliser le code de l’application' : 'Utiliser un code de récupération';
            bloc.querySelector('[data-texte-code]').classList.toggle('hidden', recuperation);
            bloc.querySelector('[data-texte-recuperation]').classList.toggle('hidden', !recuperation);
        };
        bouton.addEventListener('click', () => {
            ['[data-bloc-code]', '[data-bloc-recuperation]'].forEach(s => bloc.querySelector(s).classList.toggle('hidden'));
            bloc.querySelectorAll('[name=code], [name=recovery_code]').forEach(champ => { champ.value = ''; });
            majBouton();
            bloc.querySelector('[data-bloc-code]:not(.hidden) input, [data-bloc-recuperation]:not(.hidden) input')?.focus();
        });
        majBouton();
    })();
</script>
@endpush
