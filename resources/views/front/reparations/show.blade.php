@extends('layouts.front')
@use('App\Enums\StatutDemande')
@section('title', 'Réparation ' . $demande->reference())
@section('content')
<section class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
    <a href="{{ route('reparations.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-forest-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Retour à mes réparations</a>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-sm font-bold text-forest-600">{{ $demande->reference() }}</p><h1 class="mt-1 font-display text-3xl font-bold">{{ $demande->vetement->nom }}</h1><p class="mt-1 text-slate-500">{{ $demande->type_reparation->label() }} chez <a href="{{ route('ateliers.show', $demande->atelier) }}" class="font-semibold hover:text-forest-700">{{ $demande->atelier->nom }}</a></p></div>
        <x-badge :label="$demande->statut->label()" :color="$demande->statut->color()" />
    </div>
    <div class="mb-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">@include('partials.demandes.timeline')</div>

    @php
        $prochaineEtape = match ($demande->statut) {
            StatutDemande::EN_ATTENTE => ['clock', "L'atelier étudie votre demande. Vous pouvez encore l'annuler."],
            StatutDemande::ACCEPTEE => ['package', "C'est validé ! Déposez votre vêtement chez {$demande->atelier->nom}" . ($demande->atelier->adresseComplete() ? ' — '.$demande->atelier->adresseComplete() : '') . ". L'atelier établira le devis après l'avoir examiné."],
            StatutDemande::PRET => ['party-popper', "Votre vêtement est prêt ! Passez le récupérer chez {$demande->atelier->nom}" . ($demande->facture ? ' et réglez la facture de '.number_format((float) $demande->facture->montant_total, 2, ',', ' ').' €.' : '.')],
            StatutDemande::DEPOT_REMIS => match (true) {
                $demande->devis?->statut === \App\Enums\StatutDevis::PROPOSE => ['file-text', $demande->devis->estExpire() ? "Le devis de l'atelier a expiré : contactez-le, ou refusez-le pour récupérer votre vêtement." : "L'atelier a diagnostiqué votre vêtement et vous propose un devis : acceptez-le pour lancer la réparation."],
                $demande->devis?->statut === \App\Enums\StatutDevis::ACCEPTE => ['check', 'Devis accepté : la réparation va commencer.'],
                default => ['search', "L'atelier a bien reçu votre vêtement et le diagnostique : vous recevrez son devis."],
            },
            StatutDemande::EN_TRAITEMENT => ['scissors', 'Votre vêtement est entre de bonnes mains.'],
            default => null,
        };
    @endphp
    @if($prochaineEtape)
    <div class="mb-6 flex items-center gap-3 rounded-2xl bg-forest-50 p-4 text-sm font-medium text-forest-900"><i data-lucide="{{ $prochaineEtape[0] }}" class="h-5 w-5 shrink-0 text-forest-600"></i>{{ $prochaineEtape[1] }}</div>
    @endif

    @if($demande->photo_probleme)<div class="mb-6">@include('front.reparations._photo-probleme')</div>@endif
    <div class="grid gap-6 md:grid-cols-2">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-display text-lg font-bold">Ma demande</h2>
            <p class="mt-3 leading-7 text-slate-600">{{ $demande->description }}</p>
            <dl class="mt-5 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Budget maximum</dt><dd class="font-semibold">{{ $demande->budget_max ? number_format($demande->budget_max, 2, ',', ' ').' €' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Date souhaitée</dt><dd class="font-semibold">{{ $demande->date_souhaitee?->format('d/m/Y') ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Coût final</dt><dd class="font-semibold">{{ $demande->cout_final !== null ? number_format($demande->cout_final, 2, ',', ' ').' €' : '—' }}</dd></div>
            </dl>
            <div class="mt-6 flex flex-wrap gap-3">
                <x-button :href="route('vetements.show', $demande->vetement)" label="Voir le vêtement" icon="shirt" variant="secondary" />
                @if($demande->peutEtreAnnulee())
                <form method="POST" action="{{ route('reparations.annuler', $demande) }}" data-confirmer="Annuler cette demande ?" data-confirmer-bouton="Oui, annuler">@csrf<button class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"><i data-lucide="x" class="h-4 w-4"></i>Annuler la demande</button></form>
                @endif
                @if($demande->peutEtreModifiee())
                <x-button :href="route('reparations.edit', $demande)" label="Modifier" icon="pencil" variant="secondary" />
                @endif
                @if($demande->peutEtreSupprimee())
                <form method="POST" action="{{ route('reparations.destroy', $demande) }}" data-confirmer="Supprimer définitivement la demande ?">@csrf @method('DELETE')<button class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-500 hover:bg-red-50 hover:text-red-600"><i data-lucide="trash-2" class="h-4 w-4"></i>Supprimer</button></form>
                @endif
            </div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-display text-lg font-bold">Compte rendu de l'atelier</h2>
            @if($demande->reparation)
                @include('partials.demandes.compte-rendu', ['reparation' => $demande->reparation])
            @else
                <p class="mt-3 text-sm text-slate-500">Le compte rendu sera disponible dès que l'atelier aura commencé le travail.</p>
            @endif
        </div>
    </div>
    <div class="mt-6 grid gap-6 md:grid-cols-2">
        @include('front.reparations._documents', ['espace' => 'particulier'])
    </div>
    <div class="mt-6">@include('partials.demandes.avis', ['type' => 'reparations'])</div>
</section>
@endsection
