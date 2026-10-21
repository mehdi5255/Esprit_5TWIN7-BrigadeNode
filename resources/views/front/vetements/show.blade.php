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
                    @foreach(['État' => $vetement->etat->label(), 'Taille' => $vetement->taille, 'Couleur' => $vetement->couleur, 'Poids' => $vetement->poids_grammes ? number_format($vetement->poids_grammes, 0, ',', ' ').' g' : null] as $label => $valeur)
                    <div class="rounded-2xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">{{ $label }}</dt><dd class="mt-1 font-semibold">{{ $valeur ?: '—' }}</dd></div>
                    @endforeach
                </dl>
                <div class="mt-4 rounded-2xl bg-slate-50 p-3 text-sm">
                    <p class="text-xs text-slate-500">Composition @if($vetement->composition_estimee)<span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 font-semibold text-amber-700">estimée</span>@endif</p>
                    @if($vetement->compositionTexte())<p class="mt-1 font-semibold">{{ $vetement->compositionTexte() }}</p>
                    @else<p class="mt-1 text-slate-500">{{ $vetement->matiere ? $vetement->matiere.' — ' : '' }}<a href="{{ route('vetements.edit', $vetement) }}" class="font-semibold text-forest-700 hover:underline">Compléter la composition</a> pour connaître son impact.</p>@endif
                </div>
                @if($vetement->description)<p class="mt-6 leading-7 text-slate-600">{{ $vetement->description }}</p>@endif

                @if($impact = $vetement->impact())
                <div class="mt-6 rounded-2xl bg-forest-900 p-5 text-white">
                    <p class="flex items-center gap-2 text-sm font-semibold text-emerald-200"><i data-lucide="leaf" class="h-4 w-4"></i>Impact évité en lui donnant une seconde vie</p>
                    <div class="mt-3 flex flex-wrap gap-8">
                        <div><p class="font-display text-3xl font-bold">≈ {{ number_format($impact['co2'], 1, ',', ' ') }} kg</p><p class="text-xs text-emerald-100/70">de CO₂</p></div>
                        <div><p class="font-display text-3xl font-bold">≈ {{ number_format($impact['eau'], 0, ',', ' ') }} L</p><p class="text-xs text-emerald-100/70">d'eau</p></div>
                    </div>
                    <p class="mt-3 text-xs text-emerald-100/60">Estimation : fabrication d'un vêtement neuf équivalent, évitée en le réparant, le transformant ou le donnant{{ $vetement->composition_estimee ? ' (composition estimée)' : '' }}.</p>
                </div>
                @endif

                @if($vetement->conseilsEntretien()->isNotEmpty())
                <div class="mt-6">
                    <p class="flex items-center gap-2 text-sm font-semibold"><i data-lucide="sparkles" class="h-4 w-4 text-forest-600"></i>Pour le faire durer</p>
                    <ul class="mt-2 space-y-1.5 text-sm text-slate-600">
                        @foreach($vetement->conseilsEntretien() as $matiere => $conseil)<li><span class="font-semibold text-slate-800">{{ $matiere }} :</span> {{ $conseil }}</li>@endforeach
                    </ul>
                </div>
                @endif
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
