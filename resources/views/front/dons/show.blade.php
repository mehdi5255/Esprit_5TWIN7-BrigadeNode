@extends('layouts.front')
@use('App\Enums\StatutDon')
@section('title', 'Don ' . $demande->reference())
@section('content')
<section class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
    <a href="{{ route('dons.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-forest-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Retour à mes dons</a>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-sm font-bold text-rose-600">{{ $demande->reference() }}</p><h1 class="mt-1 font-display text-3xl font-bold">{{ $demande->vetement->nom }}</h1><p class="mt-1 text-slate-500">Don à <a href="{{ route('associations.show', $demande->association) }}" class="font-semibold hover:text-forest-700">{{ $demande->association->nom }}</a></p></div>
        <x-badge :label="$demande->statut->label()" :color="$demande->statut->color()" />
    </div>
    <div class="mb-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">@include('partials.demandes.timeline')</div>

    @php
        $prochaineEtape = match ($demande->statut) {
            StatutDon::EN_ATTENTE => ['clock', "L'association étudie votre proposition. Vous pouvez encore l'annuler."],
            StatutDon::ACCEPTEE => ($collecte = $demande->collecte())
                ? ['calendar', "Don accepté ! Apportez votre vêtement à la collecte « {$collecte->titre} », {$collecte->horaire()}, {$collecte->lieu}."]
                : ['package', "Don accepté ! Apportez votre vêtement à {$demande->association->nom}" . ($demande->association->adresseComplete() ? ' — '.$demande->association->adresseComplete() : '') . '.'],
            StatutDon::DEPOT_REMIS => ['package-check', "L'association a bien reçu votre vêtement. Merci !"],
            StatutDon::EN_TRAITEMENT => ['hand-heart', "Votre don est en préparation pour être remis aux bénéficiaires."],
            default => null,
        };
    @endphp
    @if($prochaineEtape)
    <div class="mb-6 flex items-center gap-3 rounded-2xl bg-rose-50 p-4 text-sm font-medium text-rose-900"><i data-lucide="{{ $prochaineEtape[0] }}" class="h-5 w-5 shrink-0 text-rose-600"></i>{{ $prochaineEtape[1] }}</div>
    @endif

    <div class="mb-6">@include('front.dons._evenements', ['espace' => 'particulier'])</div>
    @if($demande->orientation)
    <div class="mb-6 flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-sm shadow-sm">
        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-forest-50 text-forest-700"><i data-lucide="{{ $demande->orientation->icone() }}" class="h-5 w-5"></i></span>
        <p><span class="font-semibold">Devenir de votre don : {{ $demande->orientation->label() }}.</span> <span class="text-slate-500">{{ $demande->orientation->description() }}</span></p>
    </div>
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-display text-lg font-bold">Mon don</h2>
            @if($demande->besoin)<p class="mt-3 text-sm"><span class="text-slate-500">Besoin :</span> <span class="font-semibold">{{ $demande->besoin->titre }}</span></p>@endif
            <p class="mt-3 leading-7 text-slate-600">{{ $demande->message ?: 'Aucun message.' }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <x-button :href="route('vetements.show', $demande->vetement)" label="Voir le vêtement" icon="shirt" variant="secondary" />
                @if($demande->peutEtreAnnulee())
                <form method="POST" action="{{ route('dons.annuler', $demande) }}" data-confirmer="Annuler ce don ?" data-confirmer-bouton="Oui, annuler">@csrf<button class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"><i data-lucide="x" class="h-4 w-4"></i>Annuler le don</button></form>
                @endif
                @if($demande->peutEtreModifiee())
                <x-button :href="route('dons.edit', $demande)" label="Modifier" icon="pencil" variant="secondary" />
                @endif
                @if($demande->peutEtreSupprimee())
                <form method="POST" action="{{ route('dons.destroy', $demande) }}" data-confirmer="Supprimer définitivement le don ?">@csrf @method('DELETE')<button class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-500 hover:bg-red-50 hover:text-red-600"><i data-lucide="trash-2" class="h-4 w-4"></i>Supprimer</button></form>
                @endif
            </div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-display text-lg font-bold">Remise aux bénéficiaires</h2>
            @if($demande->statut === StatutDon::REMIS_BENEFICIAIRE)
                <p class="mt-3 flex items-center gap-2 text-sm font-semibold text-forest-700"><i data-lucide="heart" class="h-4 w-4 fill-current"></i>Remis le {{ $demande->date_remise->translatedFormat('d F Y') }} à {{ $demande->nombre_beneficiaires }} bénéficiaire{{ $demande->nombre_beneficiaires > 1 ? 's' : '' }}</p>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $demande->beneficiaire_info }}</p>
            @else
                <p class="mt-3 text-sm text-slate-500">Vous serez notifié dès que votre don aura été remis.</p>
            @endif
        </div>
    </div>
    <div class="mt-6">@include('partials.demandes.avis', ['type' => 'dons'])</div>
</section>
@endsection
