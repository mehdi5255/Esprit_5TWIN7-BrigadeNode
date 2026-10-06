@extends('layouts.front')

@section('title', $vetement->nom)

@section('content')
<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <a href="{{ route('vetements.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-forest-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Retour à mes vêtements</a>
    <div class="grid gap-8 lg:grid-cols-5">
        <div class="lg:col-span-2">
            @if($vetement->photoUrl())<img src="{{ $vetement->photoUrl() }}" alt="{{ $vetement->nom }}" class="aspect-[4/5] w-full rounded-3xl object-cover shadow-sm">
            @else<div class="grid aspect-[4/5] w-full place-items-center rounded-3xl bg-forest-50 text-forest-600"><i data-lucide="{{ $vetement->categorie->icone }}" class="h-16 w-16"></i></div>@endif
        </div>
        <div class="space-y-6 lg:col-span-3">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div><p class="text-xs font-bold uppercase tracking-wider text-forest-600">{{ $vetement->categorie->libelle }}</p><h1 class="mt-1 font-display text-3xl font-bold">{{ $vetement->nom }}</h1></div>
                    <x-badge :label="$vetement->statut->label()" :color="$vetement->statut->color()" />
                </div>
                <dl class="mt-6 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    @foreach(['État' => $vetement->etat->label(), 'Taille' => $vetement->taille, 'Matière' => $vetement->matiere, 'Couleur' => $vetement->couleur] as $label => $valeur)
                    <div class="rounded-2xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">{{ $label }}</dt><dd class="mt-1 font-semibold">{{ $valeur ?: '—' }}</dd></div>
                    @endforeach
                </dl>
                @if($vetement->description)<p class="mt-6 leading-7 text-slate-600">{{ $vetement->description }}</p>@endif
                <div class="mt-8 flex flex-wrap gap-3">
                    @if($vetement->estDisponible())
                        <x-button :href="route('reparations.create', ['vetement' => $vetement->id])" label="Réparer" icon="wrench" />
                        <x-button :href="route('transformations.create', ['vetement' => $vetement->id])" label="Transformer" icon="scissors" />
                        <x-button :href="route('dons.create', ['vetement' => $vetement->id])" label="Donner" icon="heart-handshake" />
                    @endif
                    <x-button :href="route('vetements.edit', $vetement)" label="Modifier" icon="pencil" variant="secondary" />
                    <form method="POST" action="{{ route('vetements.destroy', $vetement) }}" data-confirmer="Supprimer ce vêtement et son historique ?">@csrf @method('DELETE')<button class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"><i data-lucide="trash-2" class="h-4 w-4"></i>Supprimer</button></form>
                </div>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="font-display text-xl font-bold">Historique</h2>
                <ol class="mt-6 space-y-5 border-l-2 border-slate-100 pl-6">
                    @foreach($historique as $demande)
                    @php
                        [$segment, $titre] = match ($demande::class) {
                            \App\Models\Reparations\DemandeReparation::class => ['reparations', 'Réparation · '.$demande->type_reparation->label()],
                            \App\Models\Transformations\DemandeTransformation::class => ['transformations', 'Transformation'],
                            \App\Models\Dons\DemandeDon::class => ['dons', 'Don'],
                        };
                    @endphp
                    <li class="relative">
                        <span class="absolute -left-[33px] grid h-4 w-4 place-items-center rounded-full bg-white ring-2 ring-forest-500"></span>
                        <div class="flex flex-wrap items-center gap-3"><a href="{{ route($segment.'.show', $demande) }}" class="font-semibold hover:text-forest-700">{{ $titre }}</a><x-badge :label="$demande->statut->label()" :color="$demande->statut->color()" /></div>
                        <p class="mt-1 text-sm text-slate-500">{{ $demande->structureModel()->nom }} · {{ $demande->created_at->format('d/m/Y') }}@if($demande->cout_final ?? null) · {{ number_format($demande->cout_final, 2, ',', ' ') }} €@endif</p>
                    </li>
                    @endforeach
                    <li class="relative">
                        <span class="absolute -left-[33px] grid h-4 w-4 place-items-center rounded-full bg-white ring-2 ring-slate-300"></span>
                        <p class="font-semibold">Ajouté au dressing</p><p class="mt-1 text-sm text-slate-500">le {{ $vetement->created_at->format('d/m/Y') }}</p>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</section>
@endsection
