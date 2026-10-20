@extends('layouts.front')
@use('App\Enums\StatutDemande')
@section('title', 'Transformation ' . $demande->reference())
@section('content')
<section class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
    <a href="{{ route('transformations.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-forest-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Retour à mes transformations</a>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-sm font-bold text-violet-600">{{ $demande->reference() }}</p><h1 class="mt-1 font-display text-3xl font-bold">{{ $demande->vetement->nom }}</h1><p class="mt-1 text-slate-500">Projet d'upcycling avec <a href="{{ route('ateliers.show', $demande->atelier) }}" class="font-semibold hover:text-forest-700">{{ $demande->atelier->nom }}</a></p></div>
        <x-badge :label="$demande->statut->label()" :color="$demande->statut->color()" />
    </div>
    <div class="mb-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">@include('partials.demandes.timeline')</div>

    @php
        $prochaineEtape = match ($demande->statut) {
            StatutDemande::EN_ATTENTE => ['clock', "L'atelier étudie votre idée. Vous pouvez encore annuler le projet."],
            StatutDemande::ACCEPTEE => ['package', "Projet accepté ! Déposez votre vêtement chez {$demande->atelier->nom}" . ($demande->atelier->adresseComplete() ? ' — '.$demande->atelier->adresseComplete() : '') . '.'],
            StatutDemande::DEPOT_REMIS => ['package-check', "L'atelier a reçu votre vêtement. La confection va commencer."],
            StatutDemande::EN_TRAITEMENT => ['scissors', 'Votre pièce est en cours de confection.'],
            StatutDemande::PRET => ['party-popper', "Votre création est prête ! Passez la récupérer chez {$demande->atelier->nom}."],
            default => null,
        };
    @endphp
    @if($prochaineEtape)
    <div class="mb-6 flex items-center gap-3 rounded-2xl bg-violet-50 p-4 text-sm font-medium text-violet-900"><i data-lucide="{{ $prochaineEtape[0] }}" class="h-5 w-5 shrink-0 text-violet-600"></i>{{ $prochaineEtape[1] }}</div>
    @endif

    <div class="mb-6">@include('front.transformations._inspiration', ['lien' => $demande->inspiration?->estVisible() ? route('inspirations.show', $demande->inspiration) : null])</div>
    @if($demande->apercu_ia)<div class="mb-6">@include('front.transformations._apercu-ia')</div>@endif

    <div class="grid gap-6 md:grid-cols-2">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-display text-lg font-bold">Mon idée</h2>
            <p class="mt-3 leading-7 text-slate-600">{{ $demande->idee }}</p>
            <dl class="mt-5 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Budget maximum</dt><dd class="font-semibold">{{ $demande->budget_max ? number_format($demande->budget_max, 2, ',', ' ').' €' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Date souhaitée</dt><dd class="font-semibold">{{ $demande->date_souhaitee?->format('d/m/Y') ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Coût final</dt><dd class="font-semibold">{{ $demande->cout_final !== null ? number_format($demande->cout_final, 2, ',', ' ').' €' : '—' }}</dd></div>
            </dl>
            <div class="mt-6 flex flex-wrap gap-3">
                <x-button :href="route('vetements.show', $demande->vetement)" label="Voir le vêtement" icon="shirt" variant="secondary" />
                @if($demande->peutEtreAnnulee())
                <form method="POST" action="{{ route('transformations.annuler', $demande) }}" data-confirmer="Annuler ce projet ?" data-confirmer-bouton="Oui, annuler">@csrf<button class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"><i data-lucide="x" class="h-4 w-4"></i>Annuler le projet</button></form>
                @endif
                @if($demande->peutEtreModifiee())
                <x-button :href="route('transformations.edit', $demande)" label="Modifier" icon="pencil" variant="secondary" />
                @endif
                @if($demande->peutEtreSupprimee())
                <form method="POST" action="{{ route('transformations.destroy', $demande) }}" data-confirmer="Supprimer définitivement le projet ?">@csrf @method('DELETE')<button class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-500 hover:bg-red-50 hover:text-red-600"><i data-lucide="trash-2" class="h-4 w-4"></i>Supprimer</button></form>
                @endif
            </div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-display text-lg font-bold">Produit final</h2>
            @if($demande->produit_final)
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $demande->produit_final }}</p>
            @else
                <p class="mt-3 text-sm text-slate-500">La description et les photos du résultat apparaîtront ici à la fin de la confection.</p>
            @endif
            @include('partials.demandes.avant-apres', ['avant' => $demande->photoAvantUrl() ?? $demande->vetement->photoUrl(), 'apres' => $demande->photoApresUrl()])
        </div>
    </div>
    <div class="mt-6">@include('partials.demandes.avis', ['type' => 'transformations'])</div>
</section>
@endsection
