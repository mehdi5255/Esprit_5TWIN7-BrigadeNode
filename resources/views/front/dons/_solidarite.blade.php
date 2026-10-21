{{-- En-tête de la page « Solidarité » : besoins des associations et événements, en deux onglets. --}}
@php
    $onglets = [
        ['besoins.index', 'besoins.*', 'clipboard-list', 'Besoins des associations'],
        ['evenements.index', 'evenements.*', 'calendar-days', 'Collectes et distributions'],
    ];
@endphp
<div class="mb-8">
    <p class="text-sm font-bold text-rose-600">Solidarité</p>
    <h1 class="mt-2 font-display text-4xl font-bold">Agir pour les associations</h1>
    <p class="mt-2 max-w-2xl text-slate-500">Ce dont les associations ont besoin en ce moment, et leurs prochains rendez-vous pour déposer vos dons.</p>
    <nav class="mt-6 flex flex-wrap gap-2 border-b border-slate-200 text-sm font-semibold" aria-label="Solidarité">
        @foreach($onglets as [$route, $motif, $icone, $libelle])
        <a href="{{ route($route) }}" class="-mb-px inline-flex items-center gap-2 border-b-2 px-4 py-3 {{ request()->routeIs($motif) ? 'border-rose-500 text-rose-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}"><i data-lucide="{{ $icone }}" class="h-4 w-4"></i>{{ $libelle }}</a>
        @endforeach
    </nav>
</div>
