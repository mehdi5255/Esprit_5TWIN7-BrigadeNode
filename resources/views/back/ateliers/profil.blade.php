@extends('layouts.back')
@section('title', 'Profil de l’atelier')
@section('breadcrumb', $atelier->nom . ' / Profil')
@section('content')
<form method="POST" action="{{ route('atelier.profil.update') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-3">
    @csrf @method('PUT')
    <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
        <div><h2 class="font-display text-lg font-bold">Informations publiques</h2><p class="mt-1 text-sm text-slate-500">Affichées sur votre page atelier.</p></div>
        <div class="grid gap-5 sm:grid-cols-2">
            <x-champ label="Nom de l’atelier" name="nom" :value="$atelier->nom" :required="true" />
            <x-champ label="Spécialité" name="specialite" :value="$atelier->specialite" placeholder="Ex. Retouches et réparations couture" />
        </div>
        <x-champ label="Description" name="description" type="textarea" :rows="4" :value="$atelier->description" placeholder="Savoir-faire, histoire, engagement…" />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-champ label="E-mail de contact" name="email" type="email" :value="$atelier->email" />
            <x-champ label="Téléphone" name="telephone" :value="$atelier->telephone" />
        </div>
        <x-champ label="Adresse" name="adresse" :value="$atelier->adresse" />
        <div class="grid gap-5 sm:grid-cols-3">
            <x-champ label="Code postal" name="code_postal" :value="$atelier->code_postal" />
            <div class="sm:col-span-2"><x-champ label="Ville" name="ville" :value="$atelier->ville" :required="true" /></div>
        </div>
        <x-champ label="Horaires" name="horaires" type="textarea" :rows="4" :value="$atelier->horaires" :placeholder='"Lun–Ven : 10h–19h\nSam : 10h–13h"' />
    </section>
    <aside class="space-y-6">
        <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-display font-bold">Logo</h2>
            @if($atelier->logoUrl())<img src="{{ $atelier->logoUrl() }}" alt="Logo actuel" class="h-24 w-24 rounded-2xl object-cover">@endif
            <x-champ :label="$atelier->logo ? 'Remplacer le logo' : 'Ajouter un logo'" name="logo" type="file" accept="image/*" data-max-ko="2048" help="PNG ou JPG, 2 Mo maximum." />
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 text-sm shadow-sm">
            <h2 class="font-display font-bold">Statut</h2>
            <div class="mt-3"><x-badge :label="$atelier->statut->label()" :color="$atelier->statut->color()" /></div>
            @if($atelier->estActif())<a href="{{ route('ateliers.show', $atelier) }}" class="mt-4 inline-flex items-center gap-2 font-semibold text-forest-700"><i data-lucide="external-link" class="h-4 w-4"></i>Voir ma page publique</a>@endif
        </section>
        <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-forest-700"><i data-lucide="save" class="h-4 w-4"></i>Enregistrer</button>
    </aside>
</form>
@endsection
