@extends('layouts.front')

@section('title', 'Accueil')
@section('meta', 'Réparez, transformez ou donnez vos vêtements avec TextileCycle.')

@section('content')
<section class="relative overflow-hidden bg-forest-900 text-white">
    <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-emerald-400/10 blur-3xl"></div>
    <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-24">
        <div class="relative z-10">
            <span class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-emerald-100"><i data-lucide="leaf" class="h-3.5 w-3.5"></i>La mode circulaire, simplement</span>
            <h1 class="font-display text-4xl font-extrabold leading-tight sm:text-5xl lg:text-6xl">Vos vêtements méritent une <span class="text-emerald-300">seconde histoire.</span></h1>
            <p class="mt-6 max-w-xl text-lg leading-8 text-emerald-50/70">Trouvez le bon atelier, transformez vos pièces oubliées ou transmettez-les à une association locale.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <x-button :href="route('vetements.create')" label="Ajouter un vêtement" icon="plus" />
                <a href="{{ route('ateliers.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/20 px-4 py-2.5 text-sm font-semibold hover:bg-white/10">Découvrir les ateliers <i data-lucide="arrow-right" class="h-4 w-4"></i></a>
            </div>
            <div class="mt-10 flex gap-8 text-sm"><div><strong class="block font-display text-2xl">2 840</strong><span class="text-emerald-100/60">vêtements sauvés</span></div><div><strong class="block font-display text-2xl">48</strong><span class="text-emerald-100/60">partenaires locaux</span></div></div>
        </div>
        <div class="relative">
            <img src="https://images.unsplash.com/photo-1673201229733-69d19c5c4a87?crop=entropy&cs=tinysrgb&fit=crop&fm=jpg&q=85&w=1000" alt="Artisane travaillant sur une machine à coudre" class="h-[480px] w-full rounded-[2rem] object-cover shadow-2xl ring-1 ring-white/10">
            <div class="absolute -bottom-5 -left-5 rounded-2xl bg-white p-4 text-slate-800 shadow-xl"><div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-100 text-emerald-700"><i data-lucide="sparkles" class="h-5 w-5"></i></span><div><p class="text-xs text-slate-500">Impact cette semaine</p><p class="font-display font-bold">126 kg de textile sauvés</p></div></div></div>
        </div>
    </div>
</section>
<section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
    <div class="mb-10 max-w-2xl"><p class="mb-2 text-sm font-bold uppercase tracking-widest text-forest-600">Choisissez votre action</p><h2 class="font-display text-3xl font-bold text-slate-900">Une solution pour chaque vêtement</h2></div>
    <div class="grid gap-5 md:grid-cols-3">
        @foreach([
            ['wrench','Réparer','Un bouton, une fermeture ou une couture : trouvez un artisan proche de vous.','bg-amber-50 text-amber-700','reparations.create'],
            ['scissors','Transformer','Imaginez une nouvelle coupe et donnez une nouvelle fonction à votre pièce.','bg-violet-50 text-violet-700','transformations.create'],
            ['heart-handshake','Donner','Confiez les vêtements que vous ne portez plus à une association engagée.','bg-emerald-50 text-emerald-700','dons.create']
        ] as [$icon,$title,$text,$color,$route])
        <article class="group rounded-3xl border border-slate-200 bg-white p-7 transition hover:-translate-y-1 hover:shadow-xl">
            <span class="grid h-12 w-12 place-items-center rounded-2xl {{ $color }}"><i data-lucide="{{ $icon }}" class="h-6 w-6"></i></span>
            <h3 class="mt-6 font-display text-xl font-bold">{{ $title }}</h3><p class="mt-3 leading-7 text-slate-500">{{ $text }}</p>
            <a href="{{ route($route) }}" class="mt-6 inline-flex items-center gap-2 text-sm font-bold text-forest-700">Commencer <i data-lucide="arrow-up-right" class="h-4 w-4"></i></a>
        </article>
        @endforeach
    </div>
</section>
@endsection
