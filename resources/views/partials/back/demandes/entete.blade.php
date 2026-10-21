{{-- En-tête d'une demande côté gestion : suivi + vêtement + infos. Attend $demande, $details (libellé => valeur), $texte. --}}
<div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3"><h2 class="font-display text-lg font-bold">Suivi</h2><x-badge :label="$demande->statut->label()" :color="$demande->statut->color()" /></div>
    @include('partials.demandes.timeline')
</div>
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="font-display text-lg font-bold">Demande du particulier</h2>
    <div class="mt-5 flex gap-4">
        @if($demande->vetement->photoUrl())<img src="{{ $demande->vetement->photoUrl() }}" alt="" class="h-24 w-24 shrink-0 rounded-2xl object-cover">@endif
        <div>
            <p class="font-semibold">{{ $demande->vetement->nom }}</p>
            <p class="text-sm text-slate-500">{{ collect([$demande->vetement->categorie->libelle, $demande->vetement->matiere, $demande->vetement->couleur, $demande->vetement->taille ? 'taille '.$demande->vetement->taille : null, 'état '.mb_strtolower($demande->vetement->etat->label())])->filter()->join(' · ') }}</p>
            @if($texte)<p class="mt-2 whitespace-pre-line text-sm">{{ $texte }}</p>@endif
        </div>
    </div>
    <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
        @foreach(['Particulier' => $demande->particulier->name] + $details as $label => $valeur)
        <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">{{ $label }}</dt><dd class="mt-1 font-semibold">{{ $valeur }}</dd></div>
        @endforeach
    </dl>
</section>
