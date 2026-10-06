{{--
    Liste de demandes partagée (admin / atelier / association).
    Attend : $demandes, $compteurs, $espace, $type (reparations|transformations|dons), $statuts (enum cases), $cartes ([titre, statuts[], icône, accent]).
--}}
@php
    $structure = $type === 'dons' ? 'association' : 'atelier';
    $route = fn ($action, $params = []) => route("{$espace}.{$type}.{$action}", $params);
@endphp
<div class="mb-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    @foreach($cartes as [$titre, $valeurs, $icone, $accent])
        @include('components.card', ['title' => $titre, 'value' => collect($valeurs)->sum(fn ($v) => $compteurs[$v] ?? 0), 'icon' => $icone, 'accent' => $accent])
    @endforeach
</div>
@if($espace === 'admin')
<p class="mb-4 flex items-center gap-2 text-sm text-slate-500"><i data-lucide="eye" class="h-4 w-4"></i>Supervision en lecture seule : les demandes sont traitées par {{ $structure === 'atelier' ? 'les ateliers' : 'les associations' }}.</p>
@endif
<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <form method="GET" action="{{ $route('index') }}" class="flex flex-wrap gap-3 border-b border-slate-100 p-4">
        <div class="flex min-w-64 flex-1 items-center gap-2 rounded-xl bg-slate-100 px-3"><i data-lucide="search" class="h-4 w-4 text-slate-400"></i><input name="q" value="{{ request('q') }}" class="w-full bg-transparent py-2.5 text-sm outline-none" placeholder="Rechercher un vêtement ou un particulier"></div>
        <select name="statut" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold">
            <option value="">Tous les statuts</option>
            @foreach($statuts as $statut)<option value="{{ $statut->value }}" @selected(request('statut') === $statut->value)>{{ $statut->label() }} ({{ $compteurs[$statut->value] ?? 0 }})</option>@endforeach
        </select>
        <button class="rounded-xl bg-forest-600 px-4 text-sm font-semibold text-white hover:bg-forest-700">Filtrer</button>
    </form>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Référence</th><th class="px-6 py-3">Particulier</th><th class="px-6 py-3">Vêtement</th>@if($espace === 'admin')<th class="px-6 py-3">{{ ucfirst($structure) }}</th>@endif<th class="px-6 py-3">Statut</th><th class="px-6 py-3">Date</th><th class="px-6 py-3"></th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($demandes as $demande)
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4 font-semibold"><a href="{{ $route('show', $demande) }}" class="hover:text-forest-700">{{ $demande->reference() }}</a></td>
                <td class="px-6 py-4">{{ $demande->particulier->name }}</td>
                <td class="px-6 py-4 text-slate-500">{{ $demande->vetement->nom }}</td>
                @if($espace === 'admin')<td class="px-6 py-4 text-slate-500">{{ $demande->{$structure}->nom }}</td>@endif
                <td class="px-6 py-4">@include('components.badge', ['label' => $demande->statut->label(), 'color' => $demande->statut->color()])</td>
                <td class="px-6 py-4 text-slate-500">{{ $demande->created_at->format('d/m/Y') }}</td>
                <td class="px-6 py-4 text-right"><a href="{{ $route('show', $demande) }}" class="inline-flex items-center gap-1 font-semibold text-forest-700">{{ $espace === 'admin' ? 'Consulter' : 'Traiter' }} <i data-lucide="arrow-right" class="h-4 w-4"></i></a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">Aucune demande.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($demandes->hasPages())<div class="border-t border-slate-100 px-6 py-4">{{ $demandes->links() }}</div>@endif
</section>
