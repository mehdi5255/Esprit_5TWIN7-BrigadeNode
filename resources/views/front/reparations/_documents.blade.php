{{-- Devis, facture et garantie d'une demande de réparation. Attend $demande et $espace (particulier | atelier | admin). --}}
@use('App\Enums\StatutDemande')
@use('App\Enums\StatutDevis')
@php
    $euros = fn ($montant) => number_format((float) $montant, 2, ',', ' ').' €';
    $devis = $demande->devis;
    $facture = $demande->facture;
    $garantie = $demande->garantieAccordee();
    $carte = 'rounded-2xl border border-slate-200 bg-white p-6 shadow-sm';
    $ligne = 'flex justify-between gap-4';
    $routeFacture = match ($espace) {
        'particulier' => route('reparations.facture', $demande),
        'atelier' => route('atelier.reparations.facture', $demande),
        default => null,
    };
    $routeDemande = fn ($d) => match ($espace) {
        'particulier' => route('reparations.show', $d),
        'atelier' => route('atelier.reparations.show', $d),
        default => route('admin.reparations.show', $d->id),
    };
@endphp

@if($demande->estRetourSousGarantie())
<section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-900">
    <p class="flex items-center gap-2 font-semibold"><i data-lucide="shield-check" class="h-5 w-5"></i>Retour sous garantie — sans frais</p>
    <p class="mt-1">Garantie de la réparation <a href="{{ $routeDemande($demande->garantie->demandeOrigine()) }}" class="font-semibold underline">{{ $demande->garantie->demandeOrigine()->reference() }}</a>, valable jusqu'au {{ $demande->garantie->date_fin->format('d/m/Y') }}.</p>
</section>
@endif

{{-- ── Devis ── --}}
@if($devis && ! $demande->estRetourSousGarantie())
<section class="{{ $carte }}">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="flex items-center gap-2 font-display text-lg font-bold"><i data-lucide="file-text" class="h-5 w-5 text-forest-600"></i>Devis <span class="text-sm font-medium text-slate-400">{{ $devis->numero() }}</span></h2>
        <x-badge :label="$devis->libelleStatut()" :color="$devis->couleurStatut()" />
    </div>
    <dl class="mt-4 space-y-2 text-sm">
        <div class="{{ $ligne }}"><dt class="text-slate-500">Main-d'œuvre</dt><dd class="font-semibold">{{ $euros($devis->main_oeuvre) }}</dd></div>
        <div class="{{ $ligne }}"><dt class="text-slate-500">Délai estimé</dt><dd class="font-semibold">{{ $devis->delai_jours ? $devis->delai_jours.' jour(s)' : '—' }}</dd></div>
        <div class="{{ $ligne }}"><dt class="text-slate-500">Valable jusqu'au</dt><dd class="font-semibold">{{ $devis->valide_jusqu_au->format('d/m/Y') }}</dd></div>
        @if($devis->date_reponse)<div class="{{ $ligne }}"><dt class="text-slate-500">Réponse le</dt><dd class="font-semibold">{{ $devis->date_reponse->format('d/m/Y') }}</dd></div>@endif
    </dl>

    @if($espace === 'particulier' && $devis->statut === StatutDevis::PROPOSE && $demande->statut === StatutDemande::DEPOT_REMIS)
        <div class="mt-5 flex flex-wrap gap-3">
            @if($devis->estExpire())
                <p class="w-full text-sm text-slate-500">Ce devis a expiré : contactez l'atelier pour un nouveau devis, ou refusez-le pour libérer votre vêtement.</p>
            @else
                <form method="POST" action="{{ route('reparations.devis.accepter', $demande) }}">@csrf<button class="inline-flex items-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-forest-700"><i data-lucide="check" class="h-4 w-4"></i>Accepter le devis</button></form>
            @endif
            <form method="POST" action="{{ route('reparations.devis.refuser', $demande) }}" data-confirmer="Refuser ce devis ? La demande sera annulée." data-confirmer-bouton="Oui, refuser">@csrf<button class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"><i data-lucide="x" class="h-4 w-4"></i>Refuser</button></form>
        </div>
    @elseif($espace === 'atelier' && $devis->statut === StatutDevis::PROPOSE)
        <details class="mt-5 rounded-xl border border-slate-200" @if($errors->hasAny(['main_oeuvre', 'delai_jours', 'valide_jusqu_au'])) open @endif>
            <summary class="cursor-pointer list-none px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"><i data-lucide="pencil" class="mr-1 inline h-4 w-4"></i>Modifier le devis</summary>
            <form method="POST" action="{{ route('atelier.reparations.devis', $demande) }}" class="space-y-3 border-t border-slate-100 p-4">@csrf @method('PUT')
                <div class="grid grid-cols-2 gap-3">
                    <x-champ label="Montant (€)" name="main_oeuvre" type="number" :required="true" :value="$devis->main_oeuvre" min="0" step="0.5" />
                    <x-champ label="Délai (jours)" name="delai_jours" type="number" :value="$devis->delai_jours" min="1" />
                </div>
                <x-champ label="Valable jusqu'au" name="valide_jusqu_au" type="date" :required="true" :value="$devis->valide_jusqu_au->toDateString()" :min="now()->toDateString()" />
                <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-forest-700">Enregistrer le devis</button>
            </form>
        </details>
    @endif
</section>
@endif

{{-- ── Facture ── --}}
@if($facture)
<section class="{{ $carte }}">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="flex items-center gap-2 font-display text-lg font-bold"><i data-lucide="receipt" class="h-5 w-5 text-forest-600"></i>Facture <span class="text-sm font-medium text-slate-400">{{ $facture->numero() }}</span></h2>
        <x-badge :label="$facture->statut->label()" :color="$facture->statut->color()" />
    </div>
    <dl class="mt-4 space-y-2 text-sm">
        <div class="{{ $ligne }}"><dt class="text-slate-500">Montant du devis</dt><dd class="font-semibold">{{ $euros($facture->montant_devis) }}</dd></div>
        @if($facture->supplement > 0)
        <div class="{{ $ligne }}"><dt class="text-slate-500">Supplément</dt><dd class="font-semibold">{{ $euros($facture->supplement) }}</dd></div>
        <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">{{ $facture->motif_supplement }}</p>
        @endif
        <div class="{{ $ligne }} border-t border-slate-100 pt-2 text-base"><dt class="font-semibold">Total</dt><dd class="font-display font-bold">{{ $euros($facture->montant_total) }}</dd></div>
        <div class="{{ $ligne }}"><dt class="text-slate-500">Émise le</dt><dd>{{ $facture->date_emission->format('d/m/Y') }}</dd></div>
        @if($facture->date_paiement)<div class="{{ $ligne }}"><dt class="text-slate-500">Payée le</dt><dd>{{ $facture->date_paiement->format('d/m/Y') }}</dd></div>@endif
    </dl>
    @if($routeFacture)
    <a href="{{ $routeFacture }}" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-forest-700 hover:underline"><i data-lucide="printer" class="h-4 w-4"></i>Voir et imprimer la facture</a>
    @endif
</section>
@endif

{{-- ── Garantie ── --}}
@if($garantie)
<section class="{{ $carte }}">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="flex items-center gap-2 font-display text-lg font-bold"><i data-lucide="shield-check" class="h-5 w-5 text-forest-600"></i>Garantie {{ $garantie->duree_mois }} mois</h2>
        <x-badge :label="$garantie->libelleStatut()" :color="$garantie->estActive() ? 'green' : 'gray'" />
    </div>
    <p class="mt-3 text-sm"><span class="text-slate-500">Du</span> <span class="font-semibold">{{ $garantie->date_debut->format('d/m/Y') }}</span> <span class="text-slate-500">au</span> <span class="font-semibold">{{ $garantie->date_fin->format('d/m/Y') }}</span></p>
    <p class="mt-2 text-sm text-slate-500">{{ $garantie->conditions }}</p>

    @if($garantie->retours->isNotEmpty())
    <div class="mt-4 text-sm">
        <p class="font-semibold text-slate-700">Retours sous garantie ({{ $garantie->retours->count() }})</p>
        <ul class="mt-2 space-y-1">
            @foreach($garantie->retours as $retour)
            <li class="flex items-center justify-between gap-3"><a href="{{ $routeDemande($retour) }}" class="font-semibold text-forest-700 hover:underline">{{ $retour->reference() }}</a><x-badge :label="$retour->statut->label()" :color="$retour->statut->color()" /></li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($espace === 'particulier' && $garantie->estActive())
        @if($demande->vetement->estDisponible())
        <details class="mt-5 rounded-xl border border-forest-200" @if($errors->has('description')) open @endif>
            <summary class="cursor-pointer list-none px-4 py-2.5 text-sm font-semibold text-forest-700 hover:bg-forest-50"><i data-lucide="rotate-ccw" class="mr-1 inline h-4 w-4"></i>Utiliser ma garantie</summary>
            <form method="POST" action="{{ route('reparations.garantie.retour', $garantie) }}" class="space-y-3 border-t border-forest-100 p-4">@csrf
                <x-champ label="Quel est le problème ?" name="description" type="textarea" :required="true" placeholder="Ex. La couture reprise s'est de nouveau ouverte." />
                <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-forest-700">Envoyer le retour à {{ $demande->atelier->nom }}</button>
            </form>
        </details>
        @else
        <p class="mt-4 text-xs text-slate-400">Votre vêtement fait déjà l'objet d'une autre demande : la garantie pourra être utilisée ensuite.</p>
        @endif
    @endif
</section>
@endif
