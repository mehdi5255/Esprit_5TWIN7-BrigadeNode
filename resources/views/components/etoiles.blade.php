{{-- Note en étoiles. Paramètres : note (float|null), avis (int, optionnel), taille (classes, optionnel) --}}
@props(['note' => null, 'avis' => null, 'taille' => 'h-4 w-4'])
@php $taille = $taille ?? 'h-4 w-4'; @endphp
@if($note)
<span class="inline-flex items-center gap-1" title="{{ number_format($note, 1, ',', '') }} / 5">
    @for($i = 1; $i <= 5; $i++)
        <i data-lucide="star" class="{{ $taille }} {{ $i <= round($note) ? 'fill-amber-400 text-amber-400' : 'text-slate-300' }}"></i>
    @endfor
    <span class="ml-1 text-sm font-bold">{{ number_format($note, 1, ',', '') }}</span>
    @isset($avis)<span class="text-sm text-slate-400">({{ $avis }})</span>@endisset
</span>
@else
<span class="text-sm text-slate-400">Pas encore d'avis</span>
@endif
