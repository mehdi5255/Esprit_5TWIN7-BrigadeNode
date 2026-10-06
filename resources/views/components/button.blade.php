@php
    $styles = [
        'primary' => 'bg-forest-600 text-white hover:bg-forest-700 shadow-sm',
        'secondary' => 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
        'danger' => 'bg-red-600 text-white hover:bg-red-700'
    ];
@endphp
<a href="{{ $href ?? '#' }}" class="inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ $styles[$variant ?? 'primary'] }}">
    @isset($icon)<i data-lucide="{{ $icon }}" class="h-4 w-4"></i>@endisset
    {{ $label }}
</a>
