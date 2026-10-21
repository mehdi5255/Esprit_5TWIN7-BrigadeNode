@extends('layouts.back')
@section('title', 'Profil de l’association')
@section('breadcrumb', $association->nom . ' / Profil')
@section('content')
<form method="POST" action="{{ route('association.profil.update') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-3">
    @csrf @method('PUT')
    <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
        <div><h2 class="font-display text-lg font-bold">Informations publiques</h2><p class="mt-1 text-sm text-slate-500">Affichées sur votre page association.</p></div>
        <div class="grid gap-5 sm:grid-cols-2">
            <x-champ label="Nom de l’association" name="nom" :value="$association->nom" :required="true" />
            <x-champ label="Domaine d’action" name="domaine_action" :value="$association->domaine_action" placeholder="Ex. Aide aux personnes sans abri" />
        </div>
        <x-champ label="Description" name="description" type="textarea" :rows="4" :value="$association->description" />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-champ label="E-mail de contact" name="email" type="email" :value="$association->email" />
            <x-champ label="Téléphone" name="telephone" :value="$association->telephone" />
        </div>
        <x-champ label="Adresse de dépôt des dons" name="adresse" :value="$association->adresse" />
        <div class="grid gap-5 sm:grid-cols-3">
            <x-champ label="Code postal" name="code_postal" :value="$association->code_postal" />
            <div class="sm:col-span-2"><x-champ label="Ville" name="ville" :value="$association->ville" :required="true" /></div>
        </div>
    </section>
    <aside class="space-y-6">
        <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-display font-bold">Logo</h2>
            @if($association->logoUrl())<img src="{{ $association->logoUrl() }}" alt="Logo actuel" class="h-24 w-24 rounded-2xl object-cover">@endif
            <x-champ :label="$association->logo ? 'Remplacer le logo' : 'Ajouter un logo'" name="logo" type="file" accept="image/*" data-max-ko="2048" help="PNG ou JPG, 2 Mo maximum." />
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 text-sm shadow-sm">
            <h2 class="font-display font-bold">Statut</h2>
            <div class="mt-3"><x-badge :label="$association->statut->label()" :color="$association->statut->color()" /></div>
            @if($association->estActif())<a href="{{ route('associations.show', $association) }}" class="mt-4 inline-flex items-center gap-2 font-semibold text-forest-700"><i data-lucide="external-link" class="h-4 w-4"></i>Voir ma page publique</a>@endif
        </section>
        <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-forest-700"><i data-lucide="save" class="h-4 w-4"></i>Enregistrer</button>
    </aside>
</form>
@endsection
