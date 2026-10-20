@extends('layouts.back')
@use('App\Enums\StatutDon')
@section('title', 'Don ' . $demande->reference())
@section('breadcrumb', ($espace === 'admin' ? 'Supervision' : 'Dons') . ' / ' . $demande->reference())
@section('content')
@php
    $primaire = 'inline-flex w-full items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-forest-700';
    $action = fn ($nom) => route("association.dons.{$nom}", $demande);
@endphp
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        @include('partials.back.demandes.entete', [
            'texte' => $demande->message,
            'details' => [
                'Besoin' => $demande->besoin?->titre ?? 'Don libre',
                'Association' => $demande->association->nom,
                'Proposé le' => $demande->created_at->format('d/m/Y'),
                'Avis IA du donateur' => \App\Models\Dons\ControleQualite::VERDICTS[$demande->controle_ia] ?? '—',
            ],
        ])
        @include('back.dons._orientation')
        @include('front.dons._evenements', ['espace' => $espace])
        @if($demande->statut === StatutDon::REMIS_BENEFICIAIRE)
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-display text-lg font-bold">Remise aux bénéficiaires</h2>
            <p class="mt-3 text-sm font-semibold text-forest-700">{{ $demande->nombre_beneficiaires }} bénéficiaire{{ $demande->nombre_beneficiaires > 1 ? 's' : '' }} · le {{ $demande->date_remise->format('d/m/Y') }}</p>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $demande->beneficiaire_info }}</p>
        </section>
        @endif
        @include('partials.back.demandes.avis')
    </div>

    <aside>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-display text-lg font-bold">{{ $espace === 'admin' ? 'Statut' : 'Prochaine action' }}</h2>
            <div class="mt-4 space-y-4 text-sm">
            @if($espace === 'admin')
                <p class="text-slate-500">Ce don est traité par <span class="font-semibold text-slate-700">{{ $demande->association->nom }}</span>. L'administration consulte sans intervenir.</p>
            @else
            @switch($demande->statut)
                @case(StatutDon::EN_ATTENTE)
                    @include('partials.back.demandes.decision', ['texte' => 'Ce vêtement correspond-il à vos besoins ? Le donateur sera notifié.', 'routeAccepter' => $action('accepter'), 'routeRefuser' => $action('refuser')])
                    @break
                @case(StatutDon::ACCEPTEE)
                    <p class="text-slate-500">En attente de l'apport du vêtement par le donateur.</p>
                    <form method="POST" action="{{ $action('depot') }}">@csrf<button class="{{ $primaire }}"><i data-lucide="package-check" class="h-4 w-4"></i>Confirmer la réception</button></form>
                    @break
                @case(StatutDon::DEPOT_REMIS)
                    <p class="text-slate-500">Vêtement reçu. Préparez-le (tri, lavage, lot…) avant la remise.</p>
                    <form method="POST" action="{{ $action('preparer') }}">@csrf<button class="{{ $primaire }}"><i data-lucide="hand-heart" class="h-4 w-4"></i>Préparer le don</button></form>
                    @break
                @case(StatutDon::EN_TRAITEMENT)
                    <form method="POST" action="{{ $action('remettre') }}" class="space-y-4">@csrf
                        <x-champ label="Bénéficiaires" name="beneficiaire_info" type="textarea" :required="true" :value="$demande->orientation?->remiseProposee()" placeholder="Ex. Distribution du samedi matin, familles hébergées au centre Montreuil." help="Restez anonyme : pas de nom de bénéficiaire." />
                        <x-champ label="Nombre de bénéficiaires" name="nombre_beneficiaires" type="number" :required="true" :value="1" min="1" />
                        @if(($distributions ?? collect())->isNotEmpty())
                        <x-champ label="Lors de la distribution" name="distribution_id" type="select" vide="Aucune (remise hors distribution)" :options="$distributions->mapWithKeys(fn ($e) => [$e->id => $e->titre.' — '.$e->debut->translatedFormat('d M, H:i')])" help="Le don apparaîtra dans le bilan de cette distribution." />
                        @else
                        <p class="text-xs text-slate-400">Astuce : créez une <a href="{{ route('association.evenements.create') }}" class="font-semibold underline">distribution</a> pour y rattacher vos remises.</p>
                        @endif
                        <button class="{{ $primaire }}"><i data-lucide="heart" class="h-4 w-4"></i>Marquer comme remis</button>
                    </form>
                    @break
                @default
                    <p class="text-slate-500">Ce don est clôturé ({{ mb_strtolower($demande->statut->label()) }}). Aucune action requise.</p>
            @endswitch
            @endif
            </div>
        </section>
    </aside>
</div>
@endsection
