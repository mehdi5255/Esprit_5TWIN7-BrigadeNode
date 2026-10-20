@extends('layouts.front')

@section('title', 'Inscription')

@section('content')
@php $role = old('role', $role); @endphp
<section class="mx-auto max-w-2xl px-4 py-16 sm:px-6">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <div class="mb-8"><span class="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-forest-100 text-forest-700"><i data-lucide="user-plus" class="h-6 w-6"></i></span><h1 class="font-display text-3xl font-bold">Créer un compte</h1><p class="mt-2 text-slate-500">Rejoignez la communauté TextileCycle.</p></div>
        <form method="POST" action="{{ route('register') }}" class="space-y-6" data-inscription>
            @csrf
            <fieldset>
                <legend class="mb-3 text-sm font-semibold text-slate-700">Je suis…</legend>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach([
                        'particulier' => ['shirt', 'Un particulier', 'Je fais réparer, transformer ou je donne mes vêtements.'],
                        'atelier' => ['store', 'Un atelier', 'Je propose des services de réparation ou d’upcycling.'],
                        'association' => ['heart-handshake', 'Une association', 'Je reçois des dons et les redistribue.'],
                    ] as $valeur => [$icone, $titre, $texte])
                    <label class="cursor-pointer rounded-2xl border border-slate-200 p-4 has-[:checked]:border-forest-500 has-[:checked]:bg-forest-50">
                        <input type="radio" name="role" value="{{ $valeur }}" class="sr-only" @checked($role === $valeur)>
                        <i data-lucide="{{ $icone }}" class="h-6 w-6 text-forest-600"></i>
                        <span class="mt-2 block font-semibold">{{ $titre }}</span>
                        <span class="mt-1 block text-xs text-slate-500">{{ $texte }}</span>
                    </label>
                    @endforeach
                </div>
                @error('role')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
            </fieldset>

            <div data-structure class="space-y-5 rounded-2xl bg-slate-50 p-5" @if($role === 'particulier') hidden @endif>
                <p class="text-sm text-slate-600"><i data-lucide="info" class="mr-1 inline h-4 w-4"></i>Votre compte professionnel sera vérifié par notre équipe avant d’être visible des particuliers.</p>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-champ label="Nom de la structure" name="structure_nom" placeholder="Ex. Atelier Fil Rouge" :required="true" data-requis-pro />
                    <x-champ label="Ville" name="structure_ville" placeholder="Paris" :required="true" data-requis-pro />
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-champ label="Nom complet" name="name" :required="true" autocomplete="name" />
                <x-champ label="E-mail" name="email" type="email" :required="true" autocomplete="email" />
                <x-champ label="Mot de passe" name="password" type="password" :required="true" help="8 caractères minimum." autocomplete="new-password" />
                <x-champ label="Confirmer le mot de passe" name="password_confirmation" type="password" :required="true" autocomplete="new-password" />
            </div>
            <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-forest-700">Créer mon compte</button>
        </form>
        <p class="mt-6 text-center text-sm text-slate-500">Déjà inscrit ? <a href="{{ route('login') }}" class="font-semibold text-forest-700 hover:underline">Connectez-vous</a></p>
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Affiche les champs « structure » uniquement pour un atelier ou une association.
    const formulaire = document.querySelector('[data-inscription]');
    const bloc = formulaire.querySelector('[data-structure]');
    const majStructure = () => {
        const pro = formulaire.querySelector('input[name=role]:checked')?.value !== 'particulier';
        bloc.hidden = !pro;
        bloc.querySelectorAll('[data-requis-pro]').forEach(champ => champ.required = pro);
    };
    formulaire.querySelectorAll('input[name=role]').forEach(radio => radio.addEventListener('change', majStructure));
    majStructure();
</script>
@endpush
