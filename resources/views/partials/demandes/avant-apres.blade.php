{{-- Photos avant / après. Attend $avant et $apres (URL ou null). --}}
@if($avant || $apres)
<div class="mt-5 grid grid-cols-2 gap-3">
    @foreach(['Avant' => $avant, 'Après' => $apres] as $label => $url)
        @if($url)
        <figure><img src="{{ $url }}" alt="Photo {{ mb_strtolower($label) }}" class="aspect-square w-full rounded-2xl object-cover"><figcaption class="mt-1 text-center text-xs text-slate-500">{{ $label }}</figcaption></figure>
        @else
        <div class="grid aspect-square place-items-center rounded-2xl bg-slate-50 text-xs text-slate-400">{{ $label }} : pas de photo</div>
        @endif
    @endforeach
</div>
@endif
