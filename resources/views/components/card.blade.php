@props(['title', 'value', 'icon' => 'activity', 'accent' => 'green', 'trend' => null])
@php
    $accentClasses = ['green' => 'bg-emerald-50 text-emerald-700', 'amber' => 'bg-amber-50 text-amber-700', 'blue' => 'bg-blue-50 text-blue-700', 'violet' => 'bg-violet-50 text-violet-700'];
    $accentClass = $accentClasses[$accent ?? 'green'];
@endphp
<article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-start justify-between">
        <div><p class="text-sm font-medium text-slate-500">{{ $title }}</p><p class="mt-2 font-display text-3xl font-bold text-slate-900">{{ $value }}</p></div>
        <span class="grid h-11 w-11 place-items-center rounded-xl {{ $accentClass }}"><i data-lucide="{{ $icon ?? 'activity' }}" class="h-5 w-5"></i></span>
    </div>
    @isset($trend)<p class="mt-4 text-xs text-slate-500"><span class="font-semibold text-emerald-600">{{ $trend }}</span> ce mois-ci</p>@endisset
</article>
