{{-- Suivi d'une demande (réparation, transformation ou don), étape par étape. Attend $demande. --}}
@php
    $etapes = $demande->statut::etapes();
    $position = array_search($demande->statut, $etapes, true);
    $dates = [
        'en_attente' => $demande->created_at,
        'acceptee' => $demande->date_acceptation,
        'depot_remis' => $demande->date_depot_effectif,
        'en_traitement' => $demande->date_debut_traitement,
        'pret' => $demande->date_fin_traitement ?? null,
        'recupere' => $demande->date_recuperation ?? null,
        'remis_beneficiaire' => $demande->date_remise ?? null,
    ];
    $refusee = $demande->statut->value === 'refusee';
@endphp
@if($position === false)
    <div class="rounded-2xl {{ $refusee ? 'bg-red-50 text-red-800' : 'bg-slate-100 text-slate-700' }} p-4 text-sm">
        <p class="flex items-center gap-3 font-semibold"><i data-lucide="{{ $refusee ? 'circle-x' : 'ban' }}" class="h-5 w-5"></i>Demande {{ mb_strtolower($demande->statut->label()) }} le {{ $demande->updated_at->format('d/m/Y à H:i') }}.</p>
        @if($refusee && $demande->motif_refus)<p class="mt-2 pl-8">Motif : {{ $demande->motif_refus }}</p>@endif
        <p class="mt-1 pl-8 text-xs opacity-80">Le vêtement est de nouveau disponible.</p>
    </div>
@else
    <ol class="grid gap-4 sm:grid-cols-{{ count($etapes) }}">
        @foreach($etapes as $i => $etape)
        @php $fait = $i <= $position; $courant = $i === $position; @endphp
        <li class="flex gap-3 sm:flex-col sm:gap-2">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full {{ $courant ? 'bg-forest-600 text-white ring-4 ring-forest-100' : ($fait ? 'bg-forest-100 text-forest-700' : 'bg-slate-100 text-slate-400') }}">
                @if($fait && ! $courant)<i data-lucide="check" class="h-4 w-4"></i>@else<span class="text-xs font-bold">{{ $i + 1 }}</span>@endif
            </span>
            <div><p class="text-sm font-semibold {{ $fait ? 'text-slate-800' : 'text-slate-400' }}">{{ $etape->label() }}</p>
                @if($fait && ($dates[$etape->value] ?? null))<p class="text-xs text-slate-500">{{ $dates[$etape->value]->format('d/m H:i') }}</p>@endif</div>
        </li>
        @endforeach
    </ol>
@endif
