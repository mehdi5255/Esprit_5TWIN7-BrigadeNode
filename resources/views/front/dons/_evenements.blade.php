{{-- Collecte (lieu de dépôt) et distribution (lieu de remise) d'un don. Attend $demande et $espace (particulier | association | admin). --}}
@php
    $etapes = array_filter([
        'Dépôt prévu à la collecte' => $demande->collecte(),
        'Remis lors de la distribution' => $demande->distribution(),
    ]);
    $lien = fn ($evenement) => match ($espace) {
        'association' => route('association.evenements.show', $evenement),
        default => route('evenements.show', $evenement),
    };
@endphp
@if($etapes)
<div class="grid gap-3 sm:grid-cols-2">
    @foreach($etapes as $libelle => $evenement)
    <a href="{{ $lien($evenement) }}" class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm hover:border-rose-300">
        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white text-rose-600"><i data-lucide="{{ $evenement->type->icone() }}" class="h-5 w-5"></i></span>
        <span class="min-w-0">
            <span class="block text-xs font-semibold uppercase tracking-wide text-rose-600">{{ $libelle }}</span>
            <span class="block font-display font-bold text-slate-900">{{ $evenement->titre }}{{ $evenement->annule ? ' (annulé)' : '' }}</span>
            <span class="block text-slate-500">{{ $evenement->horaire() }}</span>
            <span class="block text-slate-500">{{ $evenement->lieu }}</span>
        </span>
    </a>
    @endforeach
</div>
@endif
